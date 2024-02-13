<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
class DeleteUserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
        RouterInterface $router
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

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
