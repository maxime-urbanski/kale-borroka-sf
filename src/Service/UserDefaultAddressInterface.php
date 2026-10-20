<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;

/**
 * The default (delivery) address: exactly one per customer who has any.
 */
interface UserDefaultAddressInterface
{
    public function defaultAddress(User $user, Address $newDefaultAddress): void;

    /**
     * After the default address was removed: the oldest remaining one takes over.
     */
    public function handOver(User $user): void;
}
