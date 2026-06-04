<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Address;
use App\Entity\User;

class UpdateUserInformation
{
    public ?string $lastname = null;

    public ?string $firstname = null;

    public ?string $email = null;

    public ?Address $address = null;

    public function __construct(
        private readonly User $user,
    ) {
        $this->lastname = $this->user->getLastname();
        $this->firstname = $this->user->getFirstname();
        $this->email = $this->user->getEmail();
    }
}
