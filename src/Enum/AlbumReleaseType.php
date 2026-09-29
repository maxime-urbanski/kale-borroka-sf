<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * What kind of record an album is. Maps onto two schema.org properties at once:
 * MusicAlbumReleaseType (album / EP / single) and MusicAlbumProductionType (studio / live / compilation).
 */
enum AlbumReleaseType: string
{
    case ALBUM = 'album';
    case EP = 'ep';
    case SINGLE = 'single';
    case SPLIT = 'split';
    case COMPILATION = 'compilation';
    case LIVE = 'live';

    public function label(): string
    {
        return match ($this) {
            self::ALBUM => 'Album',
            self::EP => 'EP',
            self::SINGLE => 'Single',
            self::SPLIT => 'Split',
            self::COMPILATION => 'Compilation',
            self::LIVE => 'Live',
        };
    }

    public function schemaOrgReleaseType(): string
    {
        return match ($this) {
            self::EP => 'https://schema.org/EPRelease',
            self::SINGLE => 'https://schema.org/SingleRelease',
            self::ALBUM, self::SPLIT, self::COMPILATION, self::LIVE => 'https://schema.org/AlbumRelease',
        };
    }

    public function schemaOrgProductionType(): string
    {
        return match ($this) {
            self::COMPILATION => 'https://schema.org/CompilationAlbum',
            self::LIVE => 'https://schema.org/LiveAlbum',
            self::ALBUM, self::EP, self::SINGLE, self::SPLIT => 'https://schema.org/StudioAlbum',
        };
    }
}
