<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Read-only: orders are created by customers and their status only changes through the
 * `order` workflow (ApplyOrderTransition), never through a form.
 *
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
            ->setSearchFields(['reference', 'buyer.email', 'buyer.lastname']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence');
        yield DateTimeField::new('created_at', 'Passée le');
        yield AssociationField::new('buyer', 'Client');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(OrderStatus::cases())
            ->setFormTypeOption('choice_label', static fn (OrderStatus $status): string => $status->label());
        yield ChoiceField::new('paymentStatus', 'Paiement')
            ->setChoices(PaymentStatus::cases())
            ->setFormTypeOption('choice_label', static fn (PaymentStatus $status): string => $status->label());
        yield MoneyField::new('totalPrice', 'Total')
            ->setCurrency('EUR');
        yield AssociationField::new('delivery', 'Livraison')
            ->hideOnIndex();
        yield AssociationField::new('address', 'Adresse')
            ->hideOnIndex();
        yield DateTimeField::new('paidAt', 'Payée le')
            ->hideOnIndex();
    }
}
