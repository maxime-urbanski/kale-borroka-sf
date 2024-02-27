<?php

declare(strict_types=1);

namespace App\Controller\User\Wantlist;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Wantlist;
use App\Repository\WantlistRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class AddToWantlistController
{
    #[Route(
        path: '/wantlist/add/{productId}',
        name: 'app_wantlist_add',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article $article,
        RefererInterface $referer,
        EntityManagerInterface $entityManager,
        WantlistRepository $wantlistRepository,
        Request $request
    ): RedirectResponse {
        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $wantlist->setUserWantlist($user);
            $entityManager->persist($wantlist);
        } else {
            $wantlist = $wantlistRepository->findOneBy(
                ['userWantlist' => $user]
            );
        }

        /** @var Session $session */
        $session = $request->getSession();

        try {
            $wantlist->addProduct($article);
            $entityManager->persist($wantlist);
            $entityManager->flush();
            $session->getFlashBag()->add(
                'success',
                $article->getName().' à bien été ajouté à la wantlist'
            );
        } catch (NotFoundHttpException $exception) {
            $session->getFlashBag()->add(
                'danger',
                $exception
            );
        }

        return new RedirectResponse($referer->getReferer());
    }
}
