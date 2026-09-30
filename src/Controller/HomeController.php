<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ReleaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /**
     * The queries are handed over unexecuted: the template caches the sections and only
     * runs them on a cache miss.
     */
    #[Route('/', 'app_homepage')]
    public function index(ReleaseRepository $releaseRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'lastArticles' => $releaseRepository->getLastReleases(),
            'lastProductions' => $releaseRepository->getOwnProduction(true),
        ]);
    }
}
