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
     * @var array{'artists': array<Artist>, 'styles': array<Style>, 'label': array<Label>, 'kbrProduction': bool}
     */
    public array $globalFilters;

    /**
     * @var Support[]
     */
    public array $supports = [];
    /**
     * @var Label[]
     */
    public array $labels;

    /**
     * @var Style[]
     */
    public array $styles;

    /**
     * @var Artist[]
     */
    public array $artists;

    public bool $kbrProduction = false;
}
