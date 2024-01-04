<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\User;

class UserAddress
{
    public string $name = '';

    public string $address = '';

    public string $city = '';

    public string $country = '';

    public ?User $users = null;
}
