<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Transporter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
            ->setEntityLabelInPlural('Modes de livraison')
            ->setDefaultSort(['price' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->setHelp('Libellé proposé au client au moment de la commande (Colissimo, Mondial Relay…).')
            ->setColumns(6);
        yield MoneyField::new('price', 'Tarif')
            ->setCurrency('EUR')
            ->setHelp('Frais de port facturés pour ce mode de livraison.')
            ->setColumns(3);
        yield TextEditorField::new('description', 'Description')
            ->setHelp('Délai indicatif, zone desservie, conditions… Affiché sous le libellé lors du choix.')
            ->setColumns(12);
    }
}
