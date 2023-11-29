<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;

class ArticleFilterData
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

    /*
     * @var Album
     */
    public bool $kbrProduction = false;
}
