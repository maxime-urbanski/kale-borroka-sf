<?php

namespace App\Data;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Entity\Support;

class ProductionFilterData
{
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
