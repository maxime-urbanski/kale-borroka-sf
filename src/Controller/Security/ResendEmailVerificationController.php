<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Account\EmailVerificationInterface;
use App\Entity\User;
use App\Security\ActionCsrfToken;
use App\Service\RefererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * « Renvoyer le lien » of partials/_email_unverified.html.twig (cart, dashboard). A remember-me
 * session is enough: it only mails the account's own address, under the `email_verification`
 * limit.
 */
#[AsController]
final readonly class ResendEmailVerificationController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/mon-compte/verification-email', name: 'app_email_verify_resend', methods: [Request::METHOD_POST])]
    public function __invoke(
        Request $request,
        #[CurrentUser]
        User $user,
        EmailVerificationInterface $emailVerification,
        ActionCsrfToken $csrf,
        RefererInterface $referer,
        RateLimiterFactoryInterface $emailVerificationLimiter,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();
        $back = new RedirectResponse($referer->getReferer());

        if (!$csrf->isValid($request, ActionCsrfToken::EMAIL_VERIFICATION)) {
            return $back;
        }

        if ($user->isEmailVerified()) {
            $session->getFlashBag()->add('info', 'Ton adresse e-mail est déjà confirmée.');

            return $back;
        }

        if (!$emailVerificationLimiter->create((string) $user->getId())->consume()->isAccepted()) {
            $session->getFlashBag()->add('danger', 'Trop de mails envoyés : réessaie dans une heure.');

            return $back;
        }

        $emailVerification->send($user);
        $session->getFlashBag()->add('success', 'Lien envoyé à '.$user->getEmail().'. Regarde aussi dans les indésirables.');

        return $back;
    }
}
