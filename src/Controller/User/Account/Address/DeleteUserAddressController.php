<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use App\Security\Voter\AddressVoter;
use Doctrine\ORM\EntityManagerInterface;
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
class DeleteUserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(AddressVoter::EDIT, subject: 'address')]
    #[Route(
        path: '/mon-compte/mes-adresses/remove/{id}',
        name: 'app_user_addresses_delete',
        requirements: ['id' => Requirement::DIGITS],
        methods: [Request::METHOD_DELETE]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Address $address,
        EntityManagerInterface $entityManager,
        Request $request,
        RouterInterface $router,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        // Token sent by assets/controllers/address_controller.js. Checked here rather than with
        // #[IsCsrfTokenValid], whose failure the firewall turns into a redirect to the login page.
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('address-'.$address->getId(), $request->headers->get('X-CSRF-Token')))) {
            $session->getFlashBag()->add('danger', 'La page a expiré : rechargez-la et recommencez.');

            return new RedirectResponse($router->generate('app_user_addresses_index'));
        }

        $userDefaultAddress = $user->getDefaultAddress();

        $sameAddress = $userDefaultAddress === $address;

        if ($sameAddress) {
            $user->setDefaultAddress(null);
            $entityManager->persist($user);
        }

        $entityManager->remove($address);
        $entityManager->flush();

        $session->getFlashBag()->add(
            'success',
            'Adresse supprimée.'
        );

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
