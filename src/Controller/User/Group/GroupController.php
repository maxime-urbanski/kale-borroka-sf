<?php

namespace App\Controller\User\Group;

use App\Entity\User;
use App\Exception\GroupException;
use App\Message\Query\GetGroupByIdQuery;
use App\MessageBus\QueryBusInterface;
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
    path: '/mon-compte/mes-groupes/{id}',
    name: 'app_user_groups_show',
    requirements: [
        'id' => Requirement::DIGITS,
    ],
    defaults: [
        'page' => 'page-1',
    ],
    methods: [Request::METHOD_GET]
)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupController
{
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        QueryBusInterface $queryBus,
        string $id
    ): Response {
        $query = $queryBus->query(new GetGroupByIdQuery($user, $id));

        if (!$query->getResult()) {
            throw new GroupException($id);
        }

        $content = $twig->render('group/show.html.twig', [
            'group' => $query,
        ]);

        return new Response($content);
    }
}
