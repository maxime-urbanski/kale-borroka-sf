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
     * Published pages linked from the footer, by placement value, in their footer order.
     *
     * @return array<string, Page[]>
     */
    public function findForFooter(): array
    {
        /** @var Page[] $pages */
        $pages = $this->createQueryBuilder('page')
            ->where('page.published = true')
            ->andWhere('page.footerPlacement != :none')
            ->orderBy('page.footerPosition', 'ASC')
            ->addOrderBy('page.title', 'ASC')
            ->setParameter('none', FooterPlacement::NONE->value)
            ->getQuery()
            ->getResult();

        $byPlacement = [];
        foreach ($pages as $page) {
            $byPlacement[$page->getFooterPlacement()->value][] = $page;
        }

        return $byPlacement;
    }
}
