<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Condition of the copy being sold (schema.org: OfferItemCondition).
 *
 * The same pressing can be offered twice — new and second-hand — at different prices,
 * which is why this sits on Article (the offer) and not on Edition (the pressing).
 */
enum ItemCondition: string
{
    case NEW = 'new';
    case USED = 'used';
    case REFURBISHED = 'refurbished';
    case DAMAGED = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Neuf',
            self::USED => 'Occasion',
            self::REFURBISHED => 'Reconditionné',
            self::DAMAGED => 'Abîmé',
        };
    }
}
