<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\MerchVariant;
use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
