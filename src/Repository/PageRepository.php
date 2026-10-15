<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Page;
use App\Enum\FooterPlacement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * Published pages linked from the footer's bottom line, in their footer order.
     *
     * @return list<Page>
     */
    public function findForFooterBottom(): array
    {
        /** @var list<Page> $pages */
        $pages = $this->createQueryBuilder('page')
            ->where('page.published = true')
            ->andWhere('page.footerPlacement = :bottom')
            ->orderBy('page.footerPosition', 'ASC')
            ->addOrderBy('page.title', 'ASC')
            ->setParameter('bottom', FooterPlacement::BOTTOM->value)
            ->getQuery()
            ->getResult();

        return $pages;
    }
}
