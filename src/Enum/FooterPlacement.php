<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Whether a CMS page is linked from the footer's bottom line (templates/layout/_footer.html.twig).
 * The footer's columns are FooterColumn rows, edited apart.
 */
enum FooterPlacement: string
{
    case NONE = 'none';
    /** Next to the copyright line: CGV, mentions légales… */
    case BOTTOM = 'bottom';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Non',
            self::BOTTOM => 'Oui, à côté du copyright (mentions légales, CGV…)',
        };
    }
}
