<?php

namespace App\Repository;

use App\Entity\MediaObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaObject>
 *
 * @method MediaObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediaObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediaObject[]    findAll()
 * @method MediaObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaObject::class);
    }

    public function save(MediaObject $mediaObjectEntity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($mediaObjectEntity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MediaObject $mediaObjectEntity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($mediaObjectEntity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
