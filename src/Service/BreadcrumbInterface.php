<?php

namespace App\Service;

interface BreadcrumbInterface
{
    /**
     * @return array<int, array{name: string, path: string, paramater: array}>
     */
    public function breadcrumb(string $lastItemName = null): array;
}
