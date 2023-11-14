<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Address;
use App\Form\UserAccountAddressFormType;
use App\Repository\AddressRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('mon-compte/mes-adresses', 'app_user_addresses')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AccountUserAddress extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', '_index')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        $userAddresses = $user->getAddresses();

        $forms = [];

        foreach ($userAddresses as $address) {
            $form = $this->createForm(UserAccountAddressFormType::class, $address);
            $forms[$address->getId()] = $form->createView();
        }

        $formAdd = $this->createForm(UserAccountAddressFormType::class);

        return $this->render('user/_user_addresses.html.twig', [
            'userAddresses' => $userAddresses,
            'forms' => $forms,
            'formAdd' => $formAdd->createView(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/update/default-address/{userId}/{addressId}', name: '_update_default_address')]
    public function patchDefaultAddress(
        string $userId,
        string $addressId
    ): Response {
        try {
            $currentUser = $this->userRepository->find($userId);
            $address = $this->addressRepository->find($addressId);

            $currentUser->setDefaultAddress($address);

            $this->entityManager->persist($currentUser);
            $this->entityManager->flush();
            $this->addFlash('success', 'Adresse de livraison mise à jour');

            return $this->redirectToRoute('app_user_addresses_index');
        } catch (\Exception $exception) {
            throw new \Exception($exception);
        }
    }

    #[Route('/add', name: '_add', methods: ['POST'])]
    public function addAddress(
        Request $request
    ): Response {
        $address = new Address();
        $form = $this->createForm(UserAccountAddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newAddress = new Address();
            $newAddress->setName($form->getData()->getName());
            $newAddress->setAddress($form->getData()->getAddress());
            $newAddress->setComplementAddress($form->getData()->getComplementAddress());
            $newAddress->setCity($form->getData()->getCity());
            $newAddress->setZipcode($form->getData()->getZipcode());
            $newAddress->setCountry($form->getData()->getCountry());
            $newAddress->setIsMainAddress(false);
            $newAddress->setUsers($this->security->getUser());

            $this->entityManager->persist($newAddress);
            $this->entityManager->flush();

            $this->addFlash('success', "L'adresse ".$form->getData()->getName()." à bien été ajouté au carnet d'adresse.");
        }

        return $this->redirectToRoute('app_user_addresses_index');
    }

    #[Route('/remove/{id}', '_remove')]
    public function deleteAddress(Address $address): Response
    {
        try {
            $user = $this->security->getUser();
            $userDefaultAddress = $user->getDefaultAddress();

            $sameAddress = $userDefaultAddress === $address;

            if ($sameAddress) {
                $user->setDefaultAddress(null);
                $this->entityManager->persist($user);
            }

            $this->entityManager->remove($address);
            $this->entityManager->flush();
            $this->addFlash('success', 'Adresse supprimée.');
        } catch (\Doctrine\DBAL\Exception $exception) {
            throw new \Exception($exception);
        }

        return $this->redirectToRoute('app_user_addresses_index');
    }

    #[Route('/update/{id}', '_update', methods: ['POST'])]
    public function update(
        Address $address,
        Request $request
    ): Response {
        $form = $this->createForm(UserAccountAddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'Adresse mise à jour.');
        }

        return $this->redirectToRoute('app_user_addresses_index');
    }
}
