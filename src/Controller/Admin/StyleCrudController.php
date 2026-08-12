<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Style;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
            ->setEntityLabelInSingular('Style')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')->setLabel('Style')->setColumns(6);
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setColumns(12);
    }
}
