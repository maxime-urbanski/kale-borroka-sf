<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
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
    public function getUserCollectionItem(Article $article, UserCollection $userCollection): ?UserCollectionItems
    {
        return $this->createQueryBuilder('uci')
            ->where('uci.article = :article')
            ->andWhere('uci.collection = :userCollection')
            ->setParameter('article', $article)
            ->setParameter('userCollection', $userCollection)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The user's latest collection items.
     *
     * @return list<UserCollectionItems>
     */
    public function latestOfUser(User $user, ?int $limit): array
    {
        return $this->createQueryBuilder('ci')
            ->addSelect('a')
            ->join('ci.collection', 'c')
            ->join('ci.article', 'a')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ci.added_at', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Every collection item of the user, the latest added first.
     *
     * @return list<UserCollectionItems>
     */
    public function allOfUser(User $user): array
    {
        return $this->latestOfUser($user, null);
    }
}
