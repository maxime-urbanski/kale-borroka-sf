<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * @extends AbstractCrudController<Order>
 */
class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['created_at' => 'DESC'])
            ->setSearchFields(['reference', 'buyer.email'])
            ->showEntityActionsInlined();
    }

    /**
     * Orders are produced by the checkout flow; creating one by hand would bypass stock
     * and price snapshotting, so only reading and status edits make sense here.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('status', 'Statut'))
            ->add(EntityFilter::new('payment', 'Paiement'))
            ->add(EntityFilter::new('delivery', 'Livraison'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence')->setColumns(4);
        yield DateTimeField::new('created_at', 'Passée le')
            ->setTimezone('Europe/Paris')
            ->setColumns(4);
        yield TextField::new('status', 'Statut')->setColumns(4);
        yield AssociationField::new('buyer', 'Client')->setColumns(4);
        yield MoneyField::new('totalPrice', 'Total')
            ->setCurrency('EUR')
            ->setColumns(4);
        yield AssociationField::new('payment', 'Paiement')->setColumns(4);
        yield AssociationField::new('delivery', 'Livraison')->setColumns(4);
        yield AssociationField::new('address', 'Adresse')->hideOnIndex()->setColumns(6);
        yield AssociationField::new('orderDetails', 'Lignes')->onlyOnDetail();
    }
}
