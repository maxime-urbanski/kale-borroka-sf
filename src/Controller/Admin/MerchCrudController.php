<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Merch;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Merch>
 */
class MerchCrudController extends AbstractCrudController
{
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

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->setColumns(6);
        yield AssociationField::new('artist', 'Groupe')
            ->autocomplete()
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
