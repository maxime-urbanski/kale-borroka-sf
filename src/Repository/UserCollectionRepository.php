<?php

namespace App\Repository;

use App\Entity\UserCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollection>
 *
 * @method UserCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCollection[]    findAll()
 * @method UserCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCollection::class);
    }
}
