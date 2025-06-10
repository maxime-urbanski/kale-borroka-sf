<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    public function save(UserCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getUserCollection(User $user): Query
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.user = :user')
            ->setParameters([
                'user' => $user,
            ])
            ->getQuery();
    }
}
