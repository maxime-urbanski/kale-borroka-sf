<?php

declare(strict_types=1);

namespace App\Order\Command;

use App\Enum\OrderTransition;

/**
 * Moves an order through the `order` workflow (pay, ship, cancel, …).
 */
final readonly class ApplyOrderTransition
{
    public function __construct(
        public int $orderId,
        public OrderTransition $transition,
    ) {
    }
}
