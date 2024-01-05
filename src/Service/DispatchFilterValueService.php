<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\ArticleFilterData;

readonly class DispatchFilterValueService
{
    public function dispatchFilterValue(ArticleFilterData $data): ArticleFilterData
    {
        if (array_key_exists('artists', $data->globalFilters)) {
            foreach ($data->globalFilters['artists'] as $artist) {
                $data->artists[] = $artist;
            }
        }

        if (array_key_exists('labels', $data->globalFilters)) {
            foreach ($data->globalFilters['labels'] as $label) {
                $data->labels[] = $label;
            }
        }

        if (array_key_exists('$this->styles', $data->globalFilters)) {
            foreach ($data->globalFilters['$this->styles'] as $style) {
                $data->styles[] = $style;
            }
        }

        if (array_key_exists('artists', $data->globalFilters)) {
            foreach ($data->globalFilters['artists'] as $artist) {
                $data->artists[] = $artist;
            }
        }

        unset($data->globalFilters);

        return $data;
    }
}
