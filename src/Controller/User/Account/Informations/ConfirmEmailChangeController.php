<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Account\EmailChangeInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

/**
 * The link of the mail sent to a new address (App\Account\EmailChange). Logged in as the
 * account it belongs to: opened elsewhere, it goes through the login form first.
 */
#[AsController]
final readonly class ConfirmEmailChangeController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/confirmer-email',
        name: 'app_user_email_confirm',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        EmailChangeInterface $emailChange,
        RouterInterface $router,
        #[Autowire(service: 'security.authenticator.remember_me_handler.main')]
        RememberMeHandlerInterface $rememberMe,
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();
        $failure = $emailChange->confirm($user, $request);

        if (null === $failure) {
            // Remember-me cookies name the account by its e-mail: every other device signs in
            // again, this one gets a cookie for the new address.
            $rememberMe->createRememberMeCookie($user);
            $session->getFlashBag()->add('success', 'Adresse e-mail confirmée : c’est maintenant '.$user->getEmail().'. Tes autres appareils devront se reconnecter.');
        } else {
            $session->getFlashBag()->add('danger', $failure);
        }

        return new RedirectResponse($router->generate('app_user_informations_patch'));
    }
}
