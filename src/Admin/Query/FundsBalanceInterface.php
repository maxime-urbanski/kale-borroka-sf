<?php

declare(strict_types=1);

namespace App\Admin\Query;

interface FundsBalanceInterface
{
    /**
     * Opening balance of the shop settings, plus shop payments and event sales, minus the
     * label's expenses, all from the opening balance date on and up to today (shop time).
     * Expenses dated later are only reported as upcoming.
     */
    public function summary(): FundsSummary;
}
