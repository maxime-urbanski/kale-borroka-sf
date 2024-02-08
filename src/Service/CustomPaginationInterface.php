<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface CustomPaginationInterface
{
    public const PRODUCT_PER_PAGE = 9;

    public function pagination(Query $data, string $pageParams = 'page-1', int $productPerPage = self::PRODUCT_PER_PAGE): PaginationInterface;
}
