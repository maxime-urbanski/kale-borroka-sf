<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Chips of the homepage's « Derniers articles » (ArticleRepository::latest()). Not catalogue
 * sections (SupportType): vinyls of every size together, second-hand copies too. No merch
 * yet: a t-shirt has no page of its own to link to.
 */
enum HomeShelf: string
{
    case ALL = 'tout';
    case VINYL = 'vinyles';
    case CD = 'cd';
    case TAPE = 'k7';
    case ZINE = 'zines';
    case USED = 'occasion';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Tout',
            self::VINYL => 'Vinyles',
            self::CD => 'CD',
            self::TAPE => 'K7',
            self::ZINE => 'Zines',
            self::USED => 'Occasion',
        };
    }
}
