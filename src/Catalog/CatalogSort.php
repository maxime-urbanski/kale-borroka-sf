<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Order of the catalogue page (?sort=), « Nouveautés » by default.
 */
enum CatalogSort: string
{
    case NEW = 'new';
    case PRICE_ASC = 'price-asc';
    case PRICE_DESC = 'price-desc';
    case AZ = 'az';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nouveautés',
            self::PRICE_ASC => 'Prix croissant',
            self::PRICE_DESC => 'Prix décroissant',
            self::AZ => 'A–Z',
        };
    }
}
