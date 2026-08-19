<?php

declare(strict_types=1);

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function save(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The catalogue grid lists albums, not pressings: a record available on black and red
     * vinyl is one card, and the edition picker takes over on the product page.
     */
    public function filterAlbumQuery(ArticleFilterData $filterData): Query
    {
        $query = $this->listQuery();

        if (!empty($filterData->artists)) {
            $query
                ->andWhere('album.artist IN (:artists)')
                ->setParameter('artists', $filterData->artists);
        }

        if (!empty($filterData->labels)) {
            $query
                ->leftJoin('album.labels', 'labels')
                ->andWhere('labels IN (:labels)')
                ->setParameter('labels', $filterData->labels);
        }

        if (!empty($filterData->styles)) {
            $query
                ->leftJoin('album.styles', 'styles')
                ->andWhere('styles IN (:styles)')
                ->setParameter('styles', $filterData->styles);
        }

        if ($filterData->kbrProduction) {
            $query
                ->andWhere('album.kbrProduction = :kbrProduction')
                ->setParameter('kbrProduction', $filterData->kbrProduction);
        }

        if (!empty($filterData->supports)) {
            $query
                ->andWhere('edition.support IN (:supports)')
                ->setParameter('supports', $filterData->supports);
        }

        return $query->getQuery();
    }

    public function getOwnProduction(bool $forHome = false): Query
    {
        $query = $this->listQuery()
            ->andWhere('album.kbrProduction = true');

        if ($forHome) {
            $query->setMaxResults(8);
        }

        return $query->getQuery();
    }

    public function getLastAlbums(): Query
    {
        return $this->listQuery()
            ->setMaxResults(8)
            ->getQuery();
    }

    /**
     * Other records by the same artist — the album being viewed excluded, so its own
     * pressings never show up as "related".
     */
    public function getAlbumWithSameArtist(Album $album): Query
    {
        return $this->listQuery()
            ->andWhere('album.artist = :artist')
            ->andWhere('album != :album')
            ->setMaxResults(10)
            ->setParameter('artist', $album->getArtist())
            ->setParameter('album', $album)
            ->getQuery();
    }

    public function getAlbumWithSameStyle(Album $album): Query
    {
        return $this->listQuery()
            ->leftJoin('album.styles', 'styles')
            ->andWhere('styles IN (:styles)')
            ->andWhere('album != :album')
            ->setMaxResults(10)
            ->setParameter('styles', $album->getStyles())
            ->setParameter('album', $album)
            ->getQuery();
    }

    /**
     * Only albums with at least one pressing are listed, and DISTINCT collapses the rows
     * the edition join would otherwise multiply.
     */
    private function listQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('album')
            ->distinct()
            ->join('album.editions', 'edition')
            ->orderBy('album.name', 'ASC');
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function numberOfAlbumsProduced(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.kbrProduction = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
