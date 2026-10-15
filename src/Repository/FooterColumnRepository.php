<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FooterColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FooterColumn>
 */
class FooterColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FooterColumn::class);
    }

    /**
     * The columns in footer order, their links and targets loaded in the same query.
     *
     * @return list<FooterColumn>
     */
    public function findForFooter(): array
    {
        /** @var list<FooterColumn> $columns */
        $columns = $this->createQueryBuilder('footerColumn')
            ->leftJoin('footerColumn.links', 'link')
            ->leftJoin('link.page', 'page')
            ->leftJoin('link.support', 'support')
            ->addSelect('link', 'page', 'support')
            ->orderBy('footerColumn.position', 'ASC')
            ->addOrderBy('footerColumn.id', 'ASC')
            ->addOrderBy('link.position', 'ASC')
            ->addOrderBy('link.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $columns;
    }
}
