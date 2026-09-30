<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\ArticleFilterData;

interface DispatchFilterValueInterface
{
    public function dispatchFilterValue(ArticleFilterData $data): ArticleFilterData;
}
