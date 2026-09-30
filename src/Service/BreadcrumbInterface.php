<?php

namespace App\Service;

interface BreadcrumbInterface
{
    /**
     * @return list<array{name: string, path: string, parameters: array<string, mixed>}>
     */
    public function breadcrumb(?string $lastItemName = null): array;
}
