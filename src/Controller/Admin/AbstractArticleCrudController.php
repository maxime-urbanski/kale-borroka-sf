<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Catalog\Command\DuplicateArticle;
use App\Entity\Article;
use App\Enum\ItemCondition;
use App\Messenger\CommandBusInterface;
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
 * What every sellable article shares in the back office: thumbnail, stock badge, price,
 * the "Dupliquer" action, and protection against deleting an article that has been ordered.
 *
 * @template TArticle of Article
 *
 * @extends AbstractCrudController<TArticle>
 */
abstract class AbstractArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ShopSettingsProviderInterface $shopSettingsProvider,
    ) {
    }

    /**
     * Fields specific to the subclass, shown between the name and the SKU.
     *
     * @return iterable<FieldInterface>
     */
    abstract protected function configureSpecificFields(string $pageName): iterable;

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
        yield ImageField::new('coverImageName', 'Visuel')
            ->setBasePath('/upload/albums')
            ->setSortable(false)
            ->onlyOnIndex();
        yield TextField::new('name', 'Nom')
            ->setColumns(6);

        yield from $this->configureSpecificFields($pageName);

        yield ChoiceField::new('itemCondition', 'État')
            ->setChoices(ItemCondition::cases())
            ->setFormTypeOption('choice_label', static fn (ItemCondition $condition): string => $condition->label())
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('sku', 'SKU')
            ->setHelp('Laisser vide pour le générer.')
            ->setRequired(false)
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('gtin', 'EAN / UPC')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('stock', 'Stock')
            ->setTemplatePath('admin/field/stock_badge.html.twig')
            // Read once per request here rather than once per row in the template.
            ->setCustomOption('lowStockThreshold', $this->shopSettingsProvider->get()->getLowStockThreshold())
            ->setColumns(3);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setColumns(3);
        yield BooleanField::new('published', 'Publié')
            ->renderAsSwitch(Crud::PAGE_INDEX !== $pageName);
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
