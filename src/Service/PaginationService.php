<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\PaginatorInterface;

class PaginationService
{
    const PRODUCT_PER_PAGE = 9;

    public function __construct(private readonly PaginatorInterface $paginator)
    {
    }

    public function pagination(Query|array $data, string $pageParams = 'page-1', $productPerPage = self::PRODUCT_PER_PAGE)
    {
        $page = (int)explode('-', $pageParams)[1];

        return $this->paginator->paginate(
            $data,
            $page,
            $productPerPage
        );
    }
}
