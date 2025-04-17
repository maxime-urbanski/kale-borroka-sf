<?php

declare(strict_types=1);

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
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

        if ($forHome) {
            $query
                ->orderBy('article.name', 'ASC')
                ->setMaxResults(8);
        }

        return $query->getQuery();
    }

    public function filterArticleQuery(
        ArticleFilterData $filterData,
    ): Query {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->orderBy('article.name', 'ASC');

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
                ->andWhere('article.support IN (:supports)')
                ->setParameter('supports', $filterData->supports);
        }

        return $query->getQuery();
    }

    public function getArticleWithSameArtist(Article $article): Query
    {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->where('album.artist = :artist')
            ->andWhere('article != :article')
            ->orderBy('article.name', 'ASC')
            ->setMaxResults(10)
            ->setParameters([
                'artist' => $article->getAlbum()->getArtist(),
                'article' => $article,
            ]);

        return $query->getQuery();
    }

    public function getArticleWithSameStyle(Article $article): Query
    {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.album', 'album')
            ->leftJoin('album.styles', 'styles')
            ->where('styles IN (:styles)')
            ->andWhere('article != :article')
            ->orderBy('article.name', 'ASC')
            ->setMaxResults(10)
            ->setParameters([
                'styles' => $article->getAlbum()->getStyles(),
                'article' => $article,
            ]);

        return $query->getQuery();
    }

    public function getLastArticle(): Query
    {
        return $this->createQueryBuilder('article')
            ->setMaxResults(8)
            ->orderBy('article.name', 'ASC')
            ->getQuery();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneBySupportAndSlug(string $support, string $slug): Article
    {
        $query = $this->createQueryBuilder('article')
            ->leftJoin('article.support', 'support')
            ->where('support.name = :support')
            ->andWhere('article.slug = :slug')
            ->setParameter('support', $support)
            ->setParameter('slug', $slug);

        return $query->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
    }
}
