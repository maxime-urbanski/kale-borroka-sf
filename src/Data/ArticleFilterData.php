<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Entity\Support;

class ArticleFilterData
{
    /**
     * Values coming from the "global" filter form, merged into the flat properties below
     * by DispatchFilterValueService.
     *
     * Keys must stay plural and match GlobalArticleFilterType's field names, which is
     * what the service reads — the defaults used to be declared as 'artist'/'label' and
     * only worked because the bound form replaced the whole array.
     *
     * @var array{'artists'?: array<Artist>, 'styles'?: array<Style>, 'labels'?: array<Label>, 'kbrProduction'?: bool}
     */
    public array $globalFilters = [
        'artists' => [],
        'styles' => [],
        'labels' => [],
        'kbrProduction' => false,
    ];

    /**
     * @var Artist[]
     */
    public array $artists = [];

    /**
     * @var Label[]
     */
    public array $labels = [];
    /**
     * @var Style[]
     */
    public array $styles = [];

    /**
     * @var Support[]
     */
    public array $supports = [];

    public bool $kbrProduction = false;
}
