<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Data\UpdatePasswordData;
use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;
use Twig\Environment;

/**
 * Mot de passe (maquette 15). The new hash also signs every other device out: their session
 * holds the old one (Symfony compares it on each request), their remember-me cookie is signed
 * with it. This device stays signed in, its token being saved with the new one, and gets a new
 * remember-me cookie.
 */
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
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'security.authenticator.remember_me_handler.main')]
        RememberMeHandlerInterface $rememberMe,
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
            // A pending e-mail change dies with the old password (the mail to the old address
            // says so), and this device's remember-me cookie is signed again with the new hash.
            $user->setPendingEmail(null);
            $entityManager->flush();
            $rememberMe->createRememberMeCookie($user);
            $session->getFlashBag()->add(
                'success',
                'Mot de passe changé. Tes autres appareils sont déconnectés.'
            );

            return new RedirectResponse($router->generate('app_user_informations_password_put'));
        }

        $content = $twig->render('user/password.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }
}
