<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Account\AccountCountsInterface;
use App\Catalog\RecordNames;
use App\Entity\Article;
use App\Entity\Release;
use App\Entity\User;
use App\Entity\UserCollectionItems;
use App\Enum\SupportType;
use App\Repository\ArticleRepository;
use App\Repository\UserCollectionItemsRepository;
use App\Service\CustomPaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Collection (maquette 12): the user's records, filtered by support (`?support=lp`), searched
 * (`?q=`) and shown as covers or as a list (`?vue=liste`). All links, so it works without
 * JavaScript. A collection is a few hundred items at most: filtered in PHP, then paginated.
 */
#[AsController]
class AccountUserCollectionController
{
    private const int PER_PAGE = 20;

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/mon-compte/collection/{page}',
        name: 'app_user_collection',
        requirements: [
            'page' => '^(page-)'.Requirement::DIGITS,
        ],
        defaults: [
            'page' => 'page-1',
        ],
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        Environment $twig,
        AccountCountsInterface $accountCounts,
        UserCollectionItemsRepository $userCollectionItemsRepository,
        ArticleRepository $articleRepository,
        CustomPaginationInterface $customPagination,
        string $page,
    ): Response {
        $items = $userCollectionItemsRepository->allOfUser($user);
        $articleRepository->preloadCovers(array_values(array_filter(array_map(
            static fn (UserCollectionItems $item): ?Article => $item->getArticle(),
            $items,
        ))));

        $support = SupportType::tryFrom($request->query->getString('support'));
        $search = trim($request->query->getString('q'));

        $counts = [];
        $shown = [];
        foreach ($items as $item) {
            $article = $item->getArticle();
            if (null === $article) {
                continue;
            }

            $type = $article->getSupportType();
            if (null !== $type) {
                $counts[$type->value] = ($counts[$type->value] ?? 0) + 1;
            }
            if (null !== $support && $type !== $support) {
                continue;
            }

            $row = $this->row($item, $article);
            if ('' === $search || str_contains(mb_strtolower($row['haystack']), mb_strtolower($search))) {
                $shown[] = $row;
            }
        }

        return new Response($twig->render('user/collection.html.twig', [
            'counts' => $accountCounts->of($user),
            'pagination' => $customPagination->pagination($shown, $page, self::PER_PAGE),
            'supports' => SupportType::cases(),
            'supportCounts' => $counts,
            'support' => $support,
            'search' => $search,
            'view' => 'liste' === $request->query->getString('vue') ? 'liste' : 'grille',
            'total' => \count($items),
            'filtered' => \count($shown),
        ]));
    }

    /**
     * @return array{item: UserCollectionItems, article: Article, artist: string, title: string, ref: ?string, year: ?int, haystack: string}
     */
    private function row(UserCollectionItems $item, Article $article): array
    {
        $names = RecordNames::of($article);
        $year = $article instanceof Release ? $article->getAlbum()?->getDateRelease()?->format('Y') : null;

        return [
            'item' => $item,
            'article' => $article,
            ...$names,
            'year' => null !== $year ? (int) $year : ($article instanceof Release ? $article->getPressingYear() : null),
            'haystack' => implode(' ', array_filter([$names['artist'], $names['title'], (string) $article->getName(), $names['ref']])),
        ];
    }
}
