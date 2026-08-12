<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * How the record was produced (schema.org: MusicAlbumProductionType).
 *
 * Only the cases that make sense for a punk/hardcore distro are kept — schema.org also
 * defines DJMixAlbum, MixtapeAlbum, RemixAlbum, SoundtrackAlbum and SpokenWordAlbum.
 */
enum AlbumProductionType: string
{
    case STUDIO = 'studio';
    case LIVE = 'live';
    case COMPILATION = 'compilation';
    case DEMO = 'demo';
    case SPLIT = 'split';
    case BOOTLEG = 'bootleg';

    public function label(): string
    {
        return match ($this) {
            self::STUDIO => 'Studio',
            self::LIVE => 'Live',
            self::COMPILATION => 'Compilation',
            self::DEMO => 'Démo',
            self::SPLIT => 'Split',
            self::BOOTLEG => 'Bootleg',
        };
    }
}
