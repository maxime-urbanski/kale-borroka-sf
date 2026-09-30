<?php

declare(strict_types=1);

namespace App\Order\EventSubscriber;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Enum\PaymentStatus;
use App\Service\StockManagerInterface;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Side effects of the `order` workflow. Transition listeners run before the new status is
 * set, so an exception here (e.g. insufficient stock) aborts the transition.
 */
readonly class OrderWorkflowSubscriber
{
    public function __construct(
        private StockManagerInterface $stockManager,
    ) {
    }

    /**
     * @param TransitionEvent<Order> $event
     */
    #[AsTransitionListener(workflow: 'order', transition: 'pay')]
    public function onPay(TransitionEvent $event): void
    {
        $order = $event->getSubject();

        foreach ($order->getOrderDetails() as $line) {
            $this->stockManager->take(self::articleOf($line), (int) $line->getQuantity());
        }

        $order
            ->setPaymentStatus(PaymentStatus::PAID)
            ->setPaidAt(new \DateTimeImmutable());
    }

    /**
     * Cancelling a paid order gives its items back to the shop and implies a refund.
     *
     * @param TransitionEvent<Order> $event
     */
    #[AsTransitionListener(workflow: 'order', transition: 'cancel')]
    public function onCancel(TransitionEvent $event): void
    {
        $order = $event->getSubject();

        if ($order->getStatus()->holdsStock()) {
            foreach ($order->getOrderDetails() as $line) {
                $this->stockManager->putBack(self::articleOf($line), (int) $line->getQuantity());
            }
        }

        if (PaymentStatus::PAID === $order->getPaymentStatus()) {
            $order->setPaymentStatus(PaymentStatus::REFUNDED);
        }
    }

    /**
     * A refunded order has been shipped: whether the items come back is not known, so the
     * stock is left alone and adjusted by hand on return.
     *
     * @param TransitionEvent<Order> $event
     */
    #[AsTransitionListener(workflow: 'order', transition: 'refund')]
    public function onRefund(TransitionEvent $event): void
    {
        $event->getSubject()->setPaymentStatus(PaymentStatus::REFUNDED);
    }

    /**
     * product_id is NOT NULL: a saved order line always has its article.
     */
    private static function articleOf(OrderDetails $line): Article
    {
        return $line->getProduct() ?? throw new \LogicException('An order line always has its article.');
    }
}
