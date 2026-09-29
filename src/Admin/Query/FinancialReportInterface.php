<?php

declare(strict_types=1);

namespace App\Admin\Query;

interface FinancialReportInterface
{
    /**
     * One row per month of the year (shop time), empty months included.
     *
     * @return list<array{month: \DateTimeImmutable, orders: int, revenue: int, refunded: int}> amounts in cents
     */
    public function monthly(int $year): array;

    /**
     * Every order paid during the year — including those refunded since — for the CSV export.
     *
     * @return iterable<array{paidAt: \DateTimeImmutable, reference: string, buyer: string, paymentMethod: string, paymentStatus: string, status: string, total: int}>
     */
    public function payments(int $year): iterable;

    /**
     * @return list<int> years with at least one payment, plus the current one, most recent first
     */
    public function years(\DateTimeImmutable $now): array;
}
