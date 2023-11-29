<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Data\UpdatePasswordData;
use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use App\Form\UserInformationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte', name: 'app_user_')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AccountInformationController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', 'informations')]
    public function userInformation(): Response
    {
        $user = $this->security->getUser();

        return $this->render('user/_informations.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/editer', 'informations_edit')]
    public function edit(
        Request $request,
    ): Response {
        $user = $this->security->getUser();

        $form = $this->createForm(UserInformationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Informations modifiées');

            return $this->redirectToRoute('app_user_informations');
        }

        return $this->render('user/_information_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/editer-mot-de-passe', 'password_edit')]
    public function editPassword(
        Request $request,
        UserPasswordHasherInterface $hasher
    ): Response {
        /* @var User $user */
        $user = $this->security->getUser();

        $passwordData = new UpdatePasswordData();
        $form = $this->createForm(UpdatePasswordFormType::class, $passwordData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $passwordData->new_password));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Mot de passe modifié');

            return $this->redirectToRoute('app_user_informations');
        }

        return $this->render('user/_password_edit.html.twig', [
            'form' => $form,
        ]);
    }
}
