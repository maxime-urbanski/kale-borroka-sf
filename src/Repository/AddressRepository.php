<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function save(Address $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Address $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The default address first, then in the order they were added.
     *
     * @return array<Address>
     */
    public function getAllUserAddresses(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.users = :user')
            ->setParameter('user', $user)
            ->orderBy('a.isMainAddress', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getDefaultUserAddress(User $user): ?Address
    {
        return $this->createQueryBuilder('a')
            ->where('a.users = :user')
            ->andWhere('a.isMainAddress = true')
            ->setParameter('user', $user)
            // Two parallel first addresses may both be flagged: the oldest wins, no exception.
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
