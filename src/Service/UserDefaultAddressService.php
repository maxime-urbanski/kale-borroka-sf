<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\DBAL\LockMode;
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
        // The account's row first, as the add action does: two writers on the same account
        // take their locks in the same order, so they wait for each other instead of deadlocking.
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($user, $newDefaultAddress): void {
            $entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

            foreach ($this->addressRepository->getAllUserAddresses($user) as $userAddress) {
                $userAddress->setIsMainAddress($userAddress === $newDefaultAddress);
            }

            // Also the one just persisted, not in the database yet.
            $newDefaultAddress->setIsMainAddress(true);
            $user->setDefaultAddress($newDefaultAddress);
            $this->addressRepository->save($newDefaultAddress);
            $entityManager->flush();
        });
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
