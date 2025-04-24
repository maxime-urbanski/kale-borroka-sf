<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Data\UpdateUserInformation;
use App\Entity\User;
use App\Form\UserInformationFormType;
use App\Repository\UserRepository;
use App\Service\UserDefaultAddressInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
        UserDefaultAddressInterface $userDefaultAddress,
        FormFactoryInterface $formFactory,
        Environment $twig,
        RouterInterface $router,
    ): Response|RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        $updateUserInformation = new UpdateUserInformation($user);
        $form = $formFactory->create(UserInformationFormType::class, $updateUserInformation, [
            'method' => 'patch',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user->setLastname($updateUserInformation->lastname);
            $user->setFirstname($updateUserInformation->firstname);
            $user->setEmail($updateUserInformation->email);

            $userDefaultAddress->defaultAddress($user, $updateUserInformation->address);

            $session->getFlashBag()->add(
                'success',
                'Informations modifiées.'
            );

            return new RedirectResponse($router->generate('app_user_informations'));
        }

        $content = $twig->render('user/_information_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
}
