<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Account\AddressBookPageInterface;
use App\Entity\Address;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use App\Repository\AddressRepository;
use App\Service\UserDefaultAddressInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
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
        AddressBookPageInterface $addressBookPage,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();

        // Counting then adding is only safe one request at a time: the account's row is locked
        // until the commit, so a parallel add waits, then counts the address this one added.
        // Without it, simultaneous posts would go past the cap, or both be the first address.
        return $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($user, $request, $session, $addressRepository, $userDefaultAddress, $formFactory, $router, $addressBookPage): Response {
            $entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $addresses = $addressRepository->getAllUserAddresses($user);

            if (\count($addresses) >= AddressBookPageInterface::MAX_ADDRESSES) {
                $session->getFlashBag()->add('danger', 'Cinq adresses au plus : supprime-en une pour en ajouter une autre.');

                return new RedirectResponse($router->generate('app_user_addresses_index'));
            }

            $address = new Address();
            $form = $formFactory->create(UserAccountAddressFormType::class, $address, [
                'is_default' => [] === $addresses,
            ]);
            $form->handleRequest($request);

            if (!$form->isSubmitted() || !$form->isValid()) {
                return $addressBookPage->render($user, $form, status: Response::HTTP_UNPROCESSABLE_ENTITY, addresses: $addresses);
            }

            $address->setUsers($user)->setIsMainAddress(false);
            $addressRepository->save($address, true);

            if ([] === $addresses || true === $form->get('makeDefault')->getData()) {
                $userDefaultAddress->defaultAddress($user, $address);
            }

            $session->getFlashBag()->add('success', 'Adresse « '.$address->getName().' » ajoutée.');

            return new RedirectResponse($router->generate('app_user_addresses_index'));
        });
    }
}
