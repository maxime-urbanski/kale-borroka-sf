<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\AddToCartWithQuantity;
use App\Entity\Article;
use App\Entity\User;
use App\Form\AddToCartWithQuantityType;
use App\Repository\ArticleRepository;
use App\Repository\UserCollectionRepository;
use App\Repository\WishlistRepository;
use App\Service\BreadcrumbInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class ArticleDetailsController
{
    public const SUPPORT_REQUIREMENTS = 'lp|ep|tape|fanzine|cd';

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NonUniqueResultException
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
        ArticleRepository $articleRepository,
        FormFactoryInterface $formInterface,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        WishlistRepository $wishlistRepository,
        UserCollectionRepository $userCollectionRepository,
        #[CurrentUser]
        User $user,
    ): Response {
        $artistArticle = $articleRepository->getArticleWithSameArtist($article);
        $articleWithSameStyle = $articleRepository->getArticleWithSameStyle($article);

        $userWishlist = $wishlistRepository->getUserWishlist($user)->getOneOrNullResult();
        $userCollection = $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();

        $addToCartData = new AddToCartWithQuantity($article);
        $addToCartForm = $formInterface->create(AddToCartWithQuantityType::class, $addToCartData);
        $addToCartForm->handleRequest($request);

        if ($addToCartForm->isSubmitted() && $addToCartForm->isValid()) {
            $url = $urlGenerator->generate('app_cart_add', [
                'id' => $article->getId(),
                'quantity' => $addToCartData->quantity,
            ]);

            return new RedirectResponse($url);
        }

        $content = $twig->render('catalog/article.html.twig', [
            'article' => $article,
            'breadcrumb' => $breadcrumb->breadcrumb(lastItemName: $article->getName()),
            'articleByArtist' => $artistArticle->getResult(),
            'articleSameStyle' => $articleWithSameStyle->getResult(),
            'form' => $addToCartForm->createView(),
            'userWishlist' => $userWishlist,
            'userCollection' => $userCollection,
        ]);

        return new Response($content);
    }
}
