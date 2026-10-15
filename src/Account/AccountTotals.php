<?php

declare(strict_types=1);

namespace App\Account;

/**
 * What the account menu and the dashboard count for one customer.
 */
final readonly class AccountTotals
{
    public function __construct(
        public int $orders,
        public int $collection,
        public int $wantlist,
        /** Wantlist articles a customer can buy right now: published and in stock. */
        public int $wantlistInStock,
        public int $addresses,
    ) {
    }
}
