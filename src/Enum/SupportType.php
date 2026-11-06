<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Catalogue section ("rayon"), and the `{support}` URL segment.
 *
 * Single source of truth for the list of sections: `Support::$code` is typed with it and the
 * catalogue routes get their requirement from it through EnumRequirement. Adding a case
 * means adding a Support row too: in the fixtures, and in production through
 * app:catalog:import (CatalogImporter creates the missing rows).
 *
 * A section is not a physical format — see ReleaseFormat. Which releases a section lists is
 * decided by CatalogQuery::inSection(); forRelease() below must stay consistent with it.
 */
enum SupportType: string
{
    case LP = 'lp';
    case EP = 'ep';
    case CD = 'cd';
    // In the order of the catalogue tabs.
    case TAPE = 'tape';
    case FANZINE = 'fanzine';

    /**
     * Name of the section on the catalogue tabs and filters.
     */
    public function label(): string
    {
        return match ($this) {
            self::LP => 'LP',
            self::EP => 'EP / 7"',
            self::CD => 'CD',
            self::TAPE => 'K7',
            self::FANZINE => 'Fanzines',
        };
    }

    /**
     * Section of a release, used to build its URL: its format alone. EP / 7" is the 45s, so
     * an EP on CD or tape sits under CD or K7, a 10" or 12" one under LP.
     */
    public static function forRelease(ReleaseFormat $format): self
    {
        return match ($format) {
            ReleaseFormat::CD => self::CD,
            ReleaseFormat::CASSETTE => self::TAPE,
            ReleaseFormat::VINYL_7 => self::EP,
            ReleaseFormat::VINYL_12, ReleaseFormat::VINYL_10 => self::LP,
        };
    }
}
