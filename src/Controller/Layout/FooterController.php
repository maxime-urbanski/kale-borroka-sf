<?php

declare(strict_types=1);

namespace App\Controller\Layout;

use App\Repository\SocialNetworkRepository;
use App\Repository\SupportRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class FooterController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        SupportRepository $supportRepository,
        SocialNetworkRepository $socialNetworkRepository,
        Environment $twig,
    ): Response {
        $supports = $supportRepository->findAll();
        $socialNetworks = $socialNetworkRepository->getAllSocialNetworkForFooter();

        $content = $twig->render('layout/_footer.html.twig', [
            'supports' => $supports,
            'socialNetworks' => $socialNetworks,
        ]);

        return new Response($content);
    }
}
