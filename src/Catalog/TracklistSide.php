<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Song;

/**
 * One side of a record (or of one of its discs) and its tracks, in order. A tracklist that
 * plays straight through is a single TracklistSide without side.
 */
final readonly class TracklistSide
{
    /**
     * @param list<Song> $tracks
     */
    public function __construct(
        public ?string $side,
        public ?int $disc,
        public array $tracks,
    ) {
    }

    /**
     * « Face A », « Disque 2 · Face C », or nothing for a tracklist without sides.
     */
    public function label(): string
    {
        if (null === $this->side) {
            return '';
        }

        return null === $this->disc ? 'Face '.$this->side : \sprintf('Disque %d · Face %s', $this->disc, $this->side);
    }
}
