<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\Address;
use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use Doctrine\ORM\EntityManagerInterface;
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
class PostUserAddressController
{
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();
        $address = new Address();
        $form = $formFactory->create(UserAccountAddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setName($form->getData()->getName());
            $address->setAddress($form->getData()->getAddress());
            $address->setComplementAddress($form->getData()->getComplementAddress());
            $address->setCity($form->getData()->getCity());
            $address->setZipcode($form->getData()->getZipcode());
            $address->setCountry($form->getData()->getCountry());
            $address->setIsMainAddress(false);
            $address->setUsers($user);

            $entityManager->persist($address);
            $entityManager->flush();

            $session->getFlashBag()->add(
                'success',
                'L\'adresse '.$form->getData()->getName().' à bien été ajouté au carnet d\'adresse.'
            );
        }

        return new RedirectResponse($router->generate('app_user_informations'));
    }
}
