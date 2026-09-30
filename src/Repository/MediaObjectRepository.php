<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MediaObject;
use App\Entity\SocialNetwork;
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

    /**
     * Names of the social networks using this file as their icon.
     *
     * @return string[]
     */
    public function findUsages(MediaObject $mediaObject): array
    {
        return array_map(
            static fn (SocialNetwork $socialNetwork): string => (string) $socialNetwork->getName(),
            $this->getEntityManager()->getRepository(SocialNetwork::class)->findBy(['file' => $mediaObject]),
        );
    }
}
