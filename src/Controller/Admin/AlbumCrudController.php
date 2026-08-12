<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Enum\AlbumProductionType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

/**
 * @extends AbstractCrudController<Album>
 */
class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Albums')
            ->setEntityLabelInSingular('Album')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'artist.name', 'kbrProductionId'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('artist', 'Artiste'))
            ->add(EntityFilter::new('styles', 'Styles'))
            ->add(EntityFilter::new('labels', 'Labels'))
            ->add(BooleanFilter::new('kbrProduction', 'Production K.B.R'))
            ->add(ChoiceFilter::new('productionType', 'Type')
                ->setChoices($this->productionTypeChoices()));
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('folder')
            ->setBasePath('/upload/albums')
            ->setUploadDir('/upload/albums')
            ->onlyOnIndex();

        yield FormField::addTab('Œuvre');
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setLabel('Artiste')
            ->setColumns(6);
        yield TextField::new('name')
            ->setLabel("Nom de l'album")
            ->setColumns(6);
        yield AssociationField::new('styles')
            ->setColumns(6)
            ->hideOnIndex();
        yield AssociationField::new('labels')
            ->setLabel('Produit par')
            ->autocomplete()
            ->setColumns(6);
        yield TextareaField::new('note')
            ->setLabel('Description')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Métadonnées')->onlyOnForms();
        yield ChoiceField::new('productionType', 'Type de production')
            ->setChoices($this->productionTypeChoices())
            ->setColumns(4);
        yield DateField::new('date_release')
            ->setLabel('Date de sortie')
            ->setColumns(4);
        yield IntegerField::new('recordingYear', "Année d'enregistrement")
            ->hideOnIndex()
            ->setColumns(4);
        yield TextField::new('countryOfOrigin', 'Pays')
            ->setHelp('Code ISO à 2 lettres (FR, US, DE, …).')
            ->hideOnIndex()
            ->setColumns(4);
        yield IntegerField::new('duration', 'Durée totale (s)')
            ->hideOnIndex()
            ->setColumns(4);
        yield BooleanField::new('kbrProduction')
            ->setLabel('Production K.B.R')
            ->setColumns(4);
        yield TextField::new('kbrProductionId', 'Référence K.B.R')
            ->hideOnIndex()
            ->setColumns(4);

        yield FormField::addTab('Tracklist')->onlyOnForms();
        yield CollectionField::new('tracklists', 'Morceaux')
            ->useEntryCrudForm(SongCrudController::class)
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Éditions')->onlyOnForms();
        // Lets a whole record be created in one go: the album, each of its pressings, and
        // the offers under them.
        yield CollectionField::new('editions', 'Éditions')
            ->useEntryCrudForm(EditionCrudController::class)
            ->hideOnIndex()
            ->setColumns(12);
    }

    /**
     * @return array<string, AlbumProductionType>
     */
    private function productionTypeChoices(): array
    {
        $choices = [];

        foreach (AlbumProductionType::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }
}
