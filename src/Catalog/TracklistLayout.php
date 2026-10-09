<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Song;
use App\Enum\ReleaseFormat;
use App\Enum\TracklistLayoutType;

/**
 * How a tracklist reads on a given pressing: by side on a vinyl or a tape (and by disc past two
 * vinyl sides), straight through on a CD. One album, one tracklist: its positions are those of
 * the vinyl, and a CD pressing simply ignores them.
 */
final class TracklistLayout
{
    /**
     * @param iterable<Song>     $tracks in track order
     * @param ReleaseFormat|null $format the pressing shown; null (back office) lets the positions decide
     *
     * @return list<TracklistSide>
     */
    public static function sides(iterable $tracks, ?ReleaseFormat $format): array
    {
        $tracks = \is_array($tracks) ? array_values($tracks) : iterator_to_array($tracks, false);

        if ([] === $tracks) {
            return [];
        }

        $layout = $format?->tracklistLayout();
        // A single track without position, and the sides would be a guess.
        $sided = TracklistLayoutType::STRAIGHT !== $layout
            && [] === array_filter($tracks, static fn (Song $track): bool => null === $track->getPosition());

        if (!$sided) {
            return [new TracklistSide(null, null, $tracks)];
        }

        $bySide = [];

        foreach ($tracks as $track) {
            $bySide[substr((string) $track->getPosition(), 0, 1)][] = $track;
        }

        ksort($bySide);
        // A, B on the first disc, C, D on the second... A tape has sides only.
        $discs = TracklistLayoutType::SIDES !== $layout && \count($bySide) > 2;

        return array_map(
            static fn (string $side, array $sideTracks): TracklistSide => new TracklistSide(
                $side,
                $discs ? intdiv(\ord($side) - \ord('A'), 2) + 1 : null,
                $sideTracks,
            ),
            array_map(strval(...), array_keys($bySide)),
            array_values($bySide),
        );
    }
}
