<?php

declare(strict_types=1);

namespace App\Controller\Layout;

use App\Service\HomepageProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * The black strip above the navbar (Homepage::$strip*). Not routed: base.html.twig renders
 * it in a {% cache %} block, apart from the navbar, which depends on the visitor.
 */
#[AsController]
class HeaderStripController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(HomepageProviderInterface $homepageProvider, Environment $twig): Response
    {
        return new Response($twig->render('layout/_strip.html.twig', [
            'homepage' => $homepageProvider->get(),
        ]));
    }
}
