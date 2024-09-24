<?php

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Message\Command\GroupRemoveInvitationCommand;
use App\MessageBus\QueryBusInterface;
use App\Service\RefererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route(
    path: '/mon-compte/mes-groupes/{invitationId}/delete-invitation',
    name: 'app_user_group_delete_invitation',
    requirements: [
        'groupId' => Requirement::DIGITS,
    ],

)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupRemoveInvitationController
{
    public function __invoke(
        #[CurrentUser]
        User              $user,
        QueryBusInterface $queryBus,
        Request           $request,
        RefererInterface  $referer,
        string            $invitationId
    ): RedirectResponse
    {
        $session = $request->getSession();

        try {
            $queryBus->query(new GroupRemoveInvitationCommand($invitationId, $user->getId()));
            $session->getBag('flashes')->add('success', 'Invitation removed.');
        } catch (\Exception $exception) {
            $session->getBag('flashes')->add('error', $exception->getMessage());
        }

        return new RedirectResponse($referer->getReferer());
    }
}
