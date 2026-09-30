<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Category>
 */
class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')->setColumns(6);
        yield AssociationField::new('parent', 'Catégorie parente')
            ->setHelp('Vide pour une catégorie principale : « Textile », puis « T-shirts » dedans.')
            ->setColumns(6);
    }
}
