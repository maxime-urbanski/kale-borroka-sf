<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Where a CMS page is linked in the footer (templates/layout/_footer.html.twig).
 */
enum FooterPlacement: string
{
    case NONE = 'none';
    case ABOUT = 'about';
    case SHOP = 'shop';
    /** Next to the copyright line: CGV, mentions légales… */
    case BOTTOM = 'bottom';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Pas dans le footer',
            self::ABOUT => 'Colonne « À propos »',
            self::SHOP => 'Colonne « Le shop »',
            self::BOTTOM => 'Bandeau du bas (mentions légales, CGV…)',
        };
    }
}
