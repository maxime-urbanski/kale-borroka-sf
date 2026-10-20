<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Account\EmailChangeInterface;
use App\Data\UpdateUserInformation;
use App\Entity\User;
use App\Form\UserInformationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Informations (maquette 14): the names are saved at once, a new e-mail waits for the link
 * mailed to it (EmailChangeInterface).
 */
#[AsController]
final readonly class PatchUserInformationsController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/editer',
        name: 'app_user_informations_patch',
        methods: [
            Request::METHOD_GET,
            Request::METHOD_PATCH,
        ]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailChangeInterface $emailChange,
        FormFactoryInterface $formFactory,
        Environment $twig,
        RouterInterface $router,
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();

        $updateUserInformation = new UpdateUserInformation($user);
        $form = $formFactory->create(UserInformationFormType::class, $updateUserInformation, [
            'method' => Request::METHOD_PATCH,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->emailIsFree($userRepository, $user, $form)) {
            // NotBlank on the DTO: a valid form has them all.
            \assert(null !== $updateUserInformation->lastname && null !== $updateUserInformation->firstname && null !== $updateUserInformation->email);
            $user->setLastname($updateUserInformation->lastname);
            $user->setFirstname($updateUserInformation->firstname);
            $entityManager->flush();

            $newEmail = mb_strtolower($updateUserInformation->email);

            if ($newEmail !== $user->getEmail()) {
                $emailChange->request($user, $newEmail);
                $session->getFlashBag()->add('success', 'Informations enregistrées. On a envoyé un lien à '.$newEmail.' : ton adresse change dès que tu l’ouvres.');
            } else {
                $session->getFlashBag()->add('success', 'Informations enregistrées.');
            }

            return new RedirectResponse($router->generate('app_user_informations_patch'));
        }

        $content = $twig->render('user/informations.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);

        return new Response($content, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    /**
     * UniqueEntity sits on User, not on this DTO: without it, a taken e-mail hits the unique index.
     *
     * @param FormInterface<mixed> $form
     */
    private function emailIsFree(UserRepository $userRepository, User $user, FormInterface $form): bool
    {
        $owner = $userRepository->findOneByEmail((string) $form->getData()?->email);

        if (null === $owner || $owner === $user) {
            return true;
        }

        $form->get('email')->addError(new FormError('Un compte utilise déjà cette adresse e-mail.'));

        return false;
    }
}
