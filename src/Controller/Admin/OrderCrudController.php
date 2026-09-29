<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\OrderTransition;
use App\Enum\PaymentStatus;
use App\Messenger\CommandBusInterface;
use App\Order\Command\ApplyOrderTransition;
use App\Order\Exception\InsufficientStockException;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Orders are created by customers and never edited through a form: their status only
 * moves through the `order` workflow, one button per transition the order allows.
 *
 * @extends AbstractCrudController<Order>
 */
class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        #[Target('order')]
        private readonly WorkflowInterface $orderWorkflow,
    ) {
    }

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
            ->setSearchFields(['reference', 'buyer.email', 'buyer.lastname'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);

        foreach (OrderTransition::cases() as $transition) {
            $action = Action::new('transition_'.$transition->value, $transition->label())
                ->linkToUrl(fn (Order $order): string => $this->transitionUrl($order, $transition))
                ->displayIf(fn (Order $order): bool => $this->orderWorkflow->can($order, $transition->value))
                ->renderAsForm();

            if (\in_array($transition, [OrderTransition::CANCEL, OrderTransition::REFUND], true)) {
                $action->asDangerAction();
            }

            $actions->add(Crud::PAGE_DETAIL, $action);
        }

        return $actions;
    }

    /**
     * @param AdminContext<Order> $context
     */
    #[AdminRoute(
        path: '/{entityId}/transition/{transition}',
        name: 'transition',
        options: ['methods' => ['POST'], 'requirements' => ['transition' => 'pay|prepare|ship|deliver|cancel|refund']],
    )]
    public function applyTransition(AdminContext $context, Request $request, CommandBusInterface $commandBus, string $transition): Response
    {
        $order = $context->getEntity()->getInstance();
        \assert($order instanceof Order);
        $orderTransition = OrderTransition::from($transition);

        if (!$this->isCsrfTokenValid($this->tokenId($order, $orderTransition), (string) $request->query->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $commandBus->dispatch(new ApplyOrderTransition((int) $order->getId(), $orderTransition));
            $this->addFlash('success', \sprintf('Commande %s : %s.', $order->getReference(), mb_strtolower($order->getStatus()->label())));
        } catch (InsufficientStockException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        } catch (NotEnabledTransitionException) {
            $this->addFlash('danger', \sprintf('« %s » n\'est pas possible depuis le statut « %s ».', $orderTransition->label(), $order->getStatus()->label()));
        }

        return $this->redirect($this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(self::labelledChoices(OrderStatus::cases())))
            ->add(ChoiceFilter::new('paymentStatus', 'Paiement')->setChoices(self::labelledChoices(PaymentStatus::cases())))
            ->add(DateTimeFilter::new('created_at', 'Passée le'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence');
        yield DateTimeField::new('created_at', 'Passée le');
        yield AssociationField::new('buyer', 'Client');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(OrderStatus::cases())
            ->setFormTypeOption('choice_label', static fn (OrderStatus $status): string => $status->label())
            ->renderAsBadges([
                OrderStatus::PENDING->value => 'warning',
                OrderStatus::PAID->value => 'info',
                OrderStatus::PREPARING->value => 'info',
                OrderStatus::SHIPPED->value => 'primary',
                OrderStatus::DELIVERED->value => 'success',
                OrderStatus::CANCELLED->value => 'secondary',
                OrderStatus::REFUNDED->value => 'secondary',
            ]);
        yield ChoiceField::new('paymentStatus', 'Paiement')
            ->setChoices(PaymentStatus::cases())
            ->setFormTypeOption('choice_label', static fn (PaymentStatus $status): string => $status->label());
        yield MoneyField::new('totalPrice', 'Total')
            ->setCurrency('EUR');
        yield AssociationField::new('delivery', 'Livraison')
            ->hideOnIndex();
        yield AssociationField::new('payment', 'Mode de paiement')
            ->hideOnIndex();
        yield AssociationField::new('address', 'Adresse')
            ->hideOnIndex();
        yield DateTimeField::new('paidAt', 'Payée le')
            ->hideOnIndex();
        yield CollectionField::new('orderDetails', 'Articles')
            ->setTemplatePath('admin/field/order_lines.html.twig')
            ->onlyOnDetail();
    }

    /**
     * @param array<OrderStatus|PaymentStatus> $cases
     *
     * @return array<string, string>
     */
    private static function labelledChoices(array $cases): array
    {
        $choices = [];

        foreach ($cases as $case) {
            $choices[$case->label()] = $case->value;
        }

        return $choices;
    }

    /**
     * EasyAdmin renders form actions without a CSRF token: it travels in the URL instead.
     */
    private function transitionUrl(Order $order, OrderTransition $transition): string
    {
        return $this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(self::class)
            ->setAction('applyTransition')
            ->setEntityId($order->getId())
            ->set('transition', $transition->value)
            ->set('_token', $this->container->get('security.csrf.token_manager')->getToken($this->tokenId($order, $transition))->getValue())
            ->generateUrl();
    }

    private function tokenId(Order $order, OrderTransition $transition): string
    {
        return \sprintf('order_%s_%d', $transition->value, $order->getId());
    }
}
