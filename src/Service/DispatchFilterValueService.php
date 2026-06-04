<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\ArticleFilterData;

readonly class DispatchFilterValueService implements DispatchFilterValueInterface
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

        if (array_key_exists('styles', $data->globalFilters)) {
            foreach ($data->globalFilters['styles'] as $style) {
                $data->styles[] = $style;
            }
        }

        if (array_key_exists('kbrProduction', $data->globalFilters)) {
            $data->kbrProduction = $data->globalFilters['kbrProduction'];
        }

        return $data;
    }
}
