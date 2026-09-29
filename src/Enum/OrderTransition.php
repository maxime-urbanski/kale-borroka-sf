<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Transitions of the `order` workflow (config/packages/workflow.yaml).
 */
enum OrderTransition: string
{
    case PAY = 'pay';
    case PREPARE = 'prepare';
    case SHIP = 'ship';
    case DELIVER = 'deliver';
    case CANCEL = 'cancel';
    case REFUND = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::PAY => 'Marquer payée',
            self::PREPARE => 'Passer en préparation',
            self::SHIP => 'Marquer expédiée',
            self::DELIVER => 'Marquer livrée',
            self::CANCEL => 'Annuler',
            self::REFUND => 'Rembourser',
        };
    }
}
