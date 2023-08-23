<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog', 'app_catalog')]
    public function index(SupportRepository $supportRepository): Response
    {
        $supports = $supportRepository->findAll();

        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage'
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog'
            ],
        ];

        return $this->render('catalog/index.html.twig', [
            'supports' => $supports,
            'breadcrumb' => $breadcrumb
        ]);
    }

    #[Route('/catalog/{support}/{page}', 'app_catalog_support', requirements: ['page' => '^(page-)\d+'])]
    public function catalogBySupport(
        ArticleRepository $articleRepository,
        SupportRepository $supportRepository,
        PaginationService $paginationService,
        string            $support,
        string            $page = 'page-1',
    ): Response
    {
        $getSupport = $supportRepository->findOneBy(['name' => $support]);
        $articles = $articleRepository->pagination($getSupport);
        $pagination = $paginationService->pagination($articles, $page);

        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage'
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog'
            ],
            [
                'name' => $support,
                'path' => 'app_catalog_support',
                'params' => [
                    'support' => $support
                ]
            ]
        ];

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb
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
            'slug' => $slug,
        ]);

        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage'
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog'
            ],
            [
                'name' => $support,
                'path' => 'app_catalog_support',
                'params' => [
                    'support' => $support
                ]
            ],
            [
                'name' => $article->getName(),
                'path' => 'app_catalog_article',
                'params' => [
                    'support' => $support,
                    'slug' => $slug
                ]
            ]
        ];

        return $this->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $breadcrumb
        ]);
    }
}
