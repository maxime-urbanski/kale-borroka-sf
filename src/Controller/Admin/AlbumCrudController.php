<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Enum\AlbumReleaseType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

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
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $addRelease = Action::new('addRelease', 'Pressage', 'fa fa-plus')
            ->linkToUrl(fn (Album $album): string => $this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(ReleaseCrudController::class)
                ->setAction(Action::NEW)
                ->set('album', $album->getId())
                ->generateUrl());

        return $actions
            ->add(Crud::PAGE_INDEX, $addRelease)
            ->add(Crud::PAGE_EDIT, $addRelease)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('coverImageName')
            ->setLabel('Visuel')
            ->setBasePath('/upload/albums')
            ->setSortable(false)
            ->onlyOnIndex();
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Artist => (new Artist())->setName($name))
            ->setLabel('Artiste')
            ->setHelp('Tapez le nom : un groupe absent de la liste est créé à l\'enregistrement.')
            ->setColumns(6);
        yield TextField::new('name')
            ->setLabel("Nom de l'album")
            ->setColumns(6);
        yield ChoiceField::new('releaseType')
            ->setLabel('Type')
            ->setChoices(AlbumReleaseType::cases())
            ->setFormTypeOption('choice_label', static fn (AlbumReleaseType $type): string => $type->label())
            ->setHelp('Un 12" d\'un EP est rangé dans le rayon EP.')
            ->setColumns(3);
        yield AssociationField::new('styles')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Style => (new Style())->setName($name))
            ->setColumns(6)
            ->hideOnIndex();
        yield AssociationField::new('labels')
            ->setLabel('Produit par')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Label => (new Label())->setName($name))
            ->setColumns(6);

        yield TextareaField::new('note')
            ->setLabel('Information')
            ->setColumns(6)
            ->hideOnIndex();
        yield CollectionField::new('tracklists')
            ->useEntryCrudForm(SongCrudController::class)
            ->setColumns(6)
            ->hideOnIndex();
        yield DateField::new('date_release')
            ->setLabel('Date de sortie');

        yield BooleanField::new('kbrProduction')
            ->setLabel('Prodution K.B.R')
            ->setColumns(3);

        yield CollectionField::new('releases')
            ->setLabel('Pressages')
            ->setHelp('Chaque pressage (LP noir, LP rouge, CD…) est un article en vente avec son propre stock.')
            ->useEntryCrudForm(ReleaseCrudController::class, ReleaseCrudController::PAGE_IN_ALBUM_NEW, ReleaseCrudController::PAGE_IN_ALBUM_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->onlyOnForms();
        yield AssociationField::new('releases')
            ->setLabel('Pressages')
            ->onlyOnIndex();
    }
}
