<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Entity\User;
use App\Form\UserInformationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class PatchUserInformationsController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/editer',
        name: 'app_user_informations_patch',
        methods: [
            Request::METHOD_GET,
            Request::METHOD_PATCH,
        ]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        Environment $twig,
        RouterInterface $router
    ): Response|RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();
        $form = $formFactory->create(UserInformationFormType::class, $user, [
            'method' => 'patch',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $session->getFlashBag()->add(
                'success',
                'Informations modifiées.'
            );

            return new RedirectResponse($router->generate('app_user_informations'));
        }

        $content = $twig->render('user/_information_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
}
