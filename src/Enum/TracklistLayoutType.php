<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * How a pressing's tracklist reads, see ReleaseFormat::tracklistLayout().
 */
enum TracklistLayoutType: string
{
    /** Vinyl: A, B on the first disc, C, D on the second… */
    case SIDES_AND_DISCS = 'sides_and_discs';
    /** Tape: sides only. */
    case SIDES = 'sides';
    /** CD: one list. */
    case STRAIGHT = 'straight';
}
