<?php

declare(strict_types=1);

namespace App\Order\Command;

/**
 * Turns a cart into a pending order. Carries ids only, so it stays a plain message.
 * Handled by PlaceOrderHandler, which returns the order reference.
 */
final readonly class PlaceOrder
{
    /**
     * @param array<int, int> $lines article id => quantity, as stored in the cart session
     */
    public function __construct(
        public int $buyerId,
        public int $addressId,
        public int $transporterId,
        public int $paymentId,
        public array $lines,
    ) {
    }
}
