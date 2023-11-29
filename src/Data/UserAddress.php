<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\User;

class UserAddress
{
    public string $name;
    public string $address;
    public string $complement_address;
    public string $city;
    public string $zipcode;
    public string $country;
    public bool $isMainAddress;
    public User $users;
}
