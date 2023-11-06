<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(
        SupportRepository $supportRepository,
        ArticleRepository $articleRepository
    ): Response {
        $lastArticle = $articleRepository->getLastArticle();
        $lasProduction = $articleRepository->getOwnProduction(true);

        $dataForReactComponent = [];

        foreach ($lastArticle->getResult() as $product) {
            $dataForReactComponent[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'quantity' => $product->getQuantity(),
                'price' => $product->getPrice(),
                'support' => $product->getSupport(),
                'album' => $product->getAlbum(),
            ];
        }


        return $this->render('home/index.html.twig', [
            'support' => $supportRepository->findAll(),
            'lastArticle' => $lastArticle->getResult(),
            'lastArticleReact' => $dataForReactComponent,
            'lastProduction' => $lasProduction->getResult(),
        ]);
    }
}
