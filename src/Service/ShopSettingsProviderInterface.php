<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShopSettings;

interface ShopSettingsProviderInterface
{
    /**
     * The settings row, or an unsaved instance holding the defaults when there is none yet.
     */
    public function get(): ShopSettings;
}
