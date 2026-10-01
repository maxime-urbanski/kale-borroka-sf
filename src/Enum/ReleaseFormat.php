<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Physical format of a pressing (schema.org: MusicReleaseFormatType).
 *
 * Distinct from SupportType, which is the catalogue section a release is listed under:
 * a 7" is always filed under EP, a 12" under LP unless its album is an EP.
 */
enum ReleaseFormat: string
{
    case VINYL_12 = 'vinyl_12';
    case VINYL_10 = 'vinyl_10';
    case VINYL_7 = 'vinyl_7';
    case CD = 'cd';
    case CASSETTE = 'cassette';

    public function label(): string
    {
        return match ($this) {
            self::VINYL_12 => 'Vinyle 12"',
            self::VINYL_10 => 'Vinyle 10"',
            self::VINYL_7 => 'Vinyle 7"',
            self::CD => 'CD',
            self::CASSETTE => 'K7',
        };
    }

    public function schemaOrg(): string
    {
        return match ($this) {
            self::VINYL_12, self::VINYL_10, self::VINYL_7 => 'https://schema.org/VinylFormat',
            self::CD => 'https://schema.org/CDFormat',
            self::CASSETTE => 'https://schema.org/CassetteFormat',
        };
    }

    /**
     * How a tracklist reads on it (TracklistLayout): by side and by disc on vinyl, by side on a
     * tape, straight through on a CD.
     */
    public function tracklistLayout(): TracklistLayoutType
    {
        return match ($this) {
            self::VINYL_12, self::VINYL_10, self::VINYL_7 => TracklistLayoutType::SIDES_AND_DISCS,
            self::CASSETTE => TracklistLayoutType::SIDES,
            self::CD => TracklistLayoutType::STRAIGHT,
        };
    }

    /**
     * Disc diameter, for vinyl only.
     */
    public function size(): ?string
    {
        return match ($this) {
            self::VINYL_12 => '12"',
            self::VINYL_10 => '10"',
            self::VINYL_7 => '7"',
            self::CD, self::CASSETTE => null,
        };
    }
}
