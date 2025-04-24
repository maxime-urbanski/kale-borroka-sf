<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use App\Repository\AddressRepository;
use App\Service\UserDefaultAddressInterface;
use Symfony\Component\Form\FormFactoryInterface;
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
class PostUserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/mes-adresses/add',
        name: 'app_user_addresses_add',
        requirements: [
            'userId' => Requirement::DIGITS,
            'addressId' => Requirement::DIGITS,
        ],
        methods: [Request::METHOD_POST]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        AddressRepository $addressRepository,
        UserDefaultAddressInterface $userDefaultAddress,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();
        $address = new Address();

        $form = $formFactory->create(UserAccountAddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUsers($user);
            $addressRepository->save($address);
            $userDefaultAddress->defaultAddress($user, $address);
            $session->getFlashBag()->add(
                'success',
                'L\'adresse '.$form->getData()->getName().' à bien été ajouté au carnet d\'adresse.'
            );
        }

        return new RedirectResponse($router->generate('app_user_addresses_index'));
    }
}
