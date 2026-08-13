<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\EditionRepository;
use App\Repository\OrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly EditionRepository $editionRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'lowStock' => $this->articleRepository->findLowStock(),
            'editionsWithoutOffer' => $this->editionRepository->findWithoutOffer(),
            'lastOrders' => $this->orderRepository->findBy([], ['created_at' => 'DESC'], 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Kale Borroka Records');
    }

    /**
     * Ordered by how a record actually gets published: the album first, since its form
     * carries the whole chain down to the offers, then the pressings and the offers on
     * their own, then everything an album merely refers to.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fas fa-gauge-high');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(AlbumCrudController::class, 'Albums', 'fas fa-record-vinyl');
        yield MenuItem::linkTo(EditionCrudController::class, 'Éditions', 'fas fa-compact-disc');
        yield MenuItem::linkTo(ArticleCrudController::class, 'Offres', 'fas fa-tag');

        yield MenuItem::section('Références');
        yield MenuItem::linkTo(ArtistCrudController::class, 'Artistes', 'fas fa-microphone-lines');
        yield MenuItem::linkTo(LabelCrudController::class, 'Labels', 'fas fa-copyright');
        yield MenuItem::linkTo(StyleCrudController::class, 'Styles', 'fas fa-guitar');
        yield MenuItem::linkTo(SupportCrudController::class, 'Supports', 'fas fa-layer-group');
        yield MenuItem::linkTo(ImageCrudController::class, 'Visuels', 'fas fa-image');

        yield MenuItem::section('Ventes');
        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fas fa-receipt');
        yield MenuItem::linkTo(TransporterCrudController::class, 'Modes de livraison', 'fas fa-truck');
        yield MenuItem::linkTo(PaymentCrudController::class, 'Modes de paiement', 'fas fa-credit-card');

        yield MenuItem::section('Configuration');
        yield MenuItem::linkTo(SocialNetworkCrudController::class, 'Réseaux sociaux', 'fas fa-share-nodes');
        yield MenuItem::linkToRoute('Voir le site', 'fas fa-arrow-up-right-from-square', 'app_homepage')
            ->setLinkTarget('_blank');
    }
}
