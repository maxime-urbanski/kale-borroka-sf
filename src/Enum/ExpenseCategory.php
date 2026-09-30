<?php

declare(strict_types=1);

namespace App\Enum;

enum ExpenseCategory: string
{
    case PRODUCTION = 'production';
    case MERCH = 'merch';
    case STOCK = 'stock';
    case SHIPPING = 'shipping';
    case EVENT = 'event';
    case PROMOTION = 'promotion';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCTION => 'Pressage & fabrication',
            self::MERCH => 'Merch & textile',
            self::STOCK => 'Achat de stock (distro)',
            self::SHIPPING => 'Envois & emballages',
            self::EVENT => 'Concerts & événements',
            self::PROMOTION => 'Promo & graphisme',
            self::OTHER => 'Autre',
        };
    }
}
