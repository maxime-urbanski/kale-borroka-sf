<?php

declare(strict_types=1);

namespace App\Order;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Twig\Attribute\AsTwigFunction;

/**
 * Where an order stands on the timeline of its page (maquette 17), also `order_progress(order)`
 * in Twig: the five steps of the normal cycle, each passed (`done`), the current one (`current`)
 * or to come (`todo`). A cancelled or refunded order leaves the cycle, and `left` says why.
 *
 * No history of the statuses is kept, so a step it left from is only claimed when the workflow
 * leaves no doubt: the steps surely reached are passed, those surely not reached are `off`, the
 * others `unknown`. A cancelled order was paid when it has a payment date, maybe prepared, never
 * shipped (cancel: from pending, paid, preparing); a refunded one was shipped, maybe delivered
 * (refund: from shipped, delivered).
 */
final readonly class OrderProgress
{
    /**
     * @return array{
     *     steps: list<array{status: OrderStatus, state: 'done'|'current'|'todo'|'off'|'unknown', date: ?\DateTimeImmutable}>,
     *     left: ?string,
     * }
     */
    #[AsTwigFunction('order_progress')]
    public function of(Order $order): array
    {
        $status = $order->getStatus();
        $paid = null !== $order->getPaidAt();
        // Index of the last step surely reached, and of the last one maybe reached.
        [$reached, $maybe] = match ($status) {
            OrderStatus::CANCELLED => $paid ? [1, 2] : [0, 0],
            OrderStatus::REFUNDED => [3, 4],
            default => array_fill(0, 2, (int) array_search($status, OrderStatus::cycle(), true)),
        };
        $left = \in_array($status, [OrderStatus::CANCELLED, OrderStatus::REFUNDED], true);

        $steps = [];
        foreach (OrderStatus::cycle() as $i => $step) {
            $steps[] = [
                'status' => $step,
                'state' => match (true) {
                    $i < $reached, $left && $i === $reached => 'done',
                    $left && $i <= $maybe => 'unknown',
                    $left => 'off',
                    $i === $reached => 'current',
                    default => 'todo',
                },
                'date' => match ($step) {
                    OrderStatus::PENDING => $order->getCreatedAt(),
                    OrderStatus::PAID => $order->getPaidAt(),
                    default => null,
                },
            ];
        }

        return [
            'steps' => $steps,
            'left' => match ($status) {
                OrderStatus::CANCELLED => PaymentStatus::REFUNDED === $order->getPaymentStatus()
                    ? 'Annulée après paiement : le montant t’est rendu.'
                    : 'Annulée avant paiement. Rien n’a été encaissé.',
                OrderStatus::REFUNDED => 'Remboursée après l’expédition.',
                default => null,
            },
        ];
    }
}
