<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Transporter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Transporter>
 */
class TransporterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transporter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mode de livraison')
            ->setEntityLabelInPlural('Modes de livraison');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')->setColumns(8);
        yield MoneyField::new('price', 'Frais de port')
            ->setCurrency('EUR')
            ->setColumns(4);
        yield TextareaField::new('description', 'Description')
            ->setHelp('Montrée au client au moment de choisir : délai, suivi, point relais…')
            ->setColumns(12);
    }
}
