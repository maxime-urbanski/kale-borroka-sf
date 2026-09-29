<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShopSettings;
use App\Repository\ShopSettingsRepository;

/**
 * Not memoised on purpose: in FrankenPHP worker mode the service outlives the request,
 * so a cached row would hide back-office changes until the worker restarts.
 */
readonly class ShopSettingsProvider implements ShopSettingsProviderInterface
{
    public function __construct(
        private ShopSettingsRepository $shopSettingsRepository,
    ) {
    }

    public function get(): ShopSettings
    {
        return $this->shopSettingsRepository->findOneBy([], ['id' => 'ASC']) ?? new ShopSettings();
    }
}
