<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\UserCollection;
use App\Entity\UserCollectionItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollectionItems>
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

    /**
     * @throws NonUniqueResultException
     */
    public function getUserCollectionItem(Article $article, UserCollection $userCollection): UserCollectionItems
    {
        return $this->createQueryBuilder('uci')
            ->where('uci.article = :article')
            ->andWhere('uci.collection = :userCollection')
            ->setParameter('article', $article)
            ->setParameter('userCollection', $userCollection)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
