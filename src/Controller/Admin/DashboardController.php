<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Article;
use App\Entity\Artist;
use App\Entity\Image;
use App\Entity\Label;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\SocialNetwork;
use App\Entity\Style;
use App\Entity\Support;
use App\Entity\Transporter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(ArtitstCrudController::class)->generateUrl());
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
        yield MenuItem::linkToCrud('Artiste', 'fas fa-list', Artist::class);
        yield MenuItem::linkToCrud('Album', 'fas fa-list', Album::class);
        yield MenuItem::linkToCrud('Style', 'fas fa-list', Style::class);
        yield MenuItem::linkToCrud('Label', 'fas fa-list', Label::class);
        yield MenuItem::linkToCrud('Image', 'fas fa-list', Image::class);

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Support', 'fas fa-list', Support::class);
        yield MenuItem::linkToCrud('Article', 'fas fa-list', Article::class);

        yield MenuItem::section('Commande');
        yield MenuItem::linkToCrud('Mode de paiement', 'fas fa-list', Payment::class);
        yield MenuItem::linkToCrud('Mode de livraison', 'fas fa-list', Transporter::class);
        yield MenuItem::linkToCrud('Commande', 'fas fa-list', Order::class);

        yield MenuItem::section('Configuration');
        yield MenuItem::linkToCrud('Réseaux Sociaux', 'fas fa-list', SocialNetwork::class);
    }
}
