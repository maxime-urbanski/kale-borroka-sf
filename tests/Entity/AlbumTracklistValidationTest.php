<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Album;
use App\Entity\Song;
use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Sides only mean something when every track has one, once, in track order.
 */
class AlbumTracklistValidationTest extends KernelTestCase
{
    use ServiceTrait;

    /**
     * @return iterable<string, array{list<string|null>, string|null}>
     */
    public static function tracklists(): iterable
    {
        yield 'an LP' => [['A1', 'A2', 'B1'], null];
        yield 'a CD, no position' => [[null, null], null];
        yield 'one track forgotten' => [['A1', null, 'B1'], 'Indiquez la face de tous les morceaux, ou d\'aucun.'];
        yield 'twice the same position' => [['A1', 'B1', 'B1'], 'Position B1 en double.'];
        yield 'positions out of track order' => [['A2', 'A1'], 'Les positions doivent suivre l\'ordre des morceaux (A1 avant A2…).'];
        yield 'side B before side A' => [['B1', 'A1'], 'Les positions doivent suivre l\'ordre des morceaux (A1 avant A2…).'];
    }

    /**
     * @param list<string|null> $positions
     */
    #[DataProvider('tracklists')]
    public function testTheTracklistPositions(array $positions, ?string $error): void
    {
        $album = (new Album())->setName('Test');

        foreach ($positions as $index => $position) {
            $album->addTracklist((new Song())->setName('Track '.($index + 1))->setTrack($index + 1)->setPosition($position));
        }

        $messages = [];

        foreach (self::service(ValidatorInterface::class)->validate($album) as $violation) {
            if ('tracklists' === $violation->getPropertyPath()) {
                $messages[] = (string) $violation->getMessage();
            }
        }

        self::assertSame(null === $error ? [] : [$error], $messages);
    }
}
