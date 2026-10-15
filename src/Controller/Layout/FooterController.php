<?php

declare(strict_types=1);

namespace App\Controller\Layout;

use App\Entity\FooterColumn;
use App\Repository\FooterColumnRepository;
use App\Repository\PageRepository;
use App\Repository\SocialNetworkRepository;
use App\Repository\SupportRepository;
use App\Service\HomepageProviderInterface;
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
        FooterColumnRepository $footerColumnRepository,
        SocialNetworkRepository $socialNetworkRepository,
        PageRepository $pageRepository,
        SupportRepository $supportRepository,
        HomepageProviderInterface $homepageProvider,
        Environment $twig,
    ): Response {
        $columns = $footerColumnRepository->findForFooter();

        $content = $twig->render('layout/_footer.html.twig', [
            'homepage' => $homepageProvider->get(),
            'columns' => $columns,
            // Only when a column lists the catalogue sections.
            'supports' => array_any($columns, static fn (FooterColumn $column): bool => $column->isSupportsListed())
                ? $supportRepository->findBy([], ['id' => 'ASC'])
                : [],
            'socialNetworks' => $socialNetworkRepository->getAllSocialNetworkForFooter(),
            'bottomPages' => $pageRepository->findForFooterBottom(),
        ]);

        return new Response($content);
    }
}
