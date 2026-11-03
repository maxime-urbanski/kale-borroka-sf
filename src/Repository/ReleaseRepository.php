<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Release>
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Release::class);
    }

    public function save(Release $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Release $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The label's latest productions, newest production number (KBR#024) first; an album
     * pressed several times shows its first pressing only.
     */
    public function latestProductions(int $limit): Query
    {
        $firstPressings = $this->getEntityManager()->createQueryBuilder()
            ->select('MIN(pressing.id)')
            ->from(Release::class, 'pressing')
            ->innerJoin('pressing.album', 'pressed')
            ->where('pressing.published = true')
            ->andWhere('pressed.kbrProduction = true')
            ->groupBy('pressing.album')
            ->getDQL();

        return $this->publishedQuery()
            ->addSelect('CASE WHEN album.kbrProductionId IS NULL THEN 1 ELSE 0 END AS HIDDEN unnumbered')
            ->addSelect('CASE WHEN album.date_release IS NULL THEN 1 ELSE 0 END AS HIDDEN undated')
            ->andWhere('album.kbrProduction = true')
            ->andWhere(\sprintf('release.id IN (%s)', $firstPressings))
            ->orderBy('unnumbered', 'ASC')
            // KBR#1000 after KBR#999: longer numbers first, then the text.
            ->addOrderBy('LENGTH(album.kbrProductionId)', 'DESC')
            ->addOrderBy('album.kbrProductionId', 'DESC')
            ->addOrderBy('undated', 'ASC')
            ->addOrderBy('album.date_release', 'DESC')
            ->addOrderBy('release.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();
    }

    public function getReleasesWithSameArtist(Release $release): Query
    {
        return $this->publishedQuery()
            ->andWhere('album.artist = :artist')
            ->andWhere('release != :release')
            ->setMaxResults(10)
            ->setParameter('artist', $release->getAlbum()?->getArtist())
            ->setParameter('release', $release)
            ->getQuery();
    }

    public function getReleasesWithSameStyle(Release $release): Query
    {
        return $this->publishedQuery()
            ->leftJoin('album.styles', 'styles')
            ->andWhere('styles IN (:styles)')
            ->andWhere('release != :release')
            ->setMaxResults(10)
            ->setParameter('styles', $release->getAlbum()?->getStyles())
            ->setParameter('release', $release)
            ->getQuery();
    }

    private function publishedQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('release')
            ->innerJoin('release.album', 'album')
            ->addSelect('album')
            ->where('release.published = true')
            ->orderBy('release.name', 'ASC');
    }
}
