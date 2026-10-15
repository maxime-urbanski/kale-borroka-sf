<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
