<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Form\UserAccountAddressFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route(
    path: '/mon-compte/mes-adresses/update/{id}',
    name: 'app_user_addresses_patch_address',
    requirements: [
        'id' => Requirement::DIGITS,
    ],
    methods: [Request::METHOD_PATCH]
)]
class PatchAddressController
{
    public function __invoke(
        Address $address,
        Request $request,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        FormFactoryInterface $formFactory
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        $form = $formFactory->create(UserAccountAddressFormType::class, $address, [
            'method' => 'patch',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $session->getFlashBag()->add(
                'success',
                'Adresse '.$address->getName().' mise à jour.'
            );
        }

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
