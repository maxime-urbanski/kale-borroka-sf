<?php

namespace App\Controller\User\Group;

use App\Entity\Invitation;
use App\Entity\User;
use App\Message\Command\GroupDeclineInvitationCommand;
use App\Service\RefererInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route(
    path: '/mon-compte/mes-groupes/{invitationId}/decline-invitation',
    name: 'app_user_group_decline_invitation',
    requirements: [
        'groupId' => Requirement::DIGITS,
    ],

)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class GroupDeclineInvitationController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findOneById(invitationId)')]
        Invitation        $invitation,
        #[CurrentUser]
        User              $user,
        MessageBusInterface $commandBus,
        Request           $request,
        RefererInterface  $referer,
    ): RedirectResponse
    {
        $session = $request->getSession();

        try {
            $commandBus->dispatch(new GroupDeclineInvitationCommand($invitation->getId(), $user->getId()));
            $session->getBag('flashes')->add('success', 'Group declined.');
        } catch (\Exception $exception) {
            $session->getBag('flashes')->add('error', $exception->getMessage());
        }

        return new RedirectResponse($referer->getReferer());
    }
}
