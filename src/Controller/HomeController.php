<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\HomeShelf;
use App\Repository\ArticleRepository;
use App\Repository\FeaturedReleaseRepository;
use App\Repository\ReleaseRepository;
use App\Service\HomepageProviderInterface;
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
        ArticleRepository $articleRepository,
        ReleaseRepository $releaseRepository,
        FeaturedReleaseRepository $featuredReleaseRepository,
        ClockInterface $clock,
        HomepageProviderInterface $homepageProvider,
    ): Response {
        $homepage = $homepageProvider->get();

        return $this->render('home/index.html.twig', [
            'homepage' => $homepage,
            'feature' => $featuredReleaseRepository->findCurrent($clock->now()),
            'latestArticles' => array_map(static fn (HomeShelf $shelf): array => [
                'shelf' => $shelf,
                'articles' => $articleRepository->latest($shelf, $homepage->getLatestLimit()),
            ], $homepage->getLatestShelves()),
            'lastProductions' => $releaseRepository->latestProductions($homepage->getProductionsLimit()),
        ]);
    }
}
