<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Catalog\Command\DuplicateArticle;
use App\Controller\Admin\Trait\RemovesOrphanImagesTrait;
use App\Entity\Article;
use App\Enum\ItemCondition;
use App\Messenger\CommandBusInterface;
use App\Repository\ImageRepository;
use App\Service\ShopSettingsProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * What every sellable article shares in the back office: the Fiche / Vente / Visuels tabs,
 * thumbnail and stock badge in lists, the "Dupliquer" action, and protection against
 * deleting an article that has been ordered.
 *
 * @template TArticle of Article
 *
 * @extends AbstractCrudController<TArticle>
 */
abstract class AbstractArticleCrudController extends AbstractCrudController
{
    use RemovesOrphanImagesTrait;

    public function __construct(
        private readonly ShopSettingsProviderInterface $shopSettingsProvider,
    ) {
    }

    /**
     * Content of the "Fiche" tab: what the article is (name included, see nameField()).
     *
     * @return iterable<FieldInterface>
     */
    abstract protected function configureSpecificFields(string $pageName): iterable;

    /**
     * Compact rows used when the article is an entry of another form (releases in an
     * album, variants in a merch). Nested tabs would be unusable there.
     *
     * @return iterable<FieldInterface>
     */
    protected function configureEmbeddedFields(string $pageName): iterable
    {
        return $this->configureSpecificFields($pageName);
    }

    protected function isEmbedded(string $pageName): bool
    {
        return false;
    }

    /**
     * Shown in the "Visuels" tab: where the pictures come from when the article has none.
     */
    protected function fallbackPicturesLabel(): string
    {
        return 'Sans photo propre, aucun visuel n\'est affiché.';
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [ImageRepository::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setSearchFields(['name', 'sku', 'gtin'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new('duplicate', 'Dupliquer', 'fa fa-copy')
            ->linkToUrl(fn (Article $article): string => $this->postActionUrl('duplicate', $article))
            ->renderAsForm();

        $hasNoOrder = static fn (Article $article): bool => $article->getOrderDetails()->isEmpty();

        return $actions
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_EDIT, $duplicate)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, static fn (Action $action): Action => $action->displayIf($hasNoOrder))
            ->update(Crud::PAGE_DETAIL, Action::DELETE, static fn (Action $action): Action => $action->displayIf($hasNoOrder))
            ->disable(Action::BATCH_DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('published', 'Publié'))
            ->add(NumericFilter::new('stock', 'Stock'));
    }

    public function configureFields(string $pageName): iterable
    {
        if ($this->isEmbedded($pageName)) {
            yield from $this->configureEmbeddedFields($pageName);

            return;
        }

        // List only.
        yield ImageField::new('coverImageName', 'Visuel')
            ->setBasePath('/upload/albums')
            ->setSortable(false)
            ->onlyOnIndex();

        yield FormField::addTab('Fiche', 'fa fa-tag');
        yield from $this->configureSpecificFields($pageName);

        yield FormField::addTab('Vente', 'fa fa-euro-sign');
        yield FormField::addFieldset('Prix & stock', 'fa fa-coins');
        yield $this->priceField()->setColumns(3);
        yield $this->stockField()->setColumns(3);
        yield $this->conditionField()->setColumns(3)->hideOnIndex();
        yield $this->publishedField($pageName)->setColumns(3);
        yield FormField::addFieldset('Références', 'fa fa-barcode')
            ->setHelp('Utiles pour la caisse, l\'inventaire et les moteurs de recherche.');
        yield $this->skuField()->setColumns(6)->hideOnIndex();
        yield $this->gtinField()->setColumns(6)->hideOnIndex();

        yield FormField::addTab('Visuels', 'fa fa-image')
            ->setBadge(static fn (?Article $article): ?int => $article?->getImages()->count() ?: null)
            ->onlyOnForms();
        yield CollectionField::new('images')
            ->setLabel(false)
            ->setHelp('Photos propres à cet article, la plus petite position en premier. '.$this->fallbackPicturesLabel())
            ->useEntryCrudForm(ImageCrudController::class, ImageCrudController::PAGE_EMBEDDED_NEW, ImageCrudController::PAGE_EMBEDDED_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->onlyOnForms();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $removed = $this->removedImages($entityInstance->getImages());
        parent::updateEntity($entityManager, $entityInstance);
        $this->deleteOrphanImages($entityManager, $removed);
    }

    protected function nameField(): TextField
    {
        return TextField::new('name', 'Nom');
    }

    protected function priceField(): MoneyField
    {
        return MoneyField::new('price', 'Prix')->setCurrency('EUR');
    }

    protected function stockField(): IntegerField
    {
        return IntegerField::new('stock', 'Stock')
            ->setTemplatePath('admin/field/stock_badge.html.twig')
            // Read once per request here rather than once per row in the template.
            ->setCustomOption('lowStockThreshold', $this->shopSettingsProvider->get()->getLowStockThreshold());
    }

    protected function conditionField(): ChoiceField
    {
        return ChoiceField::new('itemCondition', 'État')
            ->setChoices(ItemCondition::cases())
            ->setFormTypeOption('choice_label', static fn (ItemCondition $condition): string => $condition->label());
    }

    protected function publishedField(string $pageName): BooleanField
    {
        return BooleanField::new('published', 'Publié')
            ->renderAsSwitch(Crud::PAGE_INDEX !== $pageName)
            ->setHelp(Crud::PAGE_INDEX === $pageName ? '' : 'Non publié : invisible sur le site.');
    }

    protected function skuField(): TextField
    {
        return TextField::new('sku', 'SKU')
            ->setHelp('Référence interne. Laisser vide pour la générer.')
            ->setRequired(false);
    }

    protected function gtinField(): TextField
    {
        return TextField::new('gtin', 'Code-barres (EAN / UPC)')
            ->setHelp('Les chiffres sous le code-barres, 8 à 14.');
    }

    /**
     * The copy is an unpublished draft with no stock: the volunteer changes what differs
     * (colour, edition…) and publishes it.
     *
     * @param AdminContext<TArticle> $context
     */
    #[AdminRoute(path: '/{entityId}/duplicate', name: 'duplicate', options: ['methods' => ['POST']])]
    public function duplicate(AdminContext $context, Request $request, CommandBusInterface $commandBus): Response
    {
        $article = $this->articleFrom($context);
        $this->denyUnlessValidToken('duplicate', $article, $request);

        /** @var Article $copy */
        $copy = $commandBus->dispatch(new DuplicateArticle((int) $article->getId()));
        $this->addFlash('success', \sprintf('« %s » a été créé à partir de « %s ». Modifiez ce qui change, puis publiez-le.', $copy->getName(), $article->getName()));

        return $this->redirect($this->adminUrl()->setAction(Action::EDIT)->setEntityId($copy->getId())->generateUrl());
    }

    /**
     * Ordered articles are referenced by order lines: deleting them would fail on the
     * foreign key, and the history must be kept anyway. Unpublish them instead.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance->getOrderDetails()->isEmpty()) {
            $this->addFlash('danger', \sprintf('« %s » a déjà été commandé : dépubliez-le plutôt que de le supprimer.', $entityInstance->getName()));

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    /**
     * EasyAdmin renders form actions without a CSRF token: it travels in the URL instead.
     */
    protected function postActionUrl(string $action, Article $article): string
    {
        return $this->adminUrl()
            ->setAction($action)
            ->setEntityId($article->getId())
            ->set('_token', $this->container->get('security.csrf.token_manager')->getToken($action.$article->getId())->getValue())
            ->generateUrl();
    }

    protected function denyUnlessValidToken(string $action, Article $article, Request $request): void
    {
        if (!$this->isCsrfTokenValid($action.$article->getId(), (string) $request->query->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    protected function adminUrl(): AdminUrlGenerator
    {
        return $this->container->get(AdminUrlGenerator::class)->unsetAll()->setController(static::class);
    }

    /**
     * @param AdminContext<TArticle> $context
     */
    private function articleFrom(AdminContext $context): Article
    {
        $article = $context->getEntity()->getInstance();
        \assert($article instanceof Article);

        return $article;
    }
}
