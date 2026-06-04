<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;

final readonly class UserDefaultAddressService implements UserDefaultAddressInterface
{
    public function __construct(
        private AddressRepository $addressRepository,
    ) {
    }

    public function defaultAddress(User $user, Address $newDefaultAddress): void
    {
        $getAllUserAddresses = $this->addressRepository->getAllUserAddresses($user);

        if (!empty($getAllUserAddresses)) {
            foreach ($getAllUserAddresses as $userAddress) {
                if ($userAddress === $newDefaultAddress) {
                    $userAddress->setIsMainAddress(true);
                } else {
                    $userAddress->setIsMainAddress(false);
                }

                $this->addressRepository->save($userAddress, true);
            }
        } else {
            $newDefaultAddress->setIsMainAddress(true);
            $this->addressRepository->save($newDefaultAddress, true);
        }
    }
}
