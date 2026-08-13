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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
            ->setLabel('Pochette')
            ->setBasePath('/upload/albums')
            ->setUploadDir('/upload/albums')
            ->setHelp('Aperçu de la pochette. Les visuels se gèrent dans Références › Visuels.')
            ->onlyOnIndex();

        yield FormField::addTab('Œuvre')
            ->setHelp("L'album est l'œuvre elle-même, indépendamment du format sur lequel elle est vendue.");
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setLabel('Artiste')
            ->setHelp("Groupe ou artiste qui signe le disque. S'il n'existe pas encore, créez-le d'abord dans Références › Artistes.")
            ->setColumns(6);
        yield TextField::new('name')
            ->setLabel("Nom de l'album")
            ->setHelp("Titre seul, sans le nom de l'artiste : il est ajouté automatiquement à l'affichage.")
            ->setColumns(6);
        yield AssociationField::new('styles')
            ->setLabel('Styles')
            ->setHelp('Genres musicaux. Servent de filtres dans le catalogue et alimentent le bloc « Dans le même style ».')
            ->setColumns(6)
            ->hideOnIndex();
        yield AssociationField::new('labels')
            ->setLabel('Produit par')
            ->autocomplete()
            ->setHelp('Label(s) ayant sorti le disque. Plusieurs valeurs possibles pour une coproduction.')
            ->setColumns(6);
        yield TextEditorField::new('note')
            ->setLabel('Description')
            ->setHelp("Texte de présentation affiché sur la page de l'album, sous les informations de vente.")
            ->setNumOfRows(8)
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Métadonnées')->onlyOnForms();
        yield ChoiceField::new('productionType', 'Type de production')
            ->setChoices($this->productionTypeChoices())
            ->setHelp('Nature de l\'enregistrement : studio, live, compilation, démo, split ou bootleg.')
            ->setColumns(4);
        yield DateField::new('date_release')
            ->setLabel('Date de sortie')
            ->setHelp("Sortie d'origine de l'œuvre. Une réédition porte sa propre date au niveau de l'édition.")
            ->setColumns(4);
        yield IntegerField::new('recordingYear', "Année d'enregistrement")
            ->setHelp("Année de l'enregistrement, quand elle diffère de la date de sortie.")
            ->hideOnIndex()
            ->setColumns(4);
        yield TextField::new('countryOfOrigin', 'Pays')
            ->setHelp('Pays de production du disque. Code ISO à 2 lettres (FR, US, DE, …).')
            ->hideOnIndex()
            ->setColumns(4);
        yield IntegerField::new('duration', 'Durée totale (s)')
            ->setHelp("Durée totale de l'album, en secondes. Purement informatif.")
            ->hideOnIndex()
            ->setColumns(4);
        yield BooleanField::new('kbrProduction')
            ->setLabel('Production K.B.R')
            ->setHelp("Coché, l'album apparaît dans la page Production et reçoit son bandeau sur les vignettes.")
            ->setColumns(4);
        yield TextField::new('kbrProductionId', 'Référence K.B.R')
            ->setHelp('Numéro de catalogue du label, affiché dans le bandeau des productions maison.')
            ->hideOnIndex()
            ->setColumns(4);

        yield FormField::addTab('Tracklist')->onlyOnForms();
        yield CollectionField::new('tracklists', 'Morceaux')
            ->useEntryCrudForm(SongCrudController::class)
            ->setHelp("Liste des titres, affichée sur la page de l'album. L'ordre suit le numéro de piste.")
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Éditions')->onlyOnForms();
        // Lets a whole record be created in one go: the album, each of its pressings, and
        // the offers under them.
        yield CollectionField::new('editions', 'Éditions')
            ->useEntryCrudForm(EditionCrudController::class)
            ->setHelp('Les différents pressages du disque : LP noir, LP rouge, CD… Chaque édition porte ensuite ses propres offres (prix et stock). Tout se saisit ici, en une seule fois.')
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
