<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Address;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('mon-compte/mes-adresses', 'app_user_addresses')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AccountUserAddress extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository      $addressRepository,
    )
    {
    }

    #[Route('', '_index')]
    public function index(#[CurrentUser] User $user): Response
    {
        $userAddresses = $this->addressRepository->findBy(['users' => $user]);

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
        #[CurrentUser] User $user,
        string              $userId,
        string              $addressId
    ): Response
    {
        try {
            $address = $this->addressRepository->find($addressId);

            $user->setDefaultAddress($address);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Adresse de livraison mise à jour');

            return $this->redirectToRoute('app_user_addresses_index');
        } catch (\Exception $exception) {
            throw new \Exception('error', 500, $exception);
        }
    }

    #[Route('/add', name: '_add', methods: ['POST'])]
    public function addAddress(
        #[CurrentUser] User $user,
        Request             $request
    ): Response
    {
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
            $newAddress->setUsers($user);

            $this->entityManager->persist($newAddress);
            $this->entityManager->flush();

            $this->addFlash('success', "L'adresse " . $form->getData()->getName() . " à bien été ajouté au carnet d'adresse.");
        }

        return $this->redirectToRoute('app_user_addresses_index');
    }

    #[Route('/remove/{id}', '_remove')]
    public function deleteAddress(
        #[CurrentUser] User $user,
        Address             $address
    ): Response
    {
        try {
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
            throw new \Exception('error');
        }

        return $this->redirectToRoute('app_user_addresses_index');
    }

    #[Route('/update/{id}', '_update', methods: ['POST'])]
    public function update(
        Address $address,
        Request $request
    ): Response
    {
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
