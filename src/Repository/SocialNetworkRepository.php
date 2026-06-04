<?php

namespace App\Repository;

use App\Entity\SocialNetwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SocialNetwork>
 *
 * @method SocialNetwork|null find($id, $lockMode = null, $lockVersion = null)
 * @method SocialNetwork|null findOneBy(array $criteria, array $orderBy = null)
 * @method SocialNetwork[]    findAll()
 * @method SocialNetwork[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SocialNetworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialNetwork::class);
    }

    public function save(SocialNetwork $mediaObjectEntity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($mediaObjectEntity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SocialNetwork $mediaObjectEntity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($mediaObjectEntity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<SocialNetwork>
     */
    public function getAllSocialNetworkForFooter(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isPublish = true')
            ->andWhere('s.inFooter = true')
            ->getQuery()
            ->getResult();
    }
}
