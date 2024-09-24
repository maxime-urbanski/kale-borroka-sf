<?php

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Message\Command\GroupInvitationCommand;
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
    path: '/mon-compte/mes-groupes/{groupId}/{guestId}/invitations',
    name: 'app_users_group_invitations',
    requirements: [
        'groupId' => Requirement::DIGITS,
        'guestId' => Requirement::DIGITS,
    ],
    methods: [Request::METHOD_GET]
)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class GroupInvitationController
{
    public function __invoke(
        #[CurrentUser]
        User $user,
        QueryBusInterface $queryBus,
        Request $request,
        RefererInterface $referer,
        string $groupId,
        string $guestId
    ): RedirectResponse {
        $session = $request->getSession();

        if (\in_array($groupId, $user->getGroups()->toArray(), true)) {
            $session->getFlashBag()->add('danger','User is already a member of this group.');
            return new RedirectResponse($referer->getReferer());
        }

        try {
            $queryBus->query(new GroupInvitationCommand($groupId, $user->getId(), $guestId));
            $session->getFlashBag()->add('success', 'Invitation sent successfully.');
        } catch (\Exception $exception) {
            $session->getFlashBag()->add('danger', $exception->getMessage());
        }

        return new RedirectResponse($referer->getReferer());
    }
}
