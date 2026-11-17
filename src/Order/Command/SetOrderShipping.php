<?php

declare(strict_types=1);

namespace App\Order\Command;

/**
 * The label sets the shipping it worked out by hand: transporter and amount in cents.
 * Handled by SetOrderShippingHandler.
 */
final readonly class SetOrderShipping
{
    public function __construct(
        public int $orderId,
        public int $transporterId,
        public int $shippingPrice,
    ) {
    }
}
