<?php

declare(strict_types=1);


namespace App\Controller\User;

use App\Repository\AddressRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('mon-compte/mes-adresses', 'app_user_addresses')]
class AccountUserAddress extends AbstractController
{
    public function __construct(
        private readonly Security               $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository      $addressRepository,
        private readonly UserRepository         $userRepository,
    )
    {
    }

    #[Route('', '_index')]
    public function index(): Response
    {
        return $this->render('user/_user_addresses.html.twig', [
            'userAddresses' => $this->security->getUser()->getAddresses()
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/patch/default-address/{userId}/{addressId}', name: '_patch_default_address')]
    public function patchDefaultAddress(
        string $userId,
        string $addressId
    ): Response
    {
        try {
            $currentUser = $this->userRepository->find($userId);
            $address = $this->addressRepository->find($addressId);

            $currentUser->setDefaultAddress($address);

            $this->entityManager->persist($currentUser);
            $this->entityManager->flush();
            $this->addFlash('success', 'Adresse de livraison mise à jour');
            return $this->redirectToRoute('app_user_addresses_index');
        } catch (Exception $exception) {
            throw new Exception($exception);
        }
    }
}
