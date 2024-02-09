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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[asController]
class AddToWantlistController
{
    #[Route(
        path: '/wantlist/add/{productId}',
        name: 'app_wantlist_add',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapEntity(mapping: ['productId' => 'id'])] Article $article,
        RefererInterface $referer,
        EntityManagerInterface $entityManager,
        WantlistRepository $wantlistRepository,
        SessionInterface $session
    ): RedirectResponse {
        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $wantlist->setUserWantlist($user);
            $entityManager->persist($wantlist);
        }

        $currentUserWantlist = $wantlistRepository->findOneBy(
            ['userWantlist' => $user]
        );

        try {
            $currentUserWantlist->addProduct($article);
            $entityManager->persist($currentUserWantlist);
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
