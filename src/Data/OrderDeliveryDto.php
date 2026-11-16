<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Address;
use App\Entity\Payment;
use Symfony\Component\Validator\Constraints as Assert;

class OrderDeliveryDto
{
    #[Assert\NotNull(message: 'Choisis une adresse de livraison.')]
    public ?Address $deliveryAddress = null;

    #[Assert\NotNull(message: 'Choisis un moyen de paiement.')]
    public ?Payment $paymentMethod = null;

    #[Assert\Length(max: 280, maxMessage: 'Ton message fait {{ limit }} caractères au plus.')]
    public ?string $message = null;
}
