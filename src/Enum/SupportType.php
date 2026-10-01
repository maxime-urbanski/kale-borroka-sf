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
 * decided by ReleaseRepository::applySupports(); forRelease() below must stay consistent with it.
 */
enum SupportType: string
{
    case LP = 'lp';
    case EP = 'ep';
    case CD = 'cd';
    case FANZINE = 'fanzine';
    case TAPE = 'tape';

    /**
     * Canonical section of a release, used to build its URL. A release can be listed under
     * more than one section (a CD EP shows up under CD and EP), but it has only one URL.
     */
    public static function forRelease(ReleaseFormat $format, AlbumReleaseType $releaseType): self
    {
        return match ($format) {
            ReleaseFormat::CD => self::CD,
            ReleaseFormat::CASSETTE => self::TAPE,
            ReleaseFormat::VINYL_7 => self::EP,
            ReleaseFormat::VINYL_12, ReleaseFormat::VINYL_10 => AlbumReleaseType::EP === $releaseType ? self::EP : self::LP,
        };
    }
}
