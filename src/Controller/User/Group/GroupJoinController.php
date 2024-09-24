<?php

declare(strict_types=1);

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Message\Command\GroupJoinCommand;
use App\Service\RefererInterface;
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
    path: '/mon-compte/mes-groupes/{groupId}/accept',
    name: 'app_user_group_accept_invitation',
    requirements: [
        'groupId' => Requirement::DIGITS,
    ],

)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupJoinController
{
    public function __invoke(
        #[CurrentUser]
        User                $user,
        MessageBusInterface $commandBus,
        Request             $request,
        RefererInterface    $referer,
        string              $groupId
    ): RedirectResponse
    {
        $session = $request->getSession();

        try {
            $commandBus->dispatch(new GroupJoinCommand($groupId, $user->getId()));
            $session->getBag('flashes')->add('success', 'Group joined successfully.');
        } catch (\Exception $e) {
            $session->getBag('flashes')->add('error', $e->getMessage());
        }

        return new RedirectResponse($referer->getReferer());
    }
}
