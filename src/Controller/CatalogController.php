<?php

declare(strict_types=1);

namespace App\Controller;

use App\Data\ArticleFilterData;
use App\Entity\Article;
use App\Entity\Support;
use App\Form\ArticleFilterFormType;
use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use App\Service\DispatchFilterValueService;
use App\Service\PaginationService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
                'path' => 'app_homepage',
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog',
            ],
        ];

        return $this->render('catalog/index.html.twig', [
            'supports' => $supports,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    #[Route('/catalog/{support}/{page}', 'app_catalog_support', requirements: ['page' => '^(page-)\d+'])]
    public function catalogBySupport(
        Request $request,
        ArticleRepository $articleRepository,
        DispatchFilterValueService $dispatchFilterValueService,
        PaginationService $paginationService,
        #[MapEntity(mapping: ['support' => 'name'])] Support $support,
        string $page = 'page-1',
    ): Response {
        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage',
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog',
            ],
            [
                'name' => $support,
                'path' => 'app_catalog_support',
                'params' => [
                    'support' => $support,
                ],
            ],
        ];

        $filters = new ArticleFilterData();
        $filters->supports[] = $support;

        $form = $this->createForm(ArticleFilterFormType::class, $filters);
        $form->handleRequest($request);

        $articles = $articleRepository->filterArticleQuery(
            $dispatchFilterValueService->dispatchFilterValue($filters)
        );
        $pagination = $paginationService->pagination($articles, $page);

        unset($filters->globalFilters);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'filters' => $filters,
        ]);
    }

    #[Route('/catalog/{support}/{slug}', 'app_catalog_article')]
    public function pageArticle(
        #[MapEntity(mapping: ['support' => 'name'])] Support $support,
        #[MapEntity(mapping: ['support' => ':support', 'slug' => 'slug'])] Article $article,
        ArticleRepository $articleRepository
    ): Response {
        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage',
            ],
            [
                'name' => 'Catalogue',
                'path' => 'app_catalog',
            ],
            [
                'name' => $support,
                'path' => 'app_catalog_support',
                'params' => [
                    'support' => $support,
                ],
            ],
            [
                'name' => $article->getName(),
                'path' => 'app_catalog_article',
                'params' => [
                    'support' => $support,
                    'slug' => $article->getSlug(),
                ],
            ],
        ];

        $artistArticle = $articleRepository->getArticleWithSameArtist($article->getAlbum()->getArtist());

        $currentAlbumStyles = [];
        foreach ($article->getAlbum()->getStyles() as $style) {
            $currentAlbumStyles[] = $style->getId();
        }

        $articleWithSameStyle = $articleRepository->getArticleWithSameStyle($currentAlbumStyles);

        return $this->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $breadcrumb,
            'articleByArtist' => $artistArticle->getResult(),
            'articleSameStyle' => $articleWithSameStyle->getResult(),
        ]);
    }
}
