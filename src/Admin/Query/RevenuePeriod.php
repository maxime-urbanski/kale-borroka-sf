<?php

declare(strict_types=1);

namespace App\Admin\Query;

/**
 * Revenue periods of the dashboard, in shop time (Europe/Paris) whatever the server's
 * timezone. The current period so far is compared with the same span of the previous one:
 * this week so far against last week up to the same weekday and hour.
 */
enum RevenuePeriod: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';

    public const string TIMEZONE = 'Europe/Paris';

    public function label(): string
    {
        return match ($this) {
            self::DAY => 'Aujourd\'hui',
            self::WEEK => 'Cette semaine',
            self::MONTH => 'Ce mois-ci',
        };
    }

    public function comparisonLabel(): string
    {
        return match ($this) {
            self::DAY => 'vs hier à la même heure',
            self::WEEK => 'vs semaine dernière au même moment',
            self::MONTH => 'vs mois dernier au même jour',
        };
    }

    /**
     * Ends at the start of the next period rather than at "now": payment dates are stored to
     * the second, so an order paid during the current second would fall outside [start, now).
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable} [start, end), in shop time
     */
    public function currentBounds(\DateTimeImmutable $now): array
    {
        $start = $this->start($now->setTimezone(new \DateTimeZone(self::TIMEZONE)));

        return [$start, $start->modify(match ($this) {
            self::DAY => '+1 day',
            self::WEEK => '+7 days',
            self::MONTH => 'first day of next month',
        })];
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable} [start, end), in shop time
     */
    public function previousBounds(\DateTimeImmutable $now): array
    {
        $now = $now->setTimezone(new \DateTimeZone(self::TIMEZONE));
        $shift = match ($this) {
            self::DAY => '-1 day',
            self::WEEK => '-7 days',
            self::MONTH => '-1 month',
        };

        // "-1 month" from 31 March lands on 3 March: cap at the end of the previous month.
        $sameMomentBefore = $now->modify($shift);
        $previousStart = $this->start($this->start($now)->modify('-1 second'));
        if (self::MONTH === $this && $sameMomentBefore >= $this->start($now)) {
            $sameMomentBefore = $this->start($now);
        }

        return [$previousStart, $sameMomentBefore];
    }

    private function start(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return match ($this) {
            self::DAY => $moment->setTime(0, 0),
            self::WEEK => $moment->modify('monday this week')->setTime(0, 0),
            self::MONTH => $moment->modify('first day of this month')->setTime(0, 0),
        };
    }
}
