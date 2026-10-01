<?php

declare(strict_types=1);

namespace App\Tests\Catalog;

use App\Catalog\TracklistLayout;
use App\Catalog\TracklistSide;
use App\Entity\Song;
use App\Enum\ReleaseFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TracklistLayoutTest extends TestCase
{
    public function testAnLpIsSplitIntoItsTwoSides(): void
    {
        $sides = TracklistLayout::sides($this->tracks('A1', 'A2', 'B1', 'B2'), ReleaseFormat::VINYL_12);

        self::assertSame(['Face A', 'Face B'], array_map(static fn (TracklistSide $side): string => $side->label(), $sides));
        self::assertSame(['A1', 'A2'], $this->positions($sides[0]));
        self::assertSame(['B1', 'B2'], $this->positions($sides[1]));
    }

    public function testADoubleLpNamesItsDiscs(): void
    {
        $sides = TracklistLayout::sides($this->tracks('A1', 'B1', 'C1', 'D1'), ReleaseFormat::VINYL_12);

        self::assertSame(
            ['Disque 1 · Face A', 'Disque 1 · Face B', 'Disque 2 · Face C', 'Disque 2 · Face D'],
            array_map(static fn (TracklistSide $side): string => $side->label(), $sides),
        );
    }

    public function testATapeHasSidesButNoDiscs(): void
    {
        $sides = TracklistLayout::sides($this->tracks('A1', 'B1', 'C1'), ReleaseFormat::CASSETTE);

        self::assertSame(['Face A', 'Face B', 'Face C'], array_map(static fn (TracklistSide $side): string => $side->label(), $sides));
    }

    /**
     * @return iterable<string, array{list<string|null>, ReleaseFormat|null}>
     */
    public static function flatTracklists(): iterable
    {
        yield 'a CD plays straight through' => [['A1', 'A2', 'B1'], ReleaseFormat::CD];
        yield 'a track without position' => [['A1', null, 'B1'], ReleaseFormat::VINYL_12];
        yield 'no position at all' => [[null, null], ReleaseFormat::VINYL_7];
    }

    /**
     * @param list<string|null> $positions
     */
    #[DataProvider('flatTracklists')]
    public function testOtherwiseTheTracklistIsOneUnnamedList(array $positions, ?ReleaseFormat $format): void
    {
        $tracks = $this->tracks(...$positions);
        $sides = TracklistLayout::sides($tracks, $format);

        self::assertCount(1, $sides);
        self::assertNull($sides[0]->side);
        self::assertSame('', $sides[0]->label());
        self::assertSame($tracks, $sides[0]->tracks);
    }

    /**
     * The back office shows an album whatever its pressings: by side when it has positions.
     */
    public function testWithoutFormatThePositionsDecide(): void
    {
        self::assertCount(2, TracklistLayout::sides($this->tracks('A1', 'B1'), null));
        self::assertCount(1, TracklistLayout::sides($this->tracks(null, null), null));
    }

    public function testNoTrackMeansNoSide(): void
    {
        self::assertSame([], TracklistLayout::sides([], ReleaseFormat::VINYL_12));
    }

    /**
     * @return list<Song>
     */
    private function tracks(?string ...$positions): array
    {
        $tracks = [];

        foreach (array_values($positions) as $index => $position) {
            $tracks[] = (new Song())->setName('Track '.($index + 1))->setTrack($index + 1)->setPosition($position);
        }

        return $tracks;
    }

    /**
     * @return list<string|null>
     */
    private function positions(TracklistSide $side): array
    {
        return array_map(static fn (Song $song): ?string => $song->getPosition(), $side->tracks);
    }
}
