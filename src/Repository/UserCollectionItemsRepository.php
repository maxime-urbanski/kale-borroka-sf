<?php

namespace App\Repository;

use App\Entity\UserCollectionItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollectionItems>
 *
 * @method UserCollectionItems|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCollectionItems|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCollectionItems[]    findAll()
 * @method UserCollectionItems[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCollectionItemsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCollectionItems::class);
    }

    public function save(UserCollectionItems $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserCollectionItems $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
