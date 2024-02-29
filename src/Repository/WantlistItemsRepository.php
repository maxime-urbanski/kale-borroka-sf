<?php

namespace App\Repository;

use App\Entity\WantlistItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WantlistItems>
 *
 * @method WantlistItems|null find($id, $lockMode = null, $lockVersion = null)
 * @method WantlistItems|null findOneBy(array $criteria, array $orderBy = null)
 * @method WantlistItems[]    findAll()
 * @method WantlistItems[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WantlistItemsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WantlistItems::class);
    }

//    /**
//     * @return WantlistItems[] Returns an array of WantlistItems objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WantlistItems
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
