<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Edition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Article is the offer. Browsing and filtering the catalogue happens one level up, on
 * Edition — see EditionRepository.
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
     * Offers of one edition, cheapest first — the order the product page lists them in.
     *
     * @return Article[]
     */
    public function findOffersForEdition(Edition $edition): array
    {
        return $this->createQueryBuilder('article')
            ->andWhere('article.edition = :edition')
            ->orderBy('article.price', 'ASC')
            ->setParameter('edition', $edition)
            ->getQuery()
            ->getResult();
    }

    /**
     * Offers running low, for the admin dashboard.
     *
     * @return Article[]
     */
    public function findLowStock(int $threshold = 3): array
    {
        return $this->createQueryBuilder('article')
            ->addSelect('edition', 'album')
            ->join('article.edition', 'edition')
            ->join('edition.album', 'album')
            ->andWhere('article.quantity <= :threshold')
            ->orderBy('article.quantity', 'ASC')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
