<?php

namespace App\Repository;

use App\Entity\UserCollectionArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollectionArticle>
 *
 * @method UserCollectionArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCollectionArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCollectionArticle[]    findAll()
 * @method UserCollectionArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCollectionArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCollectionArticle::class);
    }
}
