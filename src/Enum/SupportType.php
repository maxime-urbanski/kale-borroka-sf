<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Physical format a release is pressed on (schema.org: MusicReleaseFormatType).
 *
 * This enum is the single source of truth for the list of supports: the `support.code`
 * column is typed with it, and the `{support}` route segment gets its requirement from
 * it through Symfony's EnumRequirement. Adding a case here is enough — no route
 * requirement to update by hand.
 */
enum SupportType: string
{
    case LP = 'lp';
    case EP = 'ep';
    case CD = 'cd';
    case FANZINE = 'fanzine';
    case TAPE = 'tape';

    public function label(): string
    {
        return match ($this) {
            self::LP => 'LP',
            self::EP => 'EP',
            self::CD => 'CD',
            self::FANZINE => 'Fanzine',
            self::TAPE => 'K7',
        };
    }
}
