<?php

declare(strict_types=1);

namespace App\Admin\Query;

use App\Entity\Order;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Figures by payment date, in shop time. Same definition of revenue as the dashboard:
 * payment status `paid`. Refunds are reported separately, on the month the order was paid.
 * Event sales and label expenses are dated by day, already in shop time.
 */
readonly class FinancialReport implements FinancialReportInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function monthly(int $year): array
    {
        [$start, $end] = self::yearBounds($year);

        // paid_at holds UTC: convert it to shop time before taking the month.
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(<<<'SQL'
            SELECT
                date_part('month', paid_at AT TIME ZONE 'UTC' AT TIME ZONE :timezone)::int AS month,
                count(*) FILTER (WHERE payment_status = :paid) AS orders,
                coalesce(sum(total_price) FILTER (WHERE payment_status = :paid), 0) AS revenue,
                coalesce(sum(total_price) FILTER (WHERE payment_status = :refunded), 0) AS refunded
            FROM "order"
            WHERE paid_at >= :start AND paid_at < :end
            GROUP BY 1
            SQL, [
            'timezone' => RevenuePeriod::TIMEZONE,
            'paid' => PaymentStatus::PAID->value,
            'refunded' => PaymentStatus::REFUNDED->value,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        $byMonth = array_column($rows, null, 'month');
        $eventSales = $this->monthlySum('event_sale', 'price', 'start_time', $year);
        $expenses = $this->monthlySum('expense', 'total_payment_due', 'payment_due_date', $year);
        $months = [];

        for ($month = 1; $month <= 12; ++$month) {
            $months[] = [
                'month' => new \DateTimeImmutable(\sprintf('%d-%02d-01', $year, $month), new \DateTimeZone(RevenuePeriod::TIMEZONE)),
                'orders' => (int) ($byMonth[$month]['orders'] ?? 0),
                'revenue' => (int) ($byMonth[$month]['revenue'] ?? 0),
                'refunded' => (int) ($byMonth[$month]['refunded'] ?? 0),
                'eventSales' => $eventSales[$month] ?? 0,
                'expenses' => $expenses[$month] ?? 0,
            ];
        }

        return $months;
    }

    public function payments(int $year): iterable
    {
        [$start, $end] = self::yearBounds($year);
        $timezone = new \DateTimeZone(RevenuePeriod::TIMEZONE);

        $orders = $this->entityManager->createQueryBuilder()
            ->select('o', 'buyer', 'payment')
            ->from(Order::class, 'o')
            ->leftJoin('o.buyer', 'buyer')
            ->leftJoin('o.payment', 'payment')
            ->where('o.paidAt >= :start')
            ->andWhere('o.paidAt < :end')
            ->andWhere('o.paymentStatus IN (:statuses)')
            ->orderBy('o.paidAt', 'ASC')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', [PaymentStatus::PAID->value, PaymentStatus::REFUNDED->value])
            ->getQuery()
            ->toIterable();

        /** @var Order $order */
        foreach ($orders as $order) {
            yield [
                'paidAt' => ($order->getPaidAt() ?? new \DateTimeImmutable())->setTimezone($timezone),
                'reference' => (string) $order->getReference(),
                'buyer' => (string) $order->getBuyer()?->getEmail(),
                'paymentMethod' => (string) $order->getPayment()?->getName(),
                'paymentStatus' => $order->getPaymentStatus()->label(),
                'status' => $order->getStatus()->label(),
                'total' => (int) $order->getTotalPrice(),
            ];

            $this->entityManager->detach($order);
        }
    }

    public function years(\DateTimeImmutable $now): array
    {
        $first = $this->entityManager->getConnection()->fetchOne(<<<'SQL'
            SELECT least(
                (SELECT min(paid_at)::date FROM "order"),
                (SELECT min(start_time) FROM event_sale),
                (SELECT min(payment_due_date) FROM expense)
            )
            SQL);
        $current = (int) $now->setTimezone(new \DateTimeZone(RevenuePeriod::TIMEZONE))->format('Y');
        $oldest = false === $first || null === $first ? $current : (int) substr((string) $first, 0, 4);

        return range($current, min($oldest, $current));
    }

    /**
     * @return array<int, int> month => amount in cents, months without any left out
     */
    private function monthlySum(string $table, string $amount, string $date, int $year): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllKeyValue(\sprintf(
            'SELECT date_part(\'month\', %2$s)::int, sum(%1$s) FROM %3$s WHERE %2$s >= :start AND %2$s < :end GROUP BY 1',
            $amount,
            $date,
            $table,
        ), [
            'start' => \sprintf('%d-01-01', $year),
            'end' => \sprintf('%d-01-01', $year + 1),
        ]);

        return array_map('intval', $rows);
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable} [1 January, next 1 January) in shop time, as UTC
     */
    private static function yearBounds(int $year): array
    {
        $timezone = new \DateTimeZone(RevenuePeriod::TIMEZONE);

        return [
            DashboardMetrics::utc(new \DateTimeImmutable(\sprintf('%d-01-01', $year), $timezone)),
            DashboardMetrics::utc(new \DateTimeImmutable(\sprintf('%d-01-01', $year + 1), $timezone)),
        ];
    }
}
