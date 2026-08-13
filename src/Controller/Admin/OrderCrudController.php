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
        yield TextField::new('reference', 'Référence')
            ->setHelp('Référence générée à la commande. Sert d\'identifiant dans les échanges avec le client.')
            ->setColumns(4);
        yield DateTimeField::new('created_at', 'Passée le')
            ->setTimezone('Europe/Paris')
            ->setHelp('Date de validation de la commande.')
            ->setColumns(4);
        yield TextField::new('status', 'Statut')
            ->setHelp("État d'avancement de la commande. C'est le seul champ que vous ayez normalement à modifier.")
            ->setColumns(4);
        yield AssociationField::new('buyer', 'Client')
            ->setHelp('Compte ayant passé la commande.')
            ->setColumns(4);
        yield MoneyField::new('totalPrice', 'Total')
            ->setCurrency('EUR')
            ->setHelp('Montant figé au moment de la commande : il ne suit pas les changements de prix ultérieurs.')
            ->setColumns(4);
        yield AssociationField::new('payment', 'Paiement')
            ->setHelp('Moyen de paiement retenu par le client.')
            ->setColumns(4);
        yield AssociationField::new('delivery', 'Livraison')
            ->setHelp('Mode d\'expédition retenu par le client.')
            ->setColumns(4);
        yield AssociationField::new('address', 'Adresse')
            ->setHelp('Adresse de livraison telle que saisie lors de la commande.')
            ->hideOnIndex()
            ->setColumns(6);
        yield AssociationField::new('orderDetails', 'Lignes')
            ->setHelp('Articles commandés, avec le prix et la quantité figés à la commande.')
            ->onlyOnDetail();
    }
}
