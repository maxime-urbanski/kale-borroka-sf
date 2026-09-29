<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Condition of a copy on sale (schema.org: OfferItemCondition).
 */
enum ItemCondition: string
{
    case NEW = 'new';
    case USED = 'used';
    case REFURBISHED = 'refurbished';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Neuf',
            self::USED => 'Occasion',
            self::REFURBISHED => 'Reconditionné',
        };
    }

    public function schemaOrg(): string
    {
        return match ($this) {
            self::NEW => 'https://schema.org/NewCondition',
            self::USED => 'https://schema.org/UsedCondition',
            self::REFURBISHED => 'https://schema.org/RefurbishedCondition',
        };
    }
}
