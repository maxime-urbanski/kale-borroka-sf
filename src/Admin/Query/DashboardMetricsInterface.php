<?php

declare(strict_types=1);

namespace App\Admin\Query;

use App\Entity\Order;
use App\Enum\OrderStatus;

interface DashboardMetricsInterface
{
    /**
     * Statuses of orders that still need something done: payment, preparation or shipping.
     */
    public const array TO_PROCESS = [OrderStatus::PENDING, OrderStatus::PAID, OrderStatus::PREPARING];

    /**
     * Oldest first: they are the ones waiting the longest.
     *
     * @return Order[]
     */
    public function ordersToProcess(int $limit = 10): array;

    /**
     * @return array<string, int> OrderStatus value => number of orders, for TO_PROCESS statuses
     */
    public function countOrdersToProcess(): array;

    public function revenue(RevenuePeriod $period, \DateTimeImmutable $now): RevenueFigure;

    /**
     * Orders placed since the given date, by payment status.
     *
     * @return array<string, array{count: int, amount: int}> PaymentStatus value => figures
     */
    public function paymentBreakdown(\DateTimeImmutable $since): array;
}
