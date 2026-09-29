<?php

declare(strict_types=1);

namespace App\Order\Command;

use App\Repository\OrderRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Side effects (stock, payment status) are workflow listeners: see OrderWorkflowSubscriber.
 * They run inside the bus transaction, so a failure leaves the order untouched.
 */
#[AsMessageHandler(bus: 'command.bus')]
readonly class ApplyOrderTransitionHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        #[Target('order')]
        private WorkflowInterface $orderWorkflow,
    ) {
    }

    /**
     * @throws NotEnabledTransitionException when the order is not in a state the transition starts from
     */
    public function __invoke(ApplyOrderTransition $command): void
    {
        $order = $this->orderRepository->find($command->orderId)
            ?? throw new \InvalidArgumentException(\sprintf('Order #%d does not exist.', $command->orderId));

        $this->orderWorkflow->apply($order, $command->transition->value);
    }
}
