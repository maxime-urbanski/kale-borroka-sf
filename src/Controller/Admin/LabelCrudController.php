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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
            ->setHelp('Aperçu du logo enregistré.')
            ->onlyOnIndex();
        yield TextField::new('name')
            ->setLabel('Nom du label')
            ->setHelp('Nom du label. Sert de filtre dans le catalogue via « Produit par ».')
            ->setColumns(6);
        yield UrlField::new('url', 'Site web')
            ->setHelp('Adresse du site officiel du label, avec le https://.')
            ->setColumns(6);
        yield TextField::new('country', 'Pays')
            ->setHelp('Pays où le label est établi. Code ISO à 2 lettres (FR, US, DE, …).')
            ->hideOnIndex()
            ->setColumns(3);
        yield BooleanField::new('isFriend')
            ->setLabel('Ami')
            ->setHelp('Marque les labels avec lesquels vous êtes en lien, pour les distinguer du reste de la distro.')
            ->setColumns(3);
        yield TextEditorField::new('description', 'Description')
            ->setHelp('Présentation du label.')
            ->hideOnIndex()
            ->setColumns(12);
        // The logo column is the Vich fileNameProperty, so the upload widget goes on the
        // non-persisted logoFile property.
        yield Field::new('logoFile', 'Logo')
            ->setFormType(VichImageType::class)
            ->setHelp('Logo du label. Remplace le précédent à chaque envoi.')
            ->onlyOnForms()
            ->setColumns(6);
        yield AssociationField::new('albums')
            ->setLabel('Albums')
            ->setHelp('Albums sortis sur ce label. Se renseigne depuis la fiche album.')
            ->hideOnForm();
    }
}
