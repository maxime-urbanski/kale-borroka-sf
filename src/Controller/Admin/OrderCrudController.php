<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Data\OrderShippingDto;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\OrderTransition;
use App\Enum\PaymentStatus;
use App\Form\OrderShippingFormType;
use App\Messenger\CommandBusInterface;
use App\Order\Command\ApplyOrderTransition;
use App\Order\Command\SetOrderShipping;
use App\Order\Exception\InsufficientStockException;
use App\Order\Exception\InvalidOrderException;
use App\Service\ShopSettingsProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
        private readonly ShopSettingsProviderInterface $shopSettingsProvider,
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
            ->setSearchFields(['reference', 'buyer.email', 'buyer.lastname', 'buyer.firstname'])
            // The buyer's message above the tabs.
            ->overrideTemplate('crud/detail', 'admin/order_detail.html.twig')
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // The checkout offers no delivery mode: the label sets it before the payment link.
            ->add(Crud::PAGE_DETAIL, Action::new('shipping', 'Fixer le port', 'fa fa-truck')
                ->linkToCrudAction('shipping')
                ->displayIf(static fn (Order $order): bool => OrderStatus::PENDING === $order->getStatus()));

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

        return $this->redirect($this->detailUrl($order));
    }

    private function detailUrl(Order $order): string
    {
        return $this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl();
    }

    private static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    /**
     * Says why « Marquer payée » is missing: the workflow guard wants the shipping set first.
     *
     * @param AdminContext<Order> $context
     */
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $order = $context->getEntity()->getInstance();

        if ($order instanceof Order && OrderStatus::PENDING === $order->getStatus() && null === $order->getDelivery()) {
            $this->addFlash('warning', 'Fixe le port avant d\'envoyer le lien de paiement : la commande ne peut pas être marquée payée sans lui.');
        }

        return parent::detail($context);
    }

    /**
     * @param AdminContext<Order> $context
     */
    #[AdminRoute(path: '/{entityId}/shipping', name: 'shipping', options: ['methods' => ['GET', 'POST']])]
    public function shipping(AdminContext $context, Request $request, CommandBusInterface $commandBus): Response
    {
        $order = $context->getEntity()->getInstance();
        \assert($order instanceof Order);

        if (OrderStatus::PENDING !== $order->getStatus()) {
            $this->addFlash('danger', \sprintf('Le port ne se change plus : la commande est « %s ».', mb_strtolower($order->getStatus()->label())));

            return $this->redirect($this->detailUrl($order));
        }

        $data = new OrderShippingDto();
        $data->transporter = $order->getDelivery();
        $data->shippingPrice = null !== $order->getDelivery() ? $order->getShippingPrice() : null;
        $form = $this->createForm(OrderShippingFormType::class, $data, ['attr' => ['novalidate' => 'novalidate']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $commandBus->dispatch(new SetOrderShipping((int) $order->getId(), (int) $data->transporter?->getId(), (int) $data->shippingPrice));
                $this->addFlash('success', \sprintf('Port de %s enregistré pour la commande %s.', self::euros((int) $data->shippingPrice), $order->getReference()));
            } catch (InvalidOrderException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }

            return $this->redirect($this->detailUrl($order));
        }

        $lines = 0;
        foreach ($order->getOrderDetails() as $line) {
            $lines += (int) $line->getPrice();
        }

        return $this->render('admin/order_shipping.html.twig', [
            'order' => $order,
            'lines' => $lines,
            'form' => $form,
            'back' => $this->detailUrl($order),
        ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(self::labelledChoices(OrderStatus::cases())))
            ->add(ChoiceFilter::new('paymentStatus', 'Paiement')->setChoices(self::labelledChoices(PaymentStatus::cases())))
            ->add(DateTimeFilter::new('created_at', 'Passée le'));
    }

    /**
     * Detail page in three tabs: the order and its status, what was bought, where it goes.
     * The list only shows what is needed to pick an order.
     */
    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Commande', 'fa fa-receipt');
        yield FormField::addColumn(6);
        yield TextField::new('reference', 'Référence');
        yield DateTimeField::new('created_at', 'Passée le');
        yield AssociationField::new('buyer', 'Client')
            ->setTemplatePath('admin/field/order_buyer.html.twig')
            ->setCustomOption('withPhone', Crud::PAGE_DETAIL === $pageName);
        yield MoneyField::new('totalPrice', 'Total')
            ->setCurrency('EUR');
        yield MoneyField::new('shippingPrice', 'Dont livraison')
            ->setCurrency('EUR')
            ->hideOnIndex();
        yield FormField::addColumn(6);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(OrderStatus::cases())
            ->setFormTypeOption('choice_label', static fn (OrderStatus $status): string => $status->label())
            ->setTemplatePath('admin/field/enum_badge.html.twig')
            ->setCustomOption('badges', [
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
            ->setFormTypeOption('choice_label', static fn (PaymentStatus $status): string => $status->label())
            ->setTemplatePath('admin/field/enum_badge.html.twig')
            ->setCustomOption('badges', [
                PaymentStatus::AWAITING->value => 'warning',
                PaymentStatus::PAID->value => 'success',
                PaymentStatus::FAILED->value => 'danger',
                PaymentStatus::REFUNDED->value => 'secondary',
            ]);
        yield AssociationField::new('payment', 'Mode de paiement')
            ->hideOnIndex();
        yield DateTimeField::new('paidAt', 'Payée le')
            ->hideOnIndex();

        yield FormField::addTab('Articles', 'fa fa-box')
            ->setBadge(static fn (?Order $order): ?int => $order?->getOrderDetails()->count() ?: null);
        yield CollectionField::new('orderDetails', false)
            ->setTemplatePath('admin/field/order_lines.html.twig')
            ->setCustomOption('lowStockThreshold', Crud::PAGE_DETAIL === $pageName ? $this->shopSettingsProvider->get()->getLowStockThreshold() : null)
            ->onlyOnDetail();

        yield FormField::addTab('Livraison', 'fa fa-truck');
        yield AssociationField::new('delivery', 'Transporteur')
            ->hideOnIndex();
        yield TextareaField::new('shippingAddress', 'Adresse de livraison')
            ->setTemplatePath('admin/field/shipping_address.html.twig')
            ->hideOnIndex();
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
