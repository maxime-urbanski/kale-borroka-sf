<?php

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Data\ProductionFilterData;
use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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

    public function getOwnProduction(bool $forHome = false): Query
    {
        $query = $this->createQueryBuilder('article')
            ->select()
            ->leftJoin('article.album', 'album')
            ->where('album.kbrProduction = true');

        if ($forHome) $query
            ->orderBy('article.name', 'ASC')
            ->orderBy('article.createdAt', 'DESC')
            ->setMaxResults(8);

        return $query->getQuery();
    }

    public function filterArticleQuery(ProductionFilterData|ArticleFilterData $filterData): Query
    {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->orderBy('article.name', 'ASC');

        if (!empty($filterData->artists)) {
            $query
                ->andWhere('album.artist IN (:artist)')
                ->setParameter('artist', $filterData->artists);
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
                ->andWhere('article.support IN (:support)')
                ->setParameter('support', $filterData->supports);
        }

        return $query->getQuery();
    }

    public function getArticleWithSameArtist($artist): Query
    {
        return $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->where('album.artist = :artist')
            ->orderBy('article.name', 'ASC')
            ->setMaxResults(4)
            ->setParameter('artist', $artist)
            ->getQuery();
    }

    public function getArticleWithSameStyle(array $styles): Query
    {
        return $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->leftJoin('album.styles', 'styles')
            ->where('styles IN (:styles)')
            ->orderBy('article.name', 'ASC')
            ->setMaxResults(4)
            ->setParameter('styles', $styles)
            ->getQuery();
    }

    public function getLastArticle(): Query
    {
        return $this->createQueryBuilder('article')
            ->setMaxResults(8)
            ->orderBy('article.createdAt', 'DESC')
            ->orderBy('article.name', 'ASC')
            ->getQuery();
    }
}
