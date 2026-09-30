<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;

/**
 * A logout with a stale CSRF token (tab left open, session renewed by remember-me) would
 * end on the firewall's bare 403 page. Back to the account page instead, still logged in,
 * with a flash: the button there carries a fresh token. Runs before the firewall's
 * exception listener (priority 1).
 */
final readonly class LogoutCsrfFailure
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: 2)]
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof LogoutException) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('danger', 'La page a expiré : rechargez-la et recommencez.');
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_user_informations')));
    }
}
