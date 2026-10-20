<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use App\Security\Voter\AddressVoter;
use App\Service\UserDefaultAddressInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final readonly class PatchDefaultUserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(AddressVoter::EDIT, subject: 'address')]
    #[Route(
        path: '/mon-compte/mes-adresses/default/{id}',
        name: 'app_user_addresses_patch_default_address',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: [
            Request::METHOD_PATCH,
        ]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Address $address,
        UserDefaultAddressInterface $userDefaultAddress,
        Request $request,
        RouterInterface $router,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        // Posted by partials/_post_button.html.twig. Checked here rather than with
        // #[IsCsrfTokenValid], whose failure the firewall turns into a redirect to the login page.
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('address-'.$address->getId(), $request->request->getString('_token')))) {
            $session->getFlashBag()->add('danger', 'La page a expiré : rechargez-la et recommencez.');

            return new RedirectResponse($router->generate('app_user_addresses_index'));
        }

        $userDefaultAddress->defaultAddress($user, $address);

        $session->getFlashBag()->add('success', '« '.$address->getName().' » est ton adresse de livraison par défaut.');

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
