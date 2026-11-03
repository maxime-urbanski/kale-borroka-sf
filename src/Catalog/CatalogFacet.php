<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A group of catalogue filters: its query string key, its title in the filter column.
 */
enum CatalogFacet: string
{
    case SUPPORTS = 'supports';
    case STYLES = 'styles';
    case LABELS = 'labels';
    case ARTISTS = 'artists';
    case CONDITIONS = 'conditions';
    case KBR_PRODUCTION = 'kbrProduction';
    case HIDE_SOLD_OUT = 'hideSoldOut';

    public function label(): string
    {
        return match ($this) {
            self::SUPPORTS => 'Rayon',
            self::STYLES => 'Style',
            self::LABELS => 'Label',
            self::ARTISTS => 'Artiste',
            self::CONDITIONS => 'État',
            self::KBR_PRODUCTION => 'Nos productions',
            self::HIDE_SOLD_OUT => 'Masquer les épuisés',
        };
    }

    /** A switch (on or off) rather than a list of values. */
    public function isSwitch(): bool
    {
        return self::KBR_PRODUCTION === $this || self::HIDE_SOLD_OUT === $this;
    }
}
