<?php

namespace App\Controller\Admin;

use App\Entity\Song;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
        yield TextField::new('name')->setLabel('Titre de la chanson');
        yield TextField::new('track')->setLabel('track');
    }
}
