<?php

declare(strict_types=1);

namespace App\Controller\Page;

use App\Entity\Page;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class PageController
{
    /**
     * Unpublished pages do not exist for visitors; admins get a preview.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/page/{slug}',
        name: 'app_page_show',
        requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Page $page,
        AuthorizationCheckerInterface $authorizationChecker,
        Environment $twig,
    ): Response {
        $preview = !$page->isPublished();

        if ($preview && !$authorizationChecker->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('Cette page n\'existe pas.');
        }

        $response = new Response($twig->render('page/show.html.twig', [
            'page' => $page,
            'preview' => $preview,
        ]));

        // A preview must never end up in a shared cache.
        if ($preview) {
            $response->setPrivate();
        }

        return $response;
    }
}
