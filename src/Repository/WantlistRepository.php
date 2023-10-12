<?php

namespace App\Repository;

use App\Entity\Wantlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wantlist>
 *
 * @method Wantlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wantlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wantlist[]    findAll()
 * @method Wantlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WantlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wantlist::class);
    }
}
