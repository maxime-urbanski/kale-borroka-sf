<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\AddToCartWithQuantity;
use App\Entity\Article;
use App\Entity\Edition;
use App\Entity\User;
use App\Enum\SupportType;
use App\Form\AddToCartWithQuantityType;
use App\Repository\AlbumRepository;
use App\Repository\ArticleRepository;
use App\Repository\EditionRepository;
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

/**
 * The product page is the album, not a single pressing: every edition of the record is
 * listed side by side and the picker swaps between them.
 *
 * The {support} segment is kept in the URL so each level of the path still resolves to a
 * route — BreadcrumbService walks the URI prefix by prefix — and it decides which edition
 * is pre-selected. `?edition=` disambiguates when one album has several editions on the
 * same support, e.g. a black and a red vinyl LP.
 */
#[AsController]
class AlbumDetailsController
{
    #[Route(
        path: '/catalog/{support}/{slug}',
        name: 'app_catalog_show',
        requirements: ['support' => new EnumRequirement(SupportType::class)],
        methods: Request::METHOD_GET
    )]
    public function __invoke(
        Environment $twig,
        BreadcrumbInterface $breadcrumb,
        string $support,
        string $slug,
        EditionRepository $editionRepository,
        AlbumRepository $albumRepository,
        ArticleRepository $articleRepository,
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
        $edition = $editionRepository->findOneBySupportAndAlbumSlug($support, $slug);

        if (null === $edition) {
            throw new NotFoundHttpException('Cet album n\'existe pas.');
        }

        $album = $edition->getAlbum();
        $editions = $editionRepository->findForAlbum($album);
        $selected = $this->selectEdition($editions, $edition, $request->query->getString('edition'));

        $offers = [];
        foreach ($editions as $candidate) {
            $offers[$candidate->getId()] = $articleRepository->findOffersForEdition($candidate);
        }

        $defaultArticle = $selected->getCheapestArticle() ?? ($offers[$selected->getId()][0] ?? null);

        $addToCartForm = null;

        if (null !== $defaultArticle) {
            $addToCartData = new AddToCartWithQuantity($defaultArticle);
            $addToCartForm = $formInterface->create(AddToCartWithQuantityType::class, $addToCartData);
            $addToCartForm->handleRequest($request);

            if ($addToCartForm->isSubmitted() && $addToCartForm->isValid()) {
                // The picker retargets the form client-side, so trust the album, not the
                // submitted id: only offers belonging to this album may be added.
                $chosen = $this->resolveSubmittedArticle($offers, $addToCartData->articleId) ?? $defaultArticle;

                return new RedirectResponse($urlGenerator->generate('app_cart_add', [
                    'id' => $chosen->getId(),
                    'quantity' => $addToCartData->quantity,
                ]));
            }
        }

        $userWishlist = null === $user ? null : $wishlistRepository->getUserWishlist($user)->getOneOrNullResult();
        $userCollection = null === $user ? null : $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();

        $content = $twig->render('catalog/album.html.twig', [
            'album' => $album,
            'editions' => $editions,
            'selectedEdition' => $selected,
            'offers' => $offers,
            'breadcrumb' => $breadcrumb->breadcrumb(lastItemName: $album->getName()),
            'albumsByArtist' => $albumRepository->getAlbumWithSameArtist($album)->getResult(),
            'albumsSameStyle' => $albumRepository->getAlbumWithSameStyle($album)->getResult(),
            'form' => $addToCartForm?->createView(),
            'userWishlist' => $userWishlist,
            'userCollection' => $userCollection,
        ]);

        return new Response($content);
    }

    /**
     * @param Edition[] $editions
     */
    private function selectEdition(array $editions, Edition $fallback, string $requestedSlug): Edition
    {
        if ('' === $requestedSlug) {
            return $fallback;
        }

        foreach ($editions as $edition) {
            if ($edition->getSlug() === $requestedSlug) {
                return $edition;
            }
        }

        return $fallback;
    }

    /**
     * @param array<int, list<Article>> $offers
     */
    private function resolveSubmittedArticle(array $offers, ?int $articleId): ?Article
    {
        if (null === $articleId) {
            return null;
        }

        foreach ($offers as $editionOffers) {
            foreach ($editionOffers as $offer) {
                if ($offer->getId() === $articleId) {
                    return $offer;
                }
            }
        }

        return null;
    }
}
