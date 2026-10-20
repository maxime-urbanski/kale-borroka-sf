<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserInformation
{
    #[Assert\NotBlank(message: 'Indique ton nom.')]
    #[Assert\Length(max: 100)]
    public ?string $lastname = null;

    #[Assert\NotBlank(message: 'Indique ton prénom.')]
    #[Assert\Length(max: 100)]
    public ?string $firstname = null;

    #[Assert\NotBlank(message: 'Indique ton adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n’est pas valide.')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    public function __construct(
        private readonly User $user,
    ) {
        $this->lastname = $this->user->getLastname();
        $this->firstname = $this->user->getFirstname();
        $this->email = $this->user->getEmail();
    }
}
