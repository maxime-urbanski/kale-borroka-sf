<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Account\AddressBookPageInterface;
use App\Entity\Address;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use App\Security\Voter\AddressVoter;
use App\Service\UserDefaultAddressInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class PatchAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(AddressVoter::EDIT, subject: 'address')]
    #[Route(
        path: '/mon-compte/mes-adresses/update/{id}',
        name: 'app_user_addresses_patch_address',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: [Request::METHOD_PATCH]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Address $address,
        Request $request,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        UserDefaultAddressInterface $userDefaultAddress,
        AddressBookPageInterface $addressBookPage,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();
        $wasDefault = true === $address->isIsMainAddress();

        $form = $formFactory->create(UserAccountAddressFormType::class, $address, [
            'method' => Request::METHOD_PATCH,
            'is_default' => $wasDefault,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            // The cards show what is saved, not what was typed.
            $entityManager->refresh($address);

            return $addressBookPage->render($user, $form, $address, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->flush();

        if (!$wasDefault && true === $form->get('makeDefault')->getData()) {
            $userDefaultAddress->defaultAddress($user, $address);
        }

        $session->getFlashBag()->add('success', 'Adresse « '.$address->getName().' » mise à jour.');

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
