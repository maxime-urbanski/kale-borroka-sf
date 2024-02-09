<?php

declare(strict_types=1);

namespace App\Controller\User\Wantlist;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\WantlistRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[asController]
class RemoveToWantlistController
{
    #[Route(
        path: '/wantlist/remove/{productId}',
        name: 'app_wantlist_remove',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article $article,
        RefererInterface $referer,
        EntityManagerInterface $entityManager,
        WantlistRepository $wantlistRepository,
        SessionInterface $session
    ): RedirectResponse {
        $currentUserWantlist = $wantlistRepository->findOneBy(
            ['userWantlist' => $user]
        );

        try {
            $currentUserWantlist->removeProduct($article);
            $entityManager->persist($currentUserWantlist);
            $entityManager->flush();
            $session->getFlashBag()->add(
                'success',
                $article->getName().' à bien été supprimé de la wantlist'
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
