<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemovesOrphanImagesTrait;
use App\Entity\Artist;
use App\Entity\Merch;
use App\Enum\MerchSize;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Merch>
 */
class MerchCrudController extends AbstractCrudController
{
    use RemovesOrphanImagesTrait;

    /** Sizes created by "Générer les tailles". */
    private const array STANDARD_SIZES = [MerchSize::S, MerchSize::M, MerchSize::L, MerchSize::XL, MerchSize::XXL];

    public static function getEntityFqcn(): string
    {
        return Merch::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Merch')
            ->setEntityLabelInPlural('Merch & textiles')
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateSizes = Action::new('generateSizes', 'Générer les tailles S→XXL', 'fa fa-shirt')
            ->linkToUrl(fn (Merch $merch): string => $this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(self::class)
                ->setAction('generateSizes')
                ->setEntityId($merch->getId())
                ->set('_token', $this->container->get('security.csrf.token_manager')->getToken('generateSizes'.$merch->getId())->getValue())
                ->generateUrl())
            ->renderAsForm();

        return $actions
            ->add(Crud::PAGE_EDIT, $generateSizes)
            ->add(Crud::PAGE_DETAIL, $generateSizes)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * New variants copy the colour and price of the first one and start unpublished with
     * no stock, so nothing goes on sale before it is checked.
     *
     * @param AdminContext<Merch> $context
     */
    #[AdminRoute(path: '/{entityId}/generate-sizes', name: 'generate_sizes', options: ['methods' => ['POST']])]
    public function generateSizes(AdminContext $context, Request $request, EntityManagerInterface $entityManager): Response
    {
        $merch = $context->getEntity()->getInstance();
        \assert($merch instanceof Merch);

        if (!$this->isCsrfTokenValid('generateSizes'.$merch->getId(), (string) $request->query->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $color = ($merch->getVariants()->first() ?: null)?->getColor();
        $added = $merch->addMissingSizes(self::STANDARD_SIZES, $color);
        $entityManager->flush();

        $this->addFlash('success', 0 === $added
            ? 'Toutes les tailles existent déjà.'
            : \sprintf('%d taille(s) ajoutée(s), sans stock et non publiées.', $added));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($merch->getId())
            ->generateUrl());
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $removed = $this->removedImages($entityInstance->getImages());
        parent::updateEntity($entityManager, $entityInstance);
        $this->deleteOrphanImages($entityManager, $removed);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [ImageRepository::class]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Produit', 'fa fa-shirt');
        yield TextField::new('name', 'Nom')
            ->setHelp('« T-shirt Quartier Maudit » : les tailles et couleurs se gèrent dans l\'onglet suivant.')
            ->setColumns(6);
        yield AssociationField::new('artist', 'Groupe')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Artist => (new Artist())->setName($name))
            ->setHelp('Vide pour le merch du label.')
            ->setColumns(3);
        yield AssociationField::new('category', 'Catégorie')
            ->setColumns(3);
        yield TextareaField::new('description', 'Description')
            ->setColumns(9)
            ->hideOnIndex();
        yield BooleanField::new('published', 'Publié')
            ->setHelp('Chaque taille a aussi sa propre publication.')
            ->setColumns(3);

        yield FormField::addTab('Tailles & stock', 'fa fa-ruler')
            ->setBadge(static fn (?Merch $merch): ?int => $merch?->getVariants()->count() ?: null);
        yield CollectionField::new('variants', 'Tailles & couleurs')
            ->setLabel(false)
            ->setHelp('Une ligne par taille et couleur, chacune avec son stock. « Générer les tailles S→XXL » crée d\'un coup les tailles manquantes.')
            ->useEntryCrudForm(MerchVariantCrudController::class, MerchVariantCrudController::PAGE_IN_MERCH_NEW, MerchVariantCrudController::PAGE_IN_MERCH_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12);

        yield FormField::addTab('Visuels', 'fa fa-image')
            ->setBadge(static fn (?Merch $merch): ?int => $merch?->getImages()->count() ?: null)
            ->onlyOnForms();
        yield CollectionField::new('images')
            ->setLabel(false)
            ->setHelp('La plus petite position sert de visuel principal, pour toutes les tailles.')
            ->useEntryCrudForm(ImageCrudController::class, ImageCrudController::PAGE_EMBEDDED_NEW, ImageCrudController::PAGE_EMBEDDED_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->onlyOnForms();
    }
}
