<?php

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Message\Query\GetGroupsQuery;
use App\MessageBus\QueryBusInterface;
use App\Repository\GroupRepository;
use App\Service\CustomPaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[Route(
    path: '/mon-compte/mes-groupes/{page}',
    name: 'app_user_groups',
    requirements: [
        'page' => '^(page-)'.Requirement::DIGITS,
    ],
    defaults: [
        'page' => 'page-1',
    ],
    methods: [Request::METHOD_GET]
)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupsController
{
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        QueryBusInterface $queryBus,
        CustomPaginationInterface $customPagination,
        GroupRepository $groupRepository,
        string $page,
    ): Response {
        $query = $queryBus->query(new GetGroupsQuery($user));
        $groups = $groupRepository->findAll();

        $groupsPaginate = $customPagination->pagination($query, $page);

        $content = $twig->render('group/index.html.twig', [
            'userGroups' => $groupsPaginate,
            'groups' => $groups,
        ]);

        return new Response($content);
    }
}
