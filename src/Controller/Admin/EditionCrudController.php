<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Edition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

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

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('support', 'Support'))
            ->add(EntityFilter::new('album', 'Album'))
            ->add(TextFilter::new('color', 'Couleur'))
            ->add(TextFilter::new('editionLabel', 'Mention d\'édition'))
            ->add(NumericFilter::new('pressingRun', 'Tirage'));
    }

    /**
     * Rendered two ways: as a page of its own, and as a row inside the album form. Nested,
     * the album is already known and tabs inside a collapsible row read as clutter, so the
     * form drops to fieldsets and short helps.
     */
    public function configureFields(string $pageName): iterable
    {
        $embedded = $this->isEmbeddedInAlbum();

        if (!$embedded) {
            yield FormField::addFieldset('Pressage')
                ->setHelp("Une édition est un pressage précis d'un album. Deux vinyles de couleurs différentes du même disque font deux éditions ; c'est entre elles que bascule le sélecteur sur la page publique.")
                ->onlyOnForms();
            yield AssociationField::new('album', 'Album')
                ->autocomplete()
                ->setHelp('Disque dont cette édition est un pressage.')
                ->setColumns(6);
        }

        yield TextField::new('name', 'Nom de l\'édition')
            ->setHelp('Ce qu\'affiche le sélecteur : « LP », « LP vinyle rouge », « CD digipack ».')
            ->setColumns(6);
        yield AssociationField::new('support', 'Support')
            ->setHelp($embedded ? 'Format physique.' : "Format physique. Détermine la rubrique du catalogue où l'édition apparaît.")
            ->setColumns(3);
        yield TextField::new('color', 'Couleur')
            ->setHelp('Couleur du vinyle. Vide pour un CD ou un pressage noir.')
            ->setColumns(3);

        yield TextField::new('editionLabel', 'Mention d\'édition')
            ->setHelp('« Collector », « Édition limitée »… Affiché en badge.')
            ->hideOnIndex()
            ->setColumns(4);
        yield TextField::new('catalogNumber', 'N° de catalogue')
            ->setHelp('Référence du label pour ce pressage.')
            ->hideOnIndex()
            ->setColumns(4);
        yield IntegerField::new('pressingRun', 'Tirage')
            ->setHelp('Nombre d\'exemplaires pressés, pour un tirage limité.')
            ->hideOnIndex()
            ->setColumns(2);
        yield DateField::new('releaseDate', 'Date de sortie')
            ->setHelp('Si différente de celle de l\'album.')
            ->hideOnIndex()
            ->setColumns(2);
        yield TextareaField::new('description', 'Description')
            ->setHelp('Précisions propres à ce pressage : encart, poster inclus, gravure…')
            ->setFormTypeOption('attr', ['rows' => 3])
            ->hideOnIndex()
            ->setColumns(12);

        yield FormField::addFieldset('Visuels')->onlyOnForms();
        yield AssociationField::new('images', 'Visuels propres à l\'édition')
            ->setHelp("Photos de ce pressage précis. Vide : la pochette de l'album est reprise.")
            ->hideOnIndex()
            ->setColumns(6);

        yield FormField::addFieldset('Offres')->onlyOnForms();
        yield CollectionField::new('articles', 'Offres')
            ->useEntryCrudForm(ArticleCrudController::class)
            ->setHelp("Prix et stock. Plusieurs offres permettent de vendre le même pressage neuf et d'occasion. Sans offre, l'édition n'est pas achetable.")
            ->hideOnIndex()
            ->setColumns(12);
    }

    /**
     * True when this form is rendered as a row of the album's Éditions collection rather
     * than as its own admin page.
     *
     * Detection goes through the controller owning the admin context rather than its
     * entity: EasyAdmin's generics pin getEntity()->getFqcn() to Edition, so comparing it
     * to Album is a contradiction as far as static analysis is concerned.
     */
    private function isEmbeddedInAlbum(): bool
    {
        $context = $this->getContext();

        return null !== $context && self::class !== $context->getCrud()?->getControllerFqcn();
    }
}
