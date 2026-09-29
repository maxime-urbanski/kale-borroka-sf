<?php

declare(strict_types=1);

namespace App\Tests\Admin\Query;

use App\Admin\Query\RevenuePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RevenuePeriodTest extends TestCase
{
    /**
     * @return iterable<string, array{RevenuePeriod, string, string, string, string, string}>
     */
    public static function boundsProvider(): iterable
    {
        // Wednesday 30 September 2026, 10:00 in Paris.
        $now = '2026-09-30 10:00';

        yield 'day' => [RevenuePeriod::DAY, $now, '2026-09-30 00:00', '2026-10-01 00:00', '2026-09-29 00:00', '2026-09-29 10:00'];
        yield 'week starts on monday' => [RevenuePeriod::WEEK, $now, '2026-09-28 00:00', '2026-10-05 00:00', '2026-09-21 00:00', '2026-09-23 10:00'];
        yield 'month' => [RevenuePeriod::MONTH, $now, '2026-09-01 00:00', '2026-10-01 00:00', '2026-08-01 00:00', '2026-08-30 10:00'];
        // 31 March minus one month overflows to 3 March: capped at the end of February.
        yield 'month, previous one is shorter' => [RevenuePeriod::MONTH, '2026-03-31 10:00', '2026-03-01 00:00', '2026-04-01 00:00', '2026-02-01 00:00', '2026-03-01 00:00'];
    }

    #[DataProvider('boundsProvider')]
    public function testBounds(RevenuePeriod $period, string $now, string $start, string $end, string $previousStart, string $previousEnd): void
    {
        $now = new \DateTimeImmutable($now, new \DateTimeZone('Europe/Paris'));

        self::assertSame([$start, $end], self::format($period->currentBounds($now)));
        self::assertSame([$previousStart, $previousEnd], self::format($period->previousBounds($now)));
    }

    /**
     * The server runs in UTC: 23:30 UTC on the 29th is already the 30th in Paris.
     */
    public function testBoundsAreInShopTime(): void
    {
        $now = new \DateTimeImmutable('2026-09-29 23:30', new \DateTimeZone('UTC'));

        self::assertSame(['2026-09-30 00:00', '2026-10-01 00:00'], self::format(RevenuePeriod::DAY->currentBounds($now)));
    }

    /**
     * @param array{\DateTimeImmutable, \DateTimeImmutable} $bounds
     *
     * @return array{string, string}
     */
    private static function format(array $bounds): array
    {
        return [$bounds[0]->format('Y-m-d H:i'), $bounds[1]->format('Y-m-d H:i')];
    }
}
