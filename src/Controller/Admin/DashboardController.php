<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public function index(): Response
    {
        return $this->redirect(
            $this->adminUrlGenerator->setController(ArtitstCrudController::class)->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Kale Borroka Records');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Album');
        yield MenuItem::linkTo(ArtitstCrudController::class, 'Artiste', 'fas fa-list');
        yield MenuItem::linkTo(AlbumCrudController::class, 'Album', 'fas fa-list');
        yield MenuItem::linkTo(StyleCrudController::class, 'Style', 'fas fa-list');
        yield MenuItem::linkTo(LabelCrudController::class, 'Label', 'fas fa-list');
        yield MenuItem::linkTo(ImageCrudController::class, 'Image', 'fas fa-list');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(SupportCrudController::class, 'Support', 'fas fa-list');
        yield MenuItem::linkTo(ArticleCrudController::class, 'Article', 'fas fa-list');

        yield MenuItem::section('Commande');
        yield MenuItem::linkTo(PaymentCrudController::class, 'Mode de paiement', 'fas fa-list');
        yield MenuItem::linkTo(TransporterCrudController::class, 'Mode de livraison', 'fas fa-list');
        yield MenuItem::linkTo(OrderCrudController::class, 'Commande', 'fas fa-list');

        yield MenuItem::section('Configuration');
        yield MenuItem::linkTo(SocialNetworkCrudController::class, 'Réseaux Sociaux', 'fas fa-list');
    }
}
