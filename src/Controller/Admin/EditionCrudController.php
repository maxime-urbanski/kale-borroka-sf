<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Edition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * The pressing: what makes a red vinyl different from a black one.
 *
 * @extends AbstractCrudController<Edition>
 */
class EditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Edition::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Édition')
            ->setEntityLabelInPlural('Éditions')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'color', 'catalogNumber', 'album.name'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('album', 'Album')
            ->autocomplete()
            ->setColumns(6);
        yield AssociationField::new('support', 'Support')
            ->setColumns(3);
        yield TextField::new('name', 'Nom de l\'édition')
            ->setHelp('Ce que le sélecteur affiche : « LP », « LP vinyle rouge », « CD digipack ».')
            ->setColumns(6);
        yield TextField::new('color', 'Couleur')
            ->setColumns(3);
        yield TextField::new('editionLabel', 'Mention d\'édition')
            ->setHelp('« Collector », « Édition limitée », « Réédition 2024 ».')
            ->hideOnIndex()
            ->setColumns(3);
        yield TextField::new('catalogNumber', 'N° de catalogue')
            ->hideOnIndex()
            ->setColumns(3);
        yield IntegerField::new('pressingRun', 'Tirage')
            ->setHelp('Nombre d\'exemplaires pressés, pour les tirages limités.')
            ->hideOnIndex()
            ->setColumns(3);
        yield DateField::new('releaseDate', 'Date de sortie')
            ->hideOnIndex()
            ->setColumns(3);
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setColumns(12);
        yield AssociationField::new('images', 'Visuels propres à l\'édition')
            ->setHelp('Laisser vide pour réutiliser la pochette de l\'album.')
            ->hideOnIndex()
            ->setColumns(12);
        yield CollectionField::new('articles', 'Offres')
            ->useEntryCrudForm(ArticleCrudController::class)
            ->hideOnIndex()
            ->setColumns(12);
    }
}
