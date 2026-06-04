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
     * @var array{'artists'?: array<Artist>, 'styles'?: array<Style>, 'label'?: array<Label>, 'kbrProduction'?: bool}
     */
    public array $globalFilters = [
        'artist' => [],
        'styles' => [],
        'label' => [],
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
