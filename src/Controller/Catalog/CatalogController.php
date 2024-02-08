<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Repository\SupportRepository;
use App\Service\BreadcrumbInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[asController]
class CatalogController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog',
        name: 'app_catalog',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        Environment $twig,
        BreadcrumbInterface $breadcrumb,
        SupportRepository $supportRepository,
    ): Response
    {
        $supports = $supportRepository->findAll();
        $content = $twig->render('catalog/index.html.twig', [
            'supports' => $supports,
            'breadcrumb' => $breadcrumb->breadcrumb(),
        ]);

        return new Response($content);
    }
}
