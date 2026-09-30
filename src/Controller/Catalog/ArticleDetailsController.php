<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\AddToCartWithQuantity;
use App\Entity\Release;
use App\Entity\User;
use App\Enum\SupportType;
use App\Form\AddToCartWithQuantityType;
use App\Repository\ArticleRepository;
use App\Repository\ReleaseRepository;
use App\Repository\UserCollectionRepository;
use App\Repository\WishlistRepository;
use App\Service\BreadcrumbInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class ArticleDetailsController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog/{support}/{slug}',
        name: 'app_catalog_show',
        requirements: ['support' => new EnumRequirement(SupportType::class)],
        methods: Request::METHOD_GET
    )]
    public function __invoke(
        Environment $twig,
        BreadcrumbInterface $breadcrumb,
        SupportType $support,
        string $slug,
        ArticleRepository $articleRepository,
        ReleaseRepository $releaseRepository,
        FormFactoryInterface $formInterface,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        WishlistRepository $wishlistRepository,
        UserCollectionRepository $userCollectionRepository,
        // Nullable on purpose: this is a public catalog page. A non-nullable argument
        // makes UserValueResolver throw an AccessDeniedException for anonymous visitors,
        // which the firewall turns into a redirect to the login page.
        #[CurrentUser]
        ?User $user = null,
    ): Response {
        $article = $articleRepository->findPublishedBySlug($slug);
        $canonicalSupport = $article?->getSupportType();

        if (null === $canonicalSupport) {
            throw new NotFoundHttpException('Cet article n\'existe pas.');
        }

        // A release can be listed under several sections but has a single URL.
        if ($canonicalSupport !== $support) {
            return new RedirectResponse($urlGenerator->generate('app_catalog_show', [
                'support' => $canonicalSupport->value,
                'slug' => $slug,
            ]), Response::HTTP_MOVED_PERMANENTLY);
        }

        // Left unexecuted: the template caches the related sections and only runs them on a miss.
        $artistArticle = $article instanceof Release ? $releaseRepository->getReleasesWithSameArtist($article) : null;
        $articleWithSameStyle = $article instanceof Release ? $releaseRepository->getReleasesWithSameStyle($article) : null;

        $userWishlist = null === $user ? null : $wishlistRepository->getUserWishlist($user)->getOneOrNullResult();
        $userCollection = null === $user ? null : $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();

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
            'articleByArtist' => $artistArticle,
            'articleSameStyle' => $articleWithSameStyle,
            'form' => $addToCartForm->createView(),
            'userWishlist' => $userWishlist,
            'userCollection' => $userCollection,
        ]);

        return new Response($content);
    }
}
