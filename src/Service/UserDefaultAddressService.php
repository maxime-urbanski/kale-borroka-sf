<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserDefaultAddressService implements UserDefaultAddressInterface
{
    public function __construct(
        private AddressRepository $addressRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function defaultAddress(User $user, Address $newDefaultAddress): void
    {
        foreach ($this->addressRepository->getAllUserAddresses($user) as $userAddress) {
            $userAddress->setIsMainAddress($userAddress === $newDefaultAddress);
        }

        // Also the one just persisted, not in the database yet.
        $newDefaultAddress->setIsMainAddress(true);
        $user->setDefaultAddress($newDefaultAddress);
        $this->addressRepository->save($newDefaultAddress);
        $this->entityManager->flush();
    }

    public function handOver(User $user): void
    {
        $addresses = $this->addressRepository->getAllUserAddresses($user);

        if ([] === $addresses || null !== $this->addressRepository->getDefaultUserAddress($user)) {
            return;
        }

        // None is the default: the list is in the order they were added.
        $this->defaultAddress($user, $addresses[0]);
    }
}
