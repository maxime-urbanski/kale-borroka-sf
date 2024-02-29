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

//    /**
//     * @return UserCollectionItems[] Returns an array of UserCollectionItems objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserCollectionItems
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
