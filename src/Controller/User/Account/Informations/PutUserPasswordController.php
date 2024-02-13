<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Data\UpdatePasswordData;
use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class PutUserPasswordController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/editer-mot-de-passe',
        name: 'app_user_informations_password_put',
        methods: [
            Request::METHOD_GET,
            Request::METHOD_PUT,
        ]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        UserPasswordHasherInterface $hasher,
        Request $request,
        RouterInterface $router,
        Environment $twig,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        /** @var Session $session */
        $session = $request->getSession();
        $passwordData = new UpdatePasswordData();
        $form = $formFactory->create(UpdatePasswordFormType::class, $passwordData, [
            'method' => 'put',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $hasher->hashPassword($user, $passwordData->new_password)
            );
            $entityManager->flush();
            $session->getFlashBag()->add(
                'success',
                'Mot de passe modifié'
            );

            return new RedirectResponse($router->generate('app_user_informations'));
        }

        $content = $twig->render('user/_password_edit.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
}
