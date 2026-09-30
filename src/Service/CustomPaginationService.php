<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

readonly class CustomPaginationService implements CustomPaginationInterface
{
    public const PRODUCT_PER_PAGE = 9;

    public function __construct(private PaginatorInterface $paginator)
    {
    }

    /**
     * @param Query|array<mixed> $data       what to paginate: a query, or the items themselves
     * @param string             $pageParams the {page} route segment, "page-N"
     *
     * @return PaginationInterface<int, mixed>
     */
    public function pagination(Query|array $data, string $pageParams = 'page-1', int $productPerPage = self::PRODUCT_PER_PAGE): PaginationInterface
    {
        $page = (int) explode('-', $pageParams)[1];

        return $this->paginator->paginate(
            $data,
            $page,
            $productPerPage
        );
    }
}
