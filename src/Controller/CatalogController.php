<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog', 'app_catalog')]
    public function index(SupportRepository $supportRepository): Response
    {
        $supports = $supportRepository->findAll();

        return $this->render('catalog/index.html.twig', [
            'supports' => $supports
        ]);
    }

    #[Route('/catalog/{support}', 'app_catalog_support')]
    public function catalogBySupport(
        ArticleRepository $articleRepository,
        SupportRepository $supportRepository,
        string            $support
    ): Response
    {

        $getSupport = $supportRepository->findOneBy(['name' => $support]);
        $articles = $articleRepository->findBy(['support' => $getSupport]);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $articles
        ]);

    }

    #[Route('/catalog/{support}/{slug}', 'app_catalog_article')]
    public function pageArticle(
        ArticleRepository $articleRepository,
        SupportRepository $supportRepository,
        string            $support,
        string            $slug
    ): Response
    {
        $getSupport = $supportRepository->findOneBy(['name' => $support]);
        $article = $articleRepository->findOneBy([
            'support' => $getSupport,
            'slug' => $slug
        ]);

        return $this->render('catalog/article.html.twig', [
            'article' => $article
        ]);
    }
}
