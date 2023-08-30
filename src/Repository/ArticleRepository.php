<?php

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Article;
use App\Entity\Support;
use App\Form\ArticleFilterFormType;
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

    public function getOwnProduction(): Query
    {
        $query = $this->createQueryBuilder('article')
            ->select()
            ->leftJoin('article.album', 'album')
            ->where('album.kbrProduction = true');

        return $query->getQuery();
    }

    public function filterArticleQuery(?Support $support, ArticleFilterData $filterData): Query
    {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->where('article.support = :support')
            ->orderBy('article.name','ASC')
            ->setParameter('support', $support);

        if (!empty($filterData->artists)) {
            $query
                ->andWhere('album.artist = :artist')
                ->setParameter('artist', $filterData->artists);
        }

        if (!empty($filterData->labels)) {
            $query
                ->leftJoin('album.labels', 'labels')
                ->andWhere("labels = :labels")
                ->setParameter('labels', $filterData->labels);
        }

        if (!empty($filterData->styles)) {
            $query
                ->leftJoin('album.styles', 'styles')
                ->andWhere('styles = :styles')
                ->setParameter('styles', $filterData->styles);
        }

        if ($filterData->kbrProduction) {
            $query
                ->andWhere('album.kbrProduction = :kbrProduction')
                ->setParameter('kbrProduction', $filterData->kbrProduction);
        }

        return $query->getQuery();
    }
}
