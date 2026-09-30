<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * CSRF check of the one-click actions (cart, wishlist, collection), posted by
 * templates/partials/_post_button.html.twig. Checked by hand rather than with
 * #[IsCsrfTokenValid], whose failure the firewall turns into a redirect to the login page:
 * on failure the caller redirects back, with a flash message added here.
 */
final readonly class ActionCsrfToken
{
    public const string CART = 'cart';
    public const string WISHLIST = 'wishlist';
    public const string COLLECTION = 'collection';

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function isValid(Request $request, string $tokenId): bool
    {
        if ($this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $request->getPayload()->getString('_token')))) {
            return true;
        }

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('danger', 'La page a expiré : rechargez-la et recommencez.');
        }

        return false;
    }
}
