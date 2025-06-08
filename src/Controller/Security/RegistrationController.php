<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Entity\User;
use App\Entity\UserCollection;
use App\Entity\Wishlist;
use App\Form\RegistrationFormType;
use App\Repository\UserCollectionRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Security\UserAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        UserAuthenticator $authenticator,
        UserRepository $userRepository,
        UserCollectionRepository $userCollectionRepository,
        WishlistRepository $wishlistRepository,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setRoles(['ROLE_USER']);

            $userRepository->save($user, true);

            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlistRepository->save($wishlist, true);

            $userCollection = new UserCollection();
            $userCollection->setUser($user);
            $userCollectionRepository->save($userCollection, true);
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'title' => 'Inscription',
        ]);
    }
}
