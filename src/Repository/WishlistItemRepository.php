<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    public function save(WishlistItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WishlistItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The user's latest wantlist items, those in stock (published, stock left) first.
     *
     * @return list<WishlistItem>
     */
    public function latestOfUser(User $user, int $limit): array
    {
        return $this->createQueryBuilder('wi')
            ->addSelect('a')
            ->addSelect('CASE WHEN a.published = true AND a.stock > 0 THEN 1 ELSE 0 END AS HIDDEN inStock')
            ->join('wi.wishlist', 'w')
            ->join('wi.article', 'a')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('inStock', 'DESC')
            ->addOrderBy('wi.added_at', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
