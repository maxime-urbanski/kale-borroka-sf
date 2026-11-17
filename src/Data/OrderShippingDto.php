<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Transporter;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The admin's « Fixer le port » form: the shipping worked out by hand, in cents.
 */
class OrderShippingDto
{
    #[Assert\NotNull(message: 'Choisis un transporteur.')]
    public ?Transporter $transporter = null;

    #[Assert\NotNull(message: 'Indique le port (0 s\'il est offert).')]
    #[Assert\PositiveOrZero(message: 'Le port ne peut pas être négatif.')]
    #[Assert\LessThanOrEqual(value: 100000, message: 'Le port ne dépasse pas {{ compared_value }} centimes (1 000 €) : vérifie le montant.')]
    public ?int $shippingPrice = null;
}
