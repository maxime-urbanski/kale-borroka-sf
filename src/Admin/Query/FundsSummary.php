<?php

declare(strict_types=1);

namespace App\Admin\Query;

/**
 * Amounts in cents.
 */
final readonly class FundsSummary
{
    public function __construct(
        public int $openingBalance,
        /** Null: counted from the very first movement. */
        public ?\DateTimeImmutable $since,
        public int $shopRevenue,
        /** Up to today. */
        public int $eventSales,
        /** Paid until today. */
        public int $expenses,
        /** Dated after today: not taken off the available funds yet. */
        public int $upcomingExpenses = 0,
    ) {
    }

    public function available(): int
    {
        return $this->openingBalance + $this->shopRevenue + $this->eventSales - $this->expenses;
    }
}
