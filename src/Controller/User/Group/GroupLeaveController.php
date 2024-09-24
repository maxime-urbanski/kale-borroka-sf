<?php

declare(strict_types=1);

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Message\Command\GroupLeaveCommand;
use App\Service\RefererInterface;
use Exception;
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
    path: '/mon-compte/mes-groupes/{groupId}/leave',
    name: 'app_user_group_leave',
    requirements: [
        'groupId' => Requirement::DIGITS,
    ],

)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupLeaveController
{
    public function __invoke(
        #[CurrentUser]
        User                $user,
        MessageBusInterface $commandBus,
        Request             $request,
        RefererInterface    $referer,
        string              $groupId
    )
    {
        $session = $request->getSession();

        try {
            $commandBus->dispatch(new GroupLeaveCommand($groupId, $user->getId()));
            $session->getBag('flashes')->add('success', 'Group leaved successfully.');
        } catch (Exception $e) {
            $session->getBag('flashes')->add('error', $e->getMessage());
        }

        return new RedirectResponse($referer->getReferer());
    }
}
