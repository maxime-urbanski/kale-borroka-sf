<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Album;
use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Release;
use App\Enum\AlbumReleaseType;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use App\Enum\SupportType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * The catalogue's articles: published records with a format, and fanzines (Book); never
 * merch. Every query starts from one row per article (a), its release (r) and album (al)
 * or its book (b), all to-one joins (plus the artist, ar, for the A–Z sort), and filters
 * through subqueries: no row is repeated,
 * so counts and pagination need no DISTINCT.
 *
 * The sections follow SupportType::forRelease() (inSection()):
 * a record can sit under two of them (a CD EP under CD and EP).
 */
final readonly class CatalogQuery
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * The page's results, sorted, for the paginator.
     */
    public function articles(CatalogFilters $filters): Query
    {
        $query = $this->filtered($filters)->select('a');

        match ($filters->sort) {
            CatalogSort::NEW => $query->orderBy('a.createdAt', 'DESC'),
            CatalogSort::PRICE_ASC => $query->orderBy('a.price', 'ASC'),
            CatalogSort::PRICE_DESC => $query->orderBy('a.price', 'DESC'),
            // As the card reads: its title (the artist, a fanzine's own name), then its subtitle.
            CatalogSort::AZ => $query->leftJoin('al.artist', 'ar')
                ->addSelect('LOWER(COALESCE(ar.name, a.name)) AS HIDDEN sortName', 'LOWER(a.name) AS HIDDEN sortTitle')
                ->orderBy('sortName', 'ASC')
                ->addOrderBy('sortTitle', 'ASC'),
        };

        return $query->addOrderBy('a.id', 'DESC')->getQuery();
    }

    public function count(CatalogFilters $filters): int
    {
        return (int) $this->filtered($filters)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * Loads, in one query each, what the cards of these articles read (album, artist,
     * labels, styles, covers; a fanzine's publisher) instead of one query per card.
     *
     * @param iterable<mixed> $articles
     */
    public function prime(iterable $articles): void
    {
        $ids = [];
        foreach ($articles as $article) {
            if ($article instanceof Article) {
                $ids[] = $article->getId();
            }
        }

        if ([] === $ids) {
            return;
        }

        // One collection per query: fetch-joining them all would multiply the rows.
        $this->entityManager->createQueryBuilder()
            ->select('r', 'ri', 'al', 'ar')
            ->from(Release::class, 'r')
            ->leftJoin('r.images', 'ri')
            ->leftJoin('r.album', 'al')
            ->leftJoin('al.artist', 'ar')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        foreach (['labels', 'styles', 'images'] as $collection) {
            $this->entityManager->createQueryBuilder()
                ->select('al', 'c')
                ->from(Album::class, 'al')
                ->leftJoin('al.'.$collection, 'c')
                ->where(\sprintf('al.id IN (SELECT IDENTITY(r_%1$s.album) FROM %2$s r_%1$s WHERE r_%1$s.id IN (:ids))', $collection, Release::class))
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
        }

        $this->entityManager->createQueryBuilder()
            ->select('b', 'bi', 'p')
            ->from(Book::class, 'b')
            ->leftJoin('b.images', 'bi')
            ->leftJoin('b.publisher', 'p')
            ->where('b.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * The catalogue narrowed by every group of filters but $except (a facet counts its own
     * values with the other groups only), without select nor order.
     */
    public function filtered(CatalogFilters $filters, ?CatalogFacet $except = null): QueryBuilder
    {
        $query = $this->entityManager->createQueryBuilder()
            ->from(Article::class, 'a')
            ->leftJoin(Release::class, 'r', 'WITH', 'r.id = a.id')
            ->leftJoin('r.album', 'al')
            ->leftJoin(Book::class, 'b', 'WITH', 'b.id = a.id')
            ->where('a.published = true')
            ->andWhere('(r.id IS NOT NULL AND r.format IS NOT NULL) OR b.id IS NOT NULL');

        $sections = $filters->sections();
        if ([] !== $sections && CatalogFacet::SUPPORTS !== $except) {
            $query->andWhere($query->expr()->orX(...array_map(fn (SupportType $section): string => $this->inSection($query, $section), $sections)));
        }

        if ([] !== $filters->styles && CatalogFacet::STYLES !== $except) {
            $query
                ->andWhere(\sprintf('al.id IN (SELECT style_album.id FROM %s style_release JOIN style_release.album style_album JOIN style_album.styles style WHERE style IN (:styles))', Release::class))
                ->setParameter('styles', $filters->styles);
        }

        if ([] !== $filters->labels && CatalogFacet::LABELS !== $except) {
            $query
                ->andWhere(\sprintf('al.id IN (SELECT label_album.id FROM %s label_release JOIN label_release.album label_album JOIN label_album.labels label WHERE label IN (:labels)) OR b.publisher IN (:labels)', Release::class))
                ->setParameter('labels', $filters->labels);
        }

        if ([] !== $filters->artists && CatalogFacet::ARTISTS !== $except) {
            $query->andWhere('al.artist IN (:artists)')->setParameter('artists', $filters->artists);
        }

        if ([] !== $filters->conditions && CatalogFacet::CONDITIONS !== $except) {
            $query
                ->andWhere('a.itemCondition IN (:conditions)')
                ->setParameter('conditions', array_map(static fn (ItemCondition $condition): string => $condition->value, $filters->conditions));
        }

        if ($filters->productions || ($filters->kbrProduction && CatalogFacet::KBR_PRODUCTION !== $except)) {
            $query->andWhere('al.kbrProduction = true');
        }

        if ($filters->hideSoldOut && CatalogFacet::HIDE_SOLD_OUT !== $except) {
            $query->andWhere('a.stock > 0');
        }

        return $query;
    }

    /**
     * DQL condition of an article listed under $section, its parameters set on $query. Must
     * stay consistent with SupportType::forRelease(): a record is always listed under the
     * section of its own URL (ReleaseRepositoryTest).
     */
    public function inSection(QueryBuilder $query, SupportType $section): string
    {
        $key = 'section_'.$section->value;

        switch ($section) {
            case SupportType::LP:
                $query->setParameter($key, [ReleaseFormat::VINYL_12->value, ReleaseFormat::VINYL_10->value])
                    ->setParameter($key.'_type', AlbumReleaseType::EP->value);

                return \sprintf('(r.format IN (:%1$s) AND al.releaseType != :%1$s_type)', $key);
            case SupportType::EP:
                $query->setParameter($key, [ReleaseFormat::VINYL_7->value])
                    ->setParameter($key.'_type', AlbumReleaseType::EP->value);

                return \sprintf('(r.format IN (:%1$s) OR al.releaseType = :%1$s_type)', $key);
            case SupportType::CD:
                $query->setParameter($key, [ReleaseFormat::CD->value]);

                return \sprintf('r.format IN (:%s)', $key);
            case SupportType::TAPE:
                $query->setParameter($key, [ReleaseFormat::CASSETTE->value]);

                return \sprintf('r.format IN (:%s)', $key);
            case SupportType::FANZINE:
                return 'b.id IS NOT NULL';
        }
    }
}
