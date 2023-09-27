<?php

namespace App\Controller\User;

use App\Form\UserInformationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte', name: 'app_user_')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AccountInformationController extends AbstractController
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Route('', 'informations')]
    public function userInformation(): Response
    {
        $user = $this->security->getUser();

        return $this->render('user/_informations.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/editer', 'informations_edit')]
    public function edit(
        Request       $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->security->getUser();

        $form = $this->createForm(UserInformationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Informations modifiées');
        }

        return $this->render('user/_information_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
