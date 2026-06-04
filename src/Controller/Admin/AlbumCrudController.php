<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Album;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('folder')
            ->setBasePath('/upload/albums')
            ->setUploadDir('/upload/albums')
            ->onlyOnIndex();
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
            ->autocomplete();

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
    }
}
