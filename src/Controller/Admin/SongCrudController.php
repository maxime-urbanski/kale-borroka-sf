<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Song;
use App\Form\Type\DurationType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
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
            ->setEntityLabelInPlural('Morceaux')
            ->setEntityLabelInSingular('Morceau')
        ;
    }

    /**
     * One row per track: it is typed in bulk from the album form.
     */
    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('track', 'N°')
            ->setColumns(1);
        yield TextField::new('position', 'Face')
            ->setHelp('A1, B3… vide sur un CD')
            ->setColumns(2);
        yield TextField::new('name', 'Titre')
            ->setColumns(6);
        yield Field::new('duration', 'Durée')
            ->setFormType(DurationType::class)
            ->formatValue(static fn (?int $seconds): string => null === $seconds ? '' : DurationType::format($seconds))
            ->setColumns(3);
    }
}
