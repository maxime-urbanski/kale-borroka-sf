<?php

declare(strict_types=1);

namespace App\Controller;

use App\Data\ArticleFilterData;
use App\Entity\Article;
use App\Entity\Support;
use App\Form\ArticleFilterFormType;
use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use App\Service\BreadcrumbInterface;
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
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly BreadcrumbInterface $breadcrumb
    ) {
    }

    #[Route(
        path: '',
        name: ''
    )]
    public function index(SupportRepository $supportRepository): Response
    {
        $supports = $supportRepository->findAll();

        return $this->render('catalog/index.html.twig', [
            'supports' => $supports,
            'breadcrumb' => $this->breadcrumb->breadcrumb(),
        ]);
    }

    #[Route(
        path: '/{support}/{page}',
        name: '_list',
        requirements: [
            'support' => 'lp|ep|tape|fanzine|cd',
            'page' => '^(page-)'.Requirement::DIGITS,
        ],
        defaults: ['page' => 'page-1']
    )]
    public function list(
        Request $request,
        DispatchFilterValueService $dispatchFilterValueService,
        PaginationService $paginationService,
        #[MapEntity(mapping: ['support' => 'name'])] Support $support,
        string $page,
    ): Response {
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
            'breadcrumb' => $this->breadcrumb->breadcrumb(),
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
        #[MapEntity(expr: 'repository.findOneBySupportAndSlug(support, slug)')]
        Article $article,
    ): Response {
        $artistArticle = $this->articleRepository->getArticleWithSameArtist($article->getAlbum()->getArtist());

        $currentAlbumStyles = [];
        foreach ($article->getAlbum()->getStyles() as $style) {
            $currentAlbumStyles[] = $style->getId();
        }

        $articleWithSameStyle = $this->articleRepository->getArticleWithSameStyle($currentAlbumStyles);

        return $this->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $this->breadcrumb->breadcrumb(lastItemName: $article->getName()),
            'articleByArtist' => $artistArticle->getResult(),
            'articleSameStyle' => $articleWithSameStyle->getResult(),
        ]);
    }
}
