<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Account\EmailChangeInterface;
use App\Entity\User;
use App\Form\ConfirmEmailChangeFormType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;
use Twig\Environment;

/**
 * The link of the mail sent to a new address (App\Account\EmailChange). Logged in as the
 * account it belongs to (opened elsewhere, it goes through the login form first), then the
 * current password: the GET only shows the form, which posts back to the same signed URL.
 * The Informations page asks for no password; this step is what stops a session left open.
 */
#[AsController]
final readonly class ConfirmEmailChangeController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/confirmer-email',
        name: 'app_user_email_confirm',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        EmailChangeInterface $emailChange,
        FormFactoryInterface $formFactory,
        Environment $twig,
        RouterInterface $router,
        #[Autowire(service: 'security.authenticator.remember_me_handler.main')]
        RememberMeHandlerInterface $rememberMe,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();
        $back = new RedirectResponse($router->generate('app_user_informations_patch'));

        if (null !== $failure = $emailChange->check($user, $request)) {
            $session->getFlashBag()->add('danger', $failure);

            return $back;
        }

        $form = $formFactory->create(ConfirmEmailChangeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $failure = $emailChange->confirm($user, $request)) {
                $session->getFlashBag()->add('danger', $failure);

                return $back;
            }

            // Remember-me cookies name the account by its e-mail: every other device signs in
            // again, this one gets a cookie for the new address.
            $rememberMe->createRememberMeCookie($user);
            $session->getFlashBag()->add('success', 'Adresse e-mail confirmée : c’est maintenant '.$user->getEmail().'. Tes autres appareils devront se reconnecter.');

            return $back;
        }

        return new Response($twig->render('user/confirm_email.html.twig', [
            'form' => $form->createView(),
            'newEmail' => $user->getPendingEmail(),
        ]), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }
}
