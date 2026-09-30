<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Style;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Style>
 */
class StyleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Style::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Styles')
            ->setEntityLabelInSingular('Style');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')
            ->setLabel('Style')
            ->setHelp('Punk, Oi!, Street punk… Un style peut aussi se créer depuis le formulaire album.')
            ->setColumns(6);
        yield AssociationField::new('albums', 'Albums')
            ->hideOnForm();
    }
}
