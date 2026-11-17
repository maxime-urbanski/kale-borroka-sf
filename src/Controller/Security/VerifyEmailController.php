<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Account\EmailVerificationInterface;
use App\Entity\User;
use App\Security\ActionCsrfToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

/**
 * The link of the mail sent at registration (App\Account\EmailVerification). Works logged in or
 * not: it is signed, and only proves an inbox. The GET shows a button (mail scanners open links
 * by themselves), which posts to the same signed URL.
 */
#[AsController]
final readonly class VerifyEmailController
{
    #[Route('/verification-email', name: 'app_email_verify', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function __invoke(
        Request $request,
        EmailVerificationInterface $emailVerification,
        ActionCsrfToken $csrf,
        RouterInterface $router,
        Environment $twig,
        #[CurrentUser] ?User $current = null,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();
        $next = new RedirectResponse($router->generate($current instanceof User ? 'app_cart_index' : 'app_login'));
        $user = $emailVerification->check($request);

        if (null === $user) {
            $session->getFlashBag()->add('danger', 'Ce lien ne marche plus : il a expiré ou ton adresse a changé. Connecte-toi pour en recevoir un nouveau.');

            return $next;
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return new Response($twig->render('security/verify_email.html.twig', ['email' => $user->getEmail()]));
        }

        if (!$csrf->isValid($request, ActionCsrfToken::EMAIL_VERIFICATION)) {
            return new RedirectResponse($request->getUri());
        }

        $emailVerification->confirm($request);
        $session->getFlashBag()->add('success', $current instanceof User && $current->getId() !== $user->getId()
            // Someone else's link, opened in this session: say whose address it was.
            ? 'Adresse '.$user->getEmail().' confirmée. Ce n’est pas celle du compte connecté.'
            : 'Adresse e-mail confirmée, tu peux commander.');

        return $next;
    }
}
