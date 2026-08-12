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
            ->setColumns(2)
        ;
        yield TextField::new('name')
            ->setLabel('Titre')
            ->setColumns(6)
        ;
        yield TextField::new('side', 'Face')
            ->setHelp('A, B, C… — sans objet pour un CD.')
            ->setColumns(2)
        ;
        yield IntegerField::new('duration', 'Durée (s)')
            ->setColumns(2)
        ;
        yield TextField::new('isrc', 'ISRC')
            ->hideOnIndex()
            ->setColumns(4)
        ;
    }
}
