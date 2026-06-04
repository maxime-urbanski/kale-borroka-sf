<?php

namespace App\Service;

use App\Data\ArticleFilterData;

interface DispatchFilterValueInterface
{
    public function dispatchFilterValue(ArticleFilterData $data): ArticleFilterData;
}
