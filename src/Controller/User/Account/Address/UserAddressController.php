<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Account\AddressBookPageInterface;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use App\Repository\AddressRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Mes adresses (maquette 13). `?modifier=<id>` opens the form of one of the customer's
 * addresses under the cards, `?ajouter` an empty one (also shown when there is no address yet):
 * links, so it works without JavaScript.
 */
#[AsController]
class UserAddressController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/mes-adresses',
        name: 'app_user_addresses_index',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        FormFactoryInterface $formFactory,
        AddressRepository $addressRepository,
        AddressBookPageInterface $addressBookPage,
    ): Response {
        $addresses = $addressRepository->getAllUserAddresses($user);
        $editing = null;
        $form = null;

        if ($request->query->has('modifier')) {
            // Among the customer's own addresses only: another id opens nothing.
            $id = $request->query->getInt('modifier');
            $editing = array_find($addresses, static fn ($address): bool => $address->getId() === $id);
        }

        if (null !== $editing) {
            $form = $formFactory->create(UserAccountAddressFormType::class, $editing, [
                'method' => Request::METHOD_PATCH,
                'is_default' => true === $editing->isIsMainAddress(),
            ]);
        } elseif (\count($addresses) < AddressBookPageInterface::MAX_ADDRESSES
            && ([] === $addresses || $request->query->has('ajouter'))) {
            $form = $formFactory->create(UserAccountAddressFormType::class, null, [
                // The first address is the default one whatever the box says.
                'is_default' => [] === $addresses,
            ]);
        }

        return $addressBookPage->render($user, $form, $editing, addresses: $addresses);
    }
}
