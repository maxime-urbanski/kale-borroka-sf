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
            ->setLabel('Artiste');
        yield TextField::new('name')
            ->setLabel("Nom de l'album");
        yield BooleanField::new('kbrProduction')->setLabel('Prodution K.B.R');
        yield TextareaField::new('note')
            ->setLabel('Information')
            ->hideOnIndex();
        // yield ImageField::new('folder')->setLabel('Pochette')->setUploadDir(__DIR__ . '/public/images/album')->hideOnIndex();
        yield DateField::new('date_release')->setLabel('Date de sortie');
        yield AssociationField::new('labels')
            ->setLabel('Produit par')
            ->autocomplete();
        yield CollectionField::new('tracklists')
            ->useEntryCrudForm(SongCrudController::class)
            ->hideOnIndex();
        yield AssociationField::new('styles')
            ->hideOnIndex();
    }
}
