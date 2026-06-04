<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use App\Service\UserDefaultAddressInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final readonly class PatchDefaultUserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/mes-adresses/update/default-address/{userId}/{addressId}',
        name: 'app_user_addresses_patch_default_address',
        requirements: [
            'userId' => Requirement::DIGITS,
            'addressId' => Requirement::DIGITS,
        ],
        methods: [
            Request::METHOD_PATCH,
        ]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['addressId' => 'id'])]
        Address $address,
        UserDefaultAddressInterface $userDefaultAddress,
        Request $request,
        RouterInterface $router,
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        $userDefaultAddress->defaultAddress($user, $address);

        $session->getFlashBAg()->add(
            'success',
            'Adresse de livraison par défaut mise à jour.'
        );

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
