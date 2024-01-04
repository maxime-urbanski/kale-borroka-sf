<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

readonly class PaginationService
{
    public const PRODUCT_PER_PAGE = 9;

    public function __construct(private PaginatorInterface $paginator)
    {
    }

    // TODO: Fix error phpstan

    /**
     * @phpstan-ignore-next-line
     */
    public function pagination(Query $data, string $pageParams = 'page-1', int $productPerPage = self::PRODUCT_PER_PAGE): PaginationInterface
    {
        $page = (int) explode('-', $pageParams)[1];

        return $this->paginator->paginate(
            $data,
            $page,
            $productPerPage
        );
    }
}
