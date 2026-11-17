<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Account\EmailVerificationInterface;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Entity\Wishlist;
use App\Repository\UserCollectionRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Security\ConnectPage;
use App\Security\UserAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
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
        ConnectPage $connectPage,
        EmailVerificationInterface $emailVerification,
        RateLimiterFactoryInterface $registrationLimiter,
    ): Response {
        $user = new User();
        $form = $connectPage->registrationForm($user);
        $form->handleRequest($request);

        // Every account mails a link to the address typed: the form must not become a mail cannon.
        if ($form->isSubmitted() && $form->isValid()
            && !$registrationLimiter->create($request->getClientIp())->consume()->isAccepted()) {
            $form->addError(new FormError('Trop d’inscriptions depuis ta connexion : réessaie dans une heure.'));
        }

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

            // An order waits until the address is proven.
            $emailVerification->send($user);
            $this->addFlash('info', 'Compte créé. On t’a envoyé un lien pour confirmer ton adresse e-mail : il faut l’ouvrir avant de commander.');

            // Null only when the authenticator answers nothing: the account exists, go log in.
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            )
                ?? $this->redirectToRoute('app_login');
        }

        return $connectPage->render(ConnectPage::REGISTER, $form);
    }
}
