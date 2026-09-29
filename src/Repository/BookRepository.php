<?php

declare(strict_types=1);

namespace App\Repository;

use App\Data\ArticleFilterData;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Only the label filter applies to books: it matches the publisher.
     */
    public function filterBookQuery(ArticleFilterData $filterData): Query
    {
        $query = $this->createQueryBuilder('book')
            ->where('book.published = true')
            ->orderBy('book.name', 'ASC');

        if (!empty($filterData->labels)) {
            $query
                ->andWhere('book.publisher IN (:labels)')
                ->setParameter('labels', $filterData->labels);
        }

        return $query->getQuery();
    }
}
