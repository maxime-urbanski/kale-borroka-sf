<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Address;
use App\Entity\Payment;
use App\Entity\Transporter;
use Symfony\Component\Validator\Constraints as Assert;

class OrderDeliveryDto
{
    #[Assert\NotNull(message: 'Choisis une adresse de livraison.')]
    public ?Address $deliveryAddress = null;

    #[Assert\NotNull(message: 'Choisis un mode de livraison.')]
    public ?Transporter $transporter = null;

    #[Assert\NotNull(message: 'Choisis un moyen de paiement.')]
    public ?Payment $paymentMethod = null;
}
