<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;

interface UserDefaultAddressInterface
{
    public function defaultAddress(User $user, Address $newDefaultAddress): void;
}
