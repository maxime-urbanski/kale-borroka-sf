<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Homepage;

interface HomepageProviderInterface
{
    /**
     * The homepage row, or an unsaved instance holding the defaults when there is none yet.
     */
    public function get(): Homepage;
}
