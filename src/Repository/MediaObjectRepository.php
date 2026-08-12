<?php

namespace App\Repository;

use App\Entity\MediaObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaObject>
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
