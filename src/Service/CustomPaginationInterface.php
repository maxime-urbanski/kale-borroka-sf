<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface CustomPaginationInterface
{
    public const PRODUCT_PER_PAGE = 9;

    /**
     * @phpstan-ignore-next-line
     */
    public function pagination(Query|array $data, string $pageParams = 'page-1', int $productPerPage = self::PRODUCT_PER_PAGE): PaginationInterface;
}
