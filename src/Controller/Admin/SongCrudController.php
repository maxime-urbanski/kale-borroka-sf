<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Song;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Song>
 */
class SongCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Song::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Tracklists')
            ->setEntityLabelInSingular('Tracklist')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('track')
            ->setLabel('N°')
            ->setHelp("Numéro de piste. Détermine l'ordre d'affichage de la tracklist.")
            ->setColumns(2)
        ;
        yield TextField::new('name')
            ->setLabel('Titre')
            ->setHelp('Titre du morceau.')
            ->setColumns(6)
        ;
        yield TextField::new('side', 'Face')
            ->setHelp('Face du disque : A, B, C… Sans objet pour un CD ou une K7.')
            ->setColumns(4)
        ;
        yield IntegerField::new('duration', 'Durée (s)')
            ->setHelp('Durée du morceau en secondes — 185 pour 3 min 05. Affichée en minutes sur le site.')
            ->setColumns(4)
        ;
        yield TextField::new('isrc', 'ISRC')
            ->setHelp('Code international du morceau, 12 caractères. Rarement renseigné sur les sorties autoproduites.')
            ->hideOnIndex()
            ->setColumns(4)
        ;
    }
}
