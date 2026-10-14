<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Homepage;
use App\Repository\HomepageRepository;

/**
 * Not memoised on purpose: in FrankenPHP worker mode the service outlives the request,
 * so a cached row would hide back-office changes until the worker restarts.
 */
readonly class HomepageProvider implements HomepageProviderInterface
{
    public function __construct(
        private HomepageRepository $homepageRepository,
    ) {
    }

    public function get(): Homepage
    {
        return $this->homepageRepository->findOneBy([], ['id' => 'ASC']) ?? new Homepage();
    }
}
