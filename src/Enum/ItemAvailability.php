<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Commercial availability of an offer (schema.org: ItemAvailability).
 *
 * Deliberately distinct from the raw `quantity`: a record can be in stock but not yet on
 * sale (pre-order), or out of stock and never coming back (sold out).
 */
enum ItemAvailability: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case PRE_ORDER = 'pre_order';
    case SOLD_OUT = 'sold_out';

    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'En stock',
            self::OUT_OF_STOCK => 'En rupture',
            self::PRE_ORDER => 'En précommande',
            self::SOLD_OUT => 'Épuisé',
        };
    }

    /**
     * Whether an offer in this state can be put in the cart at all.
     */
    public function isPurchasable(): bool
    {
        return match ($this) {
            self::IN_STOCK, self::PRE_ORDER => true,
            self::OUT_OF_STOCK, self::SOLD_OUT => false,
        };
    }
}
