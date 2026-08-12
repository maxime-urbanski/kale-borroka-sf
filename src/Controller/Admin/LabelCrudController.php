<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Label;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractCrudController<Label>
 */
class LabelCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Label::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Label')
            ->setEntityLabelInPlural('Labels')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'country'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('isFriend', 'Ami'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('logo')
            ->setBasePath('/upload/labels')
            ->setUploadDir('public/upload/labels')
            ->onlyOnIndex();
        yield TextField::new('name')->setLabel('Nom du label')->setColumns(6);
        yield UrlField::new('url', 'Site web')->setColumns(6);
        yield TextField::new('country', 'Pays')
            ->setHelp('Code ISO à 2 lettres (FR, US, DE, …).')
            ->hideOnIndex()
            ->setColumns(3);
        yield BooleanField::new('isFriend')->setLabel('Ami')->setColumns(3);
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setColumns(12);
        // The logo column is the Vich fileNameProperty, so the upload widget goes on the
        // non-persisted logoFile property.
        yield Field::new('logoFile', 'Logo')
            ->setFormType(VichImageType::class)
            ->onlyOnForms()
            ->setColumns(6);
        yield AssociationField::new('albums')->setLabel('Albums')->hideOnForm();
    }
}
