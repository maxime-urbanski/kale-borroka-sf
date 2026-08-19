<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(
        SupportRepository $supportRepository,
        AlbumRepository $albumRepository,
    ): Response {
        return $this->render('home/index.html.twig', [
            'support' => $supportRepository->findAll(),
            'lastAlbums' => $albumRepository->getLastAlbums()->getResult(),
            'lastProduction' => $albumRepository->getOwnProduction(true)->getResult(),
        ]);
    }
}
