<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserInformation
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $lastname = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $firstname = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotNull(message: 'Choisissez une adresse de livraison.')]
    public ?Address $address = null;

    public function __construct(
        private readonly User $user,
    ) {
        $this->lastname = $this->user->getLastname();
        $this->firstname = $this->user->getFirstname();
        $this->email = $this->user->getEmail();
    }
}
