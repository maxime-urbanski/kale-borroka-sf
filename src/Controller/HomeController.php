<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(
        SupportRepository   $supportRepository,
        ArticleRepository   $articleRepository,
        SerializerInterface $serializer
    ): Response
    {

        $lastArticle = $articleRepository->getLastArticle();
        $lastArticleSerialized = $serializer
            ->serialize($lastArticle->getResult(),
                'json',
                ['groups' => ['article:slider', 'support:slider']]
            );
        $lastProduction = $articleRepository->getOwnProduction(true);
        $lastProductionSerialized = $serializer
            ->serialize($lastProduction->getResult(),
                'json',
                ['groups' => ['article:slider', 'support:slider']]
            );

        return $this->render('home/index.html.twig', [
            'support' => $supportRepository->findAll(),
            'lastArticle' => $lastArticleSerialized,
            'lastProduction' => $lastProductionSerialized,
        ]);
    }
}
