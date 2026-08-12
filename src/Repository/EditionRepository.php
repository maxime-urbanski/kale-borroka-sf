<?php

declare(strict_types=1);

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Album;
use App\Entity\Edition;
use App\Entity\Support;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The catalogue is browsed by Edition, not by Article: one card per pressing, so two
 * offers of the same pressing (new and second-hand) do not show up as duplicate cards.
 *
 * @extends ServiceEntityRepository<Edition>
 */
class EditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Edition::class);
    }

    public function save(Edition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Edition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getOwnProduction(bool $forHome = false): Query
    {
        $query = $this->baseQuery()
            ->andWhere('album.kbrProduction = true');

        if ($forHome) {
            $query->setMaxResults(8);
        }

        return $query->getQuery();
    }

    public function filterEditionQuery(ArticleFilterData $filterData): Query
    {
        $query = $this->baseQuery();

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

    /**
     * Other records by the same artist. Sibling editions of the same album are excluded:
     * a red and a black pressing of the record you are already looking at are not a
     * "related product".
     */
    public function getEditionWithSameArtist(Edition $edition): Query
    {
        return $this->baseQuery()
            ->andWhere('album.artist = :artist')
            ->andWhere('edition.album != :album')
            ->setMaxResults(10)
            ->setParameter('artist', $edition->getAlbum()?->getArtist())
            ->setParameter('album', $edition->getAlbum())
            ->getQuery();
    }

    public function getEditionWithSameStyle(Edition $edition): Query
    {
        return $this->baseQuery()
            ->leftJoin('album.styles', 'styles')
            ->andWhere('styles IN (:styles)')
            ->andWhere('edition.album != :album')
            ->setMaxResults(10)
            ->setParameter('styles', $edition->getAlbum()?->getStyles())
            ->setParameter('album', $edition->getAlbum())
            ->getQuery();
    }

    public function getLastEdition(): Query
    {
        return $this->baseQuery()
            ->setMaxResults(8)
            ->getQuery();
    }

    /**
     * Resolves the album page: the {support} URL segment picks which edition is
     * pre-selected, the album slug identifies the record.
     *
     * @throws NonUniqueResultException
     */
    public function findOneBySupportAndAlbumSlug(string $support, string $slug): ?Edition
    {
        return $this->baseQuery()
            ->andWhere('support.name = :support')
            ->andWhere('album.slug = :slug')
            ->setMaxResults(1)
            ->setParameter('support', $support)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Edition[]
     */
    public function findForAlbum(Album $album): array
    {
        return $this->baseQuery()
            ->andWhere('edition.album = :album')
            ->setParameter('album', $album)
            ->getQuery()
            ->getResult();
    }

    public function countForSupport(Support $support): int
    {
        return (int) $this->createQueryBuilder('edition')
            ->select('COUNT(edition.id)')
            ->andWhere('edition.support = :support')
            ->setParameter('support', $support)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Editions are always read with their album and support: every catalogue template
     * reads through them, so joining here avoids an N+1 on every listing.
     */
    private function baseQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('edition')
            ->addSelect('album', 'support')
            ->join('edition.album', 'album')
            ->join('edition.support', 'support')
            ->orderBy('album.name', 'ASC')
            ->addOrderBy('edition.name', 'ASC');
    }
}
