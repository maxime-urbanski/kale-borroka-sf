<?php

declare(strict_types=1);

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Release;
use App\Entity\Support;
use App\Enum\AlbumReleaseType;
use App\Enum\ReleaseFormat;
use App\Enum\SupportType;
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

    public function getOwnProduction(bool $forHome = false): Query
    {
        $query = $this->publishedQuery()
            ->andWhere('album.kbrProduction = true');

        if ($forHome) {
            $query->setMaxResults(8);
        }

        return $query->getQuery();
    }

    public function filterReleaseQuery(ArticleFilterData $filterData): Query
    {
        $query = $this->publishedQuery();

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
            $query->andWhere('album.kbrProduction = true');
        }

        if (!empty($filterData->supports)) {
            $this->applySupports(
                $query,
                array_map(static fn (Support $support): ?SupportType => $support->getCode(), $filterData->supports),
            );
        }

        return $query->getQuery();
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

    public function getLastReleases(): Query
    {
        return $this->publishedQuery()
            ->setMaxResults(8)
            ->getQuery();
    }

    /**
     * Restricts the query to releases listed under at least one of the given sections.
     * Must stay consistent with SupportType::forRelease(): a release is always listed
     * under its canonical section.
     *
     * @param array<SupportType|null> $supportTypes
     */
    public function applySupports(QueryBuilder $query, array $supportTypes): QueryBuilder
    {
        $conditions = $query->expr()->orX();

        foreach (array_unique(array_filter($supportTypes), \SORT_REGULAR) as $supportType) {
            $key = $supportType->value;

            match ($supportType) {
                SupportType::LP => $conditions->add(\sprintf(
                    '(release.format IN (:formats_%1$s) AND album.releaseType != :release_type_%1$s)', $key,
                )),
                SupportType::EP => $conditions->add(\sprintf(
                    '(release.format IN (:formats_%1$s) OR album.releaseType = :release_type_%1$s)', $key,
                )),
                SupportType::CD, SupportType::TAPE => $conditions->add(\sprintf('release.format IN (:formats_%s)', $key)),
                // Fanzines are Books, not Releases.
                SupportType::FANZINE => $conditions->add('1 = 0'),
            };

            match ($supportType) {
                SupportType::LP => $query
                    ->setParameter('formats_lp', [ReleaseFormat::VINYL_12->value, ReleaseFormat::VINYL_10->value])
                    ->setParameter('release_type_lp', AlbumReleaseType::EP->value),
                SupportType::EP => $query
                    ->setParameter('formats_ep', [ReleaseFormat::VINYL_7->value])
                    ->setParameter('release_type_ep', AlbumReleaseType::EP->value),
                SupportType::CD => $query->setParameter('formats_cd', [ReleaseFormat::CD->value]),
                SupportType::TAPE => $query->setParameter('formats_tape', [ReleaseFormat::CASSETTE->value]),
                SupportType::FANZINE => null,
            };
        }

        if ($conditions->count() > 0) {
            $query->andWhere($conditions);
        }

        return $query;
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
