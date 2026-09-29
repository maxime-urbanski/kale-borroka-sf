<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
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
