<?php

declare(strict_types=1);

namespace App\Tests\Admin\Query;

use App\Admin\Query\DashboardMetricsInterface;
use App\Admin\Query\FinancialReportInterface;
use App\Admin\Query\RevenuePeriod;
use App\Entity\EventSale;
use App\Entity\Expense;
use App\Entity\Order;
use App\Enum\ExpenseCategory;
use App\Enum\OrderTransition;
use App\Order\Command\ApplyOrderTransition;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Revenue = orders whose payment status is `paid`, on their payment date, in Paris time.
 */
class FinancialFiguresTest extends KernelTestCase
{
    use OrderTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
        // Start from a clean slate so that other orders in the test database do not count.
        $this->entityManager()->getConnection()->executeStatement('UPDATE "order" SET paid_at = NULL, payment_status = \'awaiting\'');
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testRevenueCountsPaidOrdersOnTheirPaymentDayInParisTime(): void
    {
        // 23:30 UTC on the 29th = 01:30 on the 30th in Paris: today's revenue.
        $this->paidOrder(2500, '2026-09-29 23:30:00');
        // 07:00 UTC on the 29th = 09:00 in Paris: yesterday, before the same hour.
        $this->paidOrder(1000, '2026-09-29 07:00:00');
        // Refunded: not revenue any more.
        $this->paidOrder(9900, '2026-09-30 06:00:00', refunded: true);

        $now = new \DateTimeImmutable('2026-09-30 10:00', new \DateTimeZone('Europe/Paris'));
        $today = $this->metrics()->revenue(RevenuePeriod::DAY, $now);

        self::assertSame(2500, $today->amount);
        self::assertSame(1, $today->orderCount);
        self::assertSame(1000, $today->previousAmount);
        self::assertSame(150.0, $today->variation());

        $week = $this->metrics()->revenue(RevenuePeriod::WEEK, $now);
        self::assertSame(3500, $week->amount);
    }

    public function testMonthlyReport(): void
    {
        // 31 January 23:30 UTC is 1 February in Paris.
        $this->paidOrder(1500, '2026-01-31 23:30:00');
        $this->paidOrder(2000, '2026-01-15 12:00:00');
        $this->paidOrder(4000, '2026-01-20 12:00:00', refunded: true);
        // Other year.
        $this->paidOrder(7000, '2025-12-31 12:00:00');
        // Event sales and expenses are dated by day: only the test's own count.
        $connection = $this->entityManager()->getConnection();
        $connection->executeStatement('DELETE FROM event_sale');
        $connection->executeStatement('DELETE FROM expense');
        $this->entityManager()->persist((new EventSale())->setName('Fest')->setStartTime(new \DateTimeImmutable('2026-01-31'))->setPrice(30000));
        $this->entityManager()->persist((new EventSale())->setName('Réveillon')->setStartTime(new \DateTimeImmutable('2025-12-31'))->setPrice(99900));
        $this->entityManager()->persist((new Expense())->setName('Pressage')->setCategory(ExpenseCategory::PRODUCTION)->setPaymentDueDate(new \DateTimeImmutable('2026-02-01'))->setTotalPaymentDue(120000));
        $this->entityManager()->flush();

        $months = self::getContainer()->get(FinancialReportInterface::class)->monthly(2026);

        self::assertCount(12, $months);
        self::assertSame(['month' => '2026-01', 'orders' => 1, 'revenue' => 2000, 'refunded' => 4000, 'eventSales' => 30000, 'expenses' => 0], self::row($months[0]));
        self::assertSame(['month' => '2026-02', 'orders' => 1, 'revenue' => 1500, 'refunded' => 0, 'eventSales' => 0, 'expenses' => 120000], self::row($months[1]));
        self::assertSame(0, $months[11]['orders']);
    }

    public function testYearsStartFromTheFirstPaymentInParisTime(): void
    {
        // 31 December 2019 23:30 UTC is 1 January 2020 in Paris: 2019 has no payment.
        $this->paidOrder(1500, '2019-12-31 23:30:00');

        $years = self::getContainer()->get(FinancialReportInterface::class)->years(new \DateTimeImmutable('2026-06-15 12:00:00'));

        self::assertSame(2026, $years[0]);
        self::assertSame(2020, end($years));
    }

    public function testPaymentsExportListsPaidAndRefundedOrders(): void
    {
        $paid = $this->paidOrder(1500, '2026-03-10 12:00:00');
        $refunded = $this->paidOrder(4000, '2026-03-11 12:00:00', refunded: true);
        $this->paidOrder(1000, '2025-03-10 12:00:00');

        $rows = iterator_to_array(self::getContainer()->get(FinancialReportInterface::class)->payments(2026), false);

        self::assertSame([$paid->getReference(), $refunded->getReference()], array_column($rows, 'reference'));
        self::assertSame('10/03/2026 13:00', $rows[0]['paidAt']->format('d/m/Y H:i'), 'in Paris time');
    }

    private function metrics(): DashboardMetricsInterface
    {
        return self::getContainer()->get(DashboardMetricsInterface::class);
    }

    /**
     * Pays an order through the workflow, then moves its payment date (UTC) where the test needs it.
     */
    private function paidOrder(int $total, string $paidAtUtc, bool $refunded = false): Order
    {
        [$release] = $this->releasesWithStock(10);
        $order = $this->placeOrder($this->user('test@test.fr'), [$release->getId() => 1]);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));

        if ($refunded) {
            $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::CANCEL));
        }

        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE "order" SET paid_at = ?, total_price = ? WHERE id = ?',
            [$paidAtUtc, $total, $order->getId()],
        );
        // The entity in memory still holds the payment date set by the workflow.
        $this->entityManager()->clear();

        return $order;
    }

    /**
     * @param array{month: \DateTimeImmutable, orders: int, revenue: int, refunded: int} $row
     *
     * @return array{month: string, orders: int, revenue: int, refunded: int}
     */
    private static function row(array $row): array
    {
        return ['month' => $row['month']->format('Y-m')] + $row;
    }
}
