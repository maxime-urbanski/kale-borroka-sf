<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\BreadcrumbInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[asController]
class ArticleDetailsController
{
    public const SUPPORT_REQUIREMENTS = 'lp|ep|tape|fanzine|cd';

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog/{support}/{slug}',
        name: 'app_catalog_show',
        requirements: ['support' => self::SUPPORT_REQUIREMENTS],
        methods: Request::METHOD_GET
    )]
    public function __invoke(
        Environment $twig,
        BreadcrumbInterface $breadcrumb,
        #[MapEntity(expr: 'repository.findOneBySupportAndSlug(support, slug)')]
        Article $article,
        ArticleRepository $articleRepository
    ): Response {
        $artistArticle = $articleRepository->getArticleWithSameArtist($article->getAlbum()->getArtist());

        $currentAlbumStyles = [];
        foreach ($article->getAlbum()->getStyles() as $style) {
            $currentAlbumStyles[] = $style->getId();
        }

        $articleWithSameStyle = $articleRepository->getArticleWithSameStyle($currentAlbumStyles);

        $content = $twig->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $breadcrumb->breadcrumb(lastItemName: $article->getName()),
            'articleByArtist' => $artistArticle->getResult(),
            'articleSameStyle' => $articleWithSameStyle->getResult(),
        ]);

        return new Response($content);
    }
}
