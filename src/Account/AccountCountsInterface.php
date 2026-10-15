<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;

interface AccountCountsInterface
{
    /**
     * The customer's orders, collection and wantlist items and addresses, in one query.
     */
    public function of(User $user): AccountTotals;
}
