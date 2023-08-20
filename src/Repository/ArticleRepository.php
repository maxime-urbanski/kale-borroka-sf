<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Support;
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
            ->where('album.kbrProduction = true')
        ;

        return $query->getQuery();
    }

    public function pagination(?Support $support): Query
    {
        $query = $this->createQueryBuilder('article')
            ->where('article.support = :support')
            ->setParameter('support', $support)
            ;
        return $query->getQuery();
    }
}
