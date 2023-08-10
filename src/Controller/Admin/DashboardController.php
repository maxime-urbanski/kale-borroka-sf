<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Article;
use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Entity\Support;
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

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Support', 'fas fa-list', Support::class);
        yield MenuItem::linkToCrud('Article', 'fas fa-list', Article::class);

    }
}
