<?php

namespace App\Controller\Admin;

use App\Entity\Label;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LabelCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Label::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Label')
            ->setEntityLabelInPlural('Labels')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')->setLabel('Nom du label');
        yield BooleanField::new('isFriend')->setLabel('Ami');
        yield AssociationField::new('albums')->setLabel('Albums')->hideOnForm();
    }
}
