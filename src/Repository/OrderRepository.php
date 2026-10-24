<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * The buyer's latest orders, newest first, with how many items each holds.
     *
     * @return list<array{order: Order, items: int}>
     */
    public function latestOfBuyer(User $buyer, int $limit): array
    {
        /** @var list<array{0: Order, items: int|string}> $rows */
        $rows = $this->createQueryBuilder('o')
            ->select('o', 'COALESCE(SUM(d.quantity), 0) AS items')
            ->leftJoin('o.orderDetails', 'd')
            ->where('o.buyer = :buyer')
            ->setParameter('buyer', $buyer)
            ->groupBy('o.id')
            ->orderBy('o.created_at', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): array => ['order' => $row[0], 'items' => (int) $row['items']], $rows);
    }

    /**
     * The buyer's orders still moving (OrderStatus::ongoing()), newest first, with their lines.
     *
     * @return list<Order>
     */
    public function ongoingOfBuyer(User $buyer): array
    {
        /* @var list<Order> */
        return $this->ofBuyer($buyer, OrderStatus::ongoing())
            ->addSelect('d', 'p')
            ->leftJoin('o.orderDetails', 'd')
            ->leftJoin('d.product', 'p')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * The buyer's finished orders (OrderStatus::finished()), newest first: a query for the
     * paginator, without their lines (loadLines() once the page is known).
     */
    public function finishedOfBuyerQuery(User $buyer): Query
    {
        return $this->ofBuyer($buyer, OrderStatus::finished())->getQuery();
    }

    /**
     * Loads the lines of these orders and their articles in one query.
     *
     * @param list<Order> $orders
     */
    public function loadLines(array $orders): void
    {
        if ([] === $orders) {
            return;
        }

        $this->createQueryBuilder('o')
            ->addSelect('d', 'p')
            ->leftJoin('o.orderDetails', 'd')
            ->leftJoin('d.product', 'p')
            ->where('o IN (:orders)')
            ->setParameter('orders', $orders)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<OrderStatus> $statuses
     */
    private function ofBuyer(User $buyer, array $statuses): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->where('o.buyer = :buyer')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('buyer', $buyer)
            ->setParameter('statuses', $statuses)
            ->orderBy('o.created_at', 'DESC')
            ->addOrderBy('o.id', 'DESC');
    }
}
