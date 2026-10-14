<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FeaturedReleaseRepository;
use App\Repository\ReleaseRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /**
     * The queries are handed over unexecuted: the template caches the sections and only
     * runs them on a cache miss. The feature is not cached: it shows the stock and a
     * CSRF token.
     */
    #[Route('/', 'app_homepage')]
    public function index(
        ReleaseRepository $releaseRepository,
        FeaturedReleaseRepository $featuredReleaseRepository,
        ClockInterface $clock,
    ): Response {
        return $this->render('home/index.html.twig', [
            'feature' => $featuredReleaseRepository->findCurrent($clock->now()),
            'lastArticles' => $releaseRepository->getLastReleases(),
            'lastProductions' => $releaseRepository->getOwnProduction(true),
        ]);
    }
}
