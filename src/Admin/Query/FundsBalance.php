<?php

declare(strict_types=1);

namespace App\Admin\Query;

use App\Entity\EventSale;
use App\Entity\Expense;
use App\Entity\Order;
use App\Enum\PaymentStatus;
use App\Service\ShopSettingsProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Shop payments follow the dashboard's definition of revenue (payment status `paid`, on the
 * payment date): an order refunded later drops out, whenever it had been paid.
 *
 * Event sales and expenses count from their day on (shop time): one dated later is not in
 * the available funds yet; upcoming expenses are reported apart.
 */
readonly class FundsBalance implements FundsBalanceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopSettingsProviderInterface $shopSettingsProvider,
        private ClockInterface $clock,
    ) {
    }

    public function summary(): FundsSummary
    {
        $settings = $this->shopSettingsProvider->get();
        $since = $settings->getOpeningBalanceDate();
        $today = $this->clock->now()->setTimezone(new \DateTimeZone(RevenuePeriod::TIMEZONE))->format('Y-m-d');

        return new FundsSummary(
            $settings->getOpeningBalance(),
            $since,
            $this->shopRevenue($since),
            $this->sum(EventSale::class, 'price', 'startTime', $since, 'e.startTime <= :today', $today),
            $this->sum(Expense::class, 'totalPaymentDue', 'paymentDueDate', $since, 'e.paymentDueDate <= :today', $today),
            $this->sum(Expense::class, 'totalPaymentDue', 'paymentDueDate', $since, 'e.paymentDueDate > :today', $today),
        );
    }

    private function shopRevenue(?\DateTimeImmutable $since): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(o.totalPrice), 0)')
            ->from(Order::class, 'o')
            ->where('o.paymentStatus = :paid')
            ->setParameter('paid', PaymentStatus::PAID->value);

        if (null !== $since) {
            // The opening date is a day in shop time; paid_at holds UTC.
            $start = new \DateTimeImmutable($since->format('Y-m-d'), new \DateTimeZone(RevenuePeriod::TIMEZONE));
            $queryBuilder->andWhere('o.paidAt >= :since')->setParameter('since', DashboardMetrics::utc($start));
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param class-string $entity
     * @param string       $range  condition on the date against :today
     */
    private function sum(string $entity, string $amount, string $date, ?\DateTimeImmutable $since, string $range, string $today): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('COALESCE(SUM(e.%s), 0)', $amount))
            ->from($entity, 'e')
            ->where($range)
            ->setParameter('today', $today);

        if (null !== $since) {
            $queryBuilder->andWhere(\sprintf('e.%s >= :since', $date))->setParameter('since', $since->format('Y-m-d'));
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
