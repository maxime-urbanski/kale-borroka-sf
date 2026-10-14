<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FeaturedRelease;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeaturedRelease>
 */
class FeaturedReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeaturedRelease::class);
    }

    public function save(FeaturedRelease $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FeaturedRelease $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The feature of the homepage: online, within its dates, about a published release.
     * The latest scheduled one wins, then the latest created: an undated feature is the
     * fallback once a dated one starts.
     */
    public function findCurrent(\DateTimeImmutable $now): ?FeaturedRelease
    {
        /* @var FeaturedRelease|null */
        return $this->createQueryBuilder('feature')
            ->addSelect('release', 'album', 'artist')
            ->addSelect('CASE WHEN feature.datePublished IS NULL THEN 1 ELSE 0 END AS HIDDEN undated')
            ->innerJoin('feature.release', 'release')
            ->leftJoin('release.album', 'album')
            ->leftJoin('album.artist', 'artist')
            ->andWhere('feature.published = true')
            ->andWhere('release.published = true')
            ->andWhere('feature.datePublished IS NULL OR feature.datePublished <= :now')
            ->andWhere('feature.expires IS NULL OR feature.expires > :now')
            ->setParameter('now', $now)
            ->orderBy('undated', 'ASC')
            ->addOrderBy('feature.datePublished', 'DESC')
            ->addOrderBy('feature.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
