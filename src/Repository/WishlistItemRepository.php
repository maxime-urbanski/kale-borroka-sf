<?php

namespace App\Repository;

use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 *
 * @method WishlistItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method WishlistItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method WishlistItem[]    findAll()
 * @method WishlistItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
}
