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

    /**
     * The other pressings of the same album (colour, edition, format).
     */
    public function getOtherPressings(Release $release): Query
    {
        return $this->publishedQuery()
            ->andWhere('release.album = :album')
            ->andWhere('release != :release')
            ->setParameter('album', $release->getAlbum())
            ->setParameter('release', $release)
            ->getQuery();
    }

    /**
     * Other records of the artist: one pressing per album, never the album being shown.
     */
    public function getReleasesWithSameArtist(Release $release): Query
    {
        return $this->otherAlbums($release, static function (QueryBuilder $qb): void {
            $qb->andWhere('pressed.artist = :artist');
        })
            ->setParameter('artist', $release->getAlbum()?->getArtist())
            ->getQuery();
    }

    public function getReleasesWithSameStyle(Release $release): Query
    {
        return $this->otherAlbums($release, static function (QueryBuilder $qb): void {
            $qb->innerJoin('pressed.styles', 'pressed_style')->andWhere('pressed_style IN (:styles)');
        })
            ->setParameter('styles', $release->getAlbum()?->getStyles())
            ->getQuery();
    }

    /**
     * The label the page shows (Release::$label for a distro pressing, otherwise the album's
     * labels), matched the same way on the other albums: one of their pressings or the album itself.
     */
    public function getReleasesWithSameLabel(Release $release): Query
    {
        $labels = null !== $release->getLabel() ? [$release->getLabel()] : $release->getAlbum()?->getLabels()->toArray() ?? [];

        return $this->otherAlbums($release, static function (QueryBuilder $qb): void {
            $qb->leftJoin('pressed.labels', 'pressed_label')
                ->andWhere('pressing.label IN (:labels) OR pressed_label IN (:labels)');
        })
            ->setParameter('labels', $labels)
            ->getQuery();
    }

    /**
     * Published releases of other albums than the release's: an album pressed several times
     * counts once (its first pressing), the pressings of the album shown have their own section.
     */
    private function otherAlbums(Release $release, callable $narrow): QueryBuilder
    {
        return $this->publishedQuery()
            ->andWhere('release.album != :album')
            ->andWhere(\sprintf('release.id IN (%s)', $this->firstPressingOfAlbumsDql($narrow)))
            ->setParameter('album', $release->getAlbum())
            ->setMaxResults(5);
    }

    /**
     * @param callable(QueryBuilder): void $narrow
     */
    private function firstPressingOfAlbumsDql(callable $narrow): string
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('MIN(pressing.id)')
            ->from(Release::class, 'pressing')
            ->innerJoin('pressing.album', 'pressed')
            ->where('pressing.published = true')
            ->groupBy('pressing.album');
        $narrow($qb);

        return $qb->getDQL();
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
