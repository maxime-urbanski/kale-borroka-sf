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
use Symfony\Component\Routing\Requirement\Requirement;

#[Route(
    path: '/catalog',
    name: 'app_catalog',
    methods: [Request::METHOD_GET]
)]
final class CatalogController extends AbstractController
{

    public function __construct(private readonly ArticleRepository $articleRepository)
    {
    }

    #[Route(
        path: '',
        name: ''
    )]
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

    #[Route(
        path: '/{support}/{page}',
        name: '_list',
        requirements: [
            'support' => 'lp|ep|tape|fanzine|cd',
            'page' => '^(page-)' . Requirement::DIGITS
        ],
        defaults: ['page' => 'page-1']
    )]
    public function list(
        Request                                              $request,
        DispatchFilterValueService                           $dispatchFilterValueService,
        PaginationService                                    $paginationService,
        #[MapEntity(mapping: ['support' => 'name'])] Support $support,
        string                                               $page,
    ): Response
    {
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
                'path' => 'app_catalog_list',
                'params' => [
                    'support' => $support,
                ],
            ],
        ];

        $filters = new ArticleFilterData();
        $filters->supports[] = $support;

        $form = $this->createForm(ArticleFilterFormType::class, $filters);
        $form->handleRequest($request);

        $articles = $this->articleRepository->filterArticleQuery(
            $dispatchFilterValueService->dispatchFilterValue($filters)
        );
        $pagination = $paginationService->pagination($articles, $page);

        unset($filters->globalFilters);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb,
            'form' => $form,
            'filters' => $filters,

        ]);
    }

    #[Route(
        path: '/{support}/{slug}',
        name: '_show',
        requirements: ['support' => 'lp|ep|tape|fanzine|cd']
    )]
    public function show(
        string            $support,
        #[MapEntity(expr: 'repository.findOneBySupportAndSlug(support, slug)')]
        Article           $article,
    ): Response
    {
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
                'path' => 'app_catalog_list',
                'params' => [
                    'support' => $support,
                ],
            ],
            [
                'name' => $article->getName(),
                'path' => 'app_catalog_show',
                'params' => [
                    'support' => $support,
                    'slug' => $article->getSlug(),
                ],
            ],
        ];

        $artistArticle = $this->articleRepository->getArticleWithSameArtist($article->getAlbum()->getArtist());

        $currentAlbumStyles = [];
        foreach ($article->getAlbum()->getStyles() as $style) {
            $currentAlbumStyles[] = $style->getId();
        }

        $articleWithSameStyle = $this->articleRepository->getArticleWithSameStyle($currentAlbumStyles);

        return $this->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $breadcrumb,
            'articleByArtist' => $artistArticle->getResult(),
            'articleSameStyle' => $articleWithSameStyle->getResult(),
        ]);
    }
}
