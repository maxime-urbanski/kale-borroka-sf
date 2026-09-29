<?php

declare(strict_types=1);

namespace App\Admin\Query;

final readonly class RevenueFigure
{
    public function __construct(
        public RevenuePeriod $period,
        /** Cents. */
        public int $amount,
        public int $orderCount,
        /** Cents, same span of the previous period. */
        public int $previousAmount,
    ) {
    }

    /**
     * Change against the previous period, in percent; null when there is nothing to compare with.
     */
    public function variation(): ?float
    {
        if (0 === $this->previousAmount) {
            return null;
        }

        return round(($this->amount - $this->previousAmount) / $this->previousAmount * 100, 1);
    }
}
