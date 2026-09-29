<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Entity\Merch;
use App\Enum\MerchSize;
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

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->setColumns(6);
        yield AssociationField::new('artist', 'Groupe')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Artist => (new Artist())->setName($name))
            ->setColumns(3);
        yield AssociationField::new('category', 'Catégorie')
            ->setColumns(3);
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
        yield CollectionField::new('variants', 'Tailles & couleurs')
            ->useEntryCrudForm(MerchVariantCrudController::class)
            ->setFormTypeOption('by_reference', false);
        yield BooleanField::new('published', 'Publié');
    }
}
