<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\MerchVariant;
use App\Entity\Release;
use App\Enum\HomeShelf;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Queries that apply to every kind of article. Catalogue queries, which need subclass
 * fields, live in ReleaseRepository and BookRepository.
 *
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * The cart's articles with everything its lines show, in a fixed number of queries
     * whatever their count: their pictures, and the pictures of their parent (the album of
     * a release, the design of a merch variant), used when they have none of their own.
     *
     * @param list<int> $ids
     *
     * @return list<Article>
     */
    public function findForCart(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<Article> $articles */
        $articles = $this->createQueryBuilder('article')
            ->addSelect('image')
            ->leftJoin('article.images', 'image')
            ->where('article.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Loaded into the same entity manager: the lines then read them without a query.
        foreach ([
            Release::class => 'SELECT article, parent, parentImage FROM %s article JOIN article.album parent LEFT JOIN parent.images parentImage WHERE article IN (:articles)',
            MerchVariant::class => 'SELECT article, parent, parentImage FROM %s article JOIN article.merch parent LEFT JOIN parent.images parentImage WHERE article IN (:articles)',
        ] as $class => $dql) {
            $ofClass = array_values(array_filter($articles, static fn (Article $article): bool => $article instanceof $class));

            if ([] !== $ofClass) {
                $this->getEntityManager()->createQuery(\sprintf($dql, $class))->setParameter('articles', $ofClass)->getResult();
            }
        }

        return $articles;
    }

    /**
     * Loads the pictures of already loaded articles and their parent (album and artist of a
     * release, design of a merch variant) into the entity manager, as findForCart() does:
     * a cover and its title then read them without a query.
     *
     * @param list<Article> $articles
     */
    public function preloadCovers(array $articles): void
    {
        if ([] === $articles) {
            return;
        }

        $this->createQueryBuilder('article')
            ->addSelect('image')
            ->leftJoin('article.images', 'image')
            ->where('article IN (:articles)')
            ->setParameter('articles', $articles)
            ->getQuery()
            ->getResult();

        foreach ([
            Release::class => 'SELECT article, parent, parentImage, artist FROM %s article JOIN article.album parent LEFT JOIN parent.images parentImage LEFT JOIN parent.artist artist WHERE article IN (:articles)',
            MerchVariant::class => 'SELECT article, parent, parentImage FROM %s article JOIN article.merch parent LEFT JOIN parent.images parentImage WHERE article IN (:articles)',
        ] as $class => $dql) {
            $ofClass = array_values(array_filter($articles, static fn (Article $article): bool => $article instanceof $class));

            if ([] !== $ofClass) {
                $this->getEntityManager()->createQuery(\sprintf($dql, $class))->setParameter('articles', $ofClass)->getResult();
            }
        }
    }

    /**
     * Stock of the published ones among these articles, by id: one scalar query, no entity
     * loaded (the navbar counts the cart on every page).
     *
     * @param list<int> $ids
     *
     * @return array<int, int> id => stock; unpublished and deleted articles are left out
     */
    public function stockOfPublished(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<array{id: int, stock: int|null}> $rows */
        $rows = $this->createQueryBuilder('article')
            ->select('article.id', 'article.stock')
            ->where('article.id IN (:ids)')
            ->andWhere('article.published = true')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $stock = [];
        foreach ($rows as $row) {
            $stock[(int) $row['id']] = (int) $row['stock'];
        }

        return $stock;
    }

    /**
     * Newest published articles of a homepage shelf. No merch: a t-shirt has no page of its
     * own to link to yet (CatalogExtension::articlePath()).
     */
    public function latest(HomeShelf $shelf, int $limit): Query
    {
        $class = match ($shelf) {
            HomeShelf::VINYL, HomeShelf::CD, HomeShelf::TAPE => Release::class,
            HomeShelf::ZINE => Book::class,
            HomeShelf::ALL, HomeShelf::USED => Article::class,
        };

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('article')
            ->from($class, 'article')
            ->where('article.published = true')
            ->orderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->setMaxResults($limit);

        match ($shelf) {
            HomeShelf::VINYL => $query->andWhere('article.format IN (:formats)')->setParameter('formats', array_map(
                static fn (ReleaseFormat $format): string => $format->value,
                array_filter(ReleaseFormat::cases(), static fn (ReleaseFormat $format): bool => null !== $format->size()),
            )),
            HomeShelf::CD => $query->andWhere('article.format = :format')->setParameter('format', ReleaseFormat::CD->value),
            HomeShelf::TAPE => $query->andWhere('article.format = :format')->setParameter('format', ReleaseFormat::CASSETTE->value),
            HomeShelf::USED => $query->andWhere('article.itemCondition = :condition')->setParameter('condition', ItemCondition::USED->value),
            HomeShelf::ALL, HomeShelf::ZINE => null,
        };

        // What a record's card reads, loaded with it; the root query cannot reach subclass fields.
        if (Release::class === $class) {
            $query
                ->leftJoin('article.album', 'album')
                ->leftJoin('album.artist', 'artist')
                ->leftJoin('article.label', 'label')
                ->addSelect('album', 'artist', 'label');
        }

        if (Article::class === $class) {
            $query->andWhere(\sprintf('article NOT INSTANCE OF %s', MerchVariant::class));
        }

        return $query->getQuery();
    }

    public function findPublishedBySlug(string $slug): ?Article
    {
        return $this->findOneBy(['slug' => $slug, 'published' => true]);
    }

    /**
     * Published articles at or below the threshold, emptiest first.
     *
     * @return Article[]
     */
    public function findLowStock(int $threshold, int $limit = 20): array
    {
        return $this->createQueryBuilder('article')
            ->where('article.published = true')
            ->andWhere('article.stock <= :threshold')
            ->orderBy('article.stock', 'ASC')
            ->addOrderBy('article.name', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
