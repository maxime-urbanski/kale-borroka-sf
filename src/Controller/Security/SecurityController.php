<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Security\ConnectPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(ConnectPage $connectPage): Response
    {
        return $connectPage->render(ConnectPage::LOGIN);
    }

    /**
     * GET stays allowed for EasyAdmin's "Sign out" link, which carries the token in the query
     * string (logout_path()): the firewall refuses a logout without a valid token either way.
     */
    #[Route(path: '/logout', name: 'app_logout', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
