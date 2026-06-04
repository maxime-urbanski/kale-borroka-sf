<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\NonUniqueResultException;
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
class AccountUserInformationsController
{
    /**
     * @throws SyntaxError
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte',
        name: 'app_user_informations'
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        AddressRepository $addressRepository,
    ): Response {
        $userDefaultAddress = $addressRepository->getDefaultUserAddress($user);

        $content = $twig->render('user/_informations.html.twig', [
            'user' => $user,
            'userDefaultAddress' => $userDefaultAddress,
        ]);

        return new Response($content);
    }
}
