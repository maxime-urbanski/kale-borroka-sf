<?php

declare(strict_types=1);

namespace App\Admin\Query;

use App\Entity\Order;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Revenue is money actually collected: orders whose payment status is `paid`, counted on
 * their payment date. A refunded order drops out of the revenue of the period it was paid in.
 *
 * Dates are stored in UTC (the server timezone): shop-time bounds are converted before querying.
 */
readonly class DashboardMetrics implements DashboardMetricsInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function ordersToProcess(int $limit = 10): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o', 'buyer')
            ->from(Order::class, 'o')
            ->leftJoin('o.buyer', 'buyer')
            ->where('o.status IN (:statuses)')
            ->orderBy('o.created_at', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('statuses', array_map(static fn ($status) => $status->value, self::TO_PROCESS))
            ->getQuery()
            ->getResult();
    }

    public function countOrdersToProcess(): array
    {
        $counts = array_fill_keys(array_map(static fn ($status) => $status->value, self::TO_PROCESS), 0);

        $rows = $this->entityManager->createQueryBuilder()
            ->select('o.status AS status', 'COUNT(o.id) AS total')
            ->from(Order::class, 'o')
            ->where('o.status IN (:statuses)')
            ->groupBy('o.status')
            ->setParameter('statuses', array_keys($counts))
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $counts[self::value($row['status'])] = (int) $row['total'];
        }

        return $counts;
    }

    public function revenue(RevenuePeriod $period, \DateTimeImmutable $now): RevenueFigure
    {
        [$amount, $count] = $this->collected(...$period->currentBounds($now));
        [$previousAmount] = $this->collected(...$period->previousBounds($now));

        return new RevenueFigure($period, $amount, $count, $previousAmount);
    }

    public function paymentBreakdown(\DateTimeImmutable $since): array
    {
        $breakdown = [];
        foreach (PaymentStatus::cases() as $status) {
            $breakdown[$status->value] = ['count' => 0, 'amount' => 0];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('o.paymentStatus AS status', 'COUNT(o.id) AS total', 'COALESCE(SUM(o.totalPrice), 0) AS amount')
            ->from(Order::class, 'o')
            ->where('o.created_at >= :since')
            ->groupBy('o.paymentStatus')
            ->setParameter('since', self::utc($since))
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $breakdown[self::value($row['status'])] = ['count' => (int) $row['total'], 'amount' => (int) $row['amount']];
        }

        return $breakdown;
    }

    /**
     * @return array{int, int} [amount in cents, number of orders]
     */
    private function collected(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $row = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(o.totalPrice), 0) AS amount', 'COUNT(o.id) AS total')
            ->from(Order::class, 'o')
            ->where('o.paymentStatus = :paid')
            ->andWhere('o.paidAt >= :start')
            ->andWhere('o.paidAt < :end')
            ->setParameter('paid', PaymentStatus::PAID->value)
            ->setParameter('start', self::utc($start))
            ->setParameter('end', self::utc($end))
            ->getQuery()
            ->getSingleResult();

        return [(int) $row['amount'], (int) $row['total']];
    }

    /**
     * Scalar results of an enum column come back as the enum or as its value depending on the hydrator.
     */
    private static function value(mixed $status): string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    }

    /**
     * Doctrine writes a DateTime in its own timezone without converting it: the columns hold UTC.
     */
    public static function utc(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone('UTC'));
    }
}
