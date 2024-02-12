<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Address;

use App\Entity\User;
use App\Form\UserAccountAddressFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route(
    path: '/mon-compte/mes-adresses',
    name: 'app_user_addresses_index',
    methods: [Request::METHOD_GET]
)]
class UserAddressController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function __invoke(
        #[CurrentUser]
        User $user,
        FormFactoryInterface $form,
        Environment $twig
    ): Response {
        $userAddresses = $user->getAddresses();
        $forms = [];

        foreach ($userAddresses as $address) {
            $formAddresses = $form->create(UserAccountAddressFormType::class, $address);
            $forms[$address->getId()] = $formAddresses->createView();
        }

        $formAddNewAddress = $form->create(UserAccountAddressFormType::class);
        $content = $twig->render('user/_user_addresses.html.twig', [
            'userAddresses' => $userAddresses,
            'forms' => $forms,
            'formAdd' => $formAddNewAddress->createView(),
        ]);

        return new Response($content);
    }
}
