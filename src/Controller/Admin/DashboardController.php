<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // With pretty URLs, EasyAdmin registers a real Symfony route per CRUD action,
        // so redirecting by route name is enough — no AdminUrlGenerator needed.
        return $this->redirectToRoute('admin_artist_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Kale Borroka Records');
    }

    /**
     * assets/admin.js: create-on-the-fly for autocompletes (CreatableAutocompleteExtension).
     */
    public function configureAssets(): Assets
    {
        return Assets::new()->addWebpackEncoreEntry('admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::subMenu('Ventes & commandes', 'fas fa-cart-shopping')->setSubItems([
            MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fas fa-receipt'),
            MenuItem::linkTo(UserCrudController::class, 'Clients', 'fas fa-users'),
        ]);

        yield MenuItem::subMenu('Catalogue musique', 'fas fa-music')->setSubItems([
            MenuItem::linkTo(ArtistCrudController::class, 'Groupes & artistes', 'fas fa-microphone-lines'),
            MenuItem::linkTo(AlbumCrudController::class, 'Discographie', 'fas fa-record-vinyl'),
            MenuItem::linkTo(ReleaseCrudController::class, 'Exemplaires & pressages', 'fas fa-compact-disc'),
            MenuItem::linkTo(SongCrudController::class, 'Morceaux', 'fas fa-list-ol'),
            MenuItem::linkTo(StyleCrudController::class, 'Styles', 'fas fa-guitar'),
            MenuItem::linkTo(LabelCrudController::class, 'Labels', 'fas fa-copyright'),
        ]);

        yield MenuItem::subMenu('Boutique & merch', 'fas fa-box-open')->setSubItems([
            MenuItem::linkTo(BookCrudController::class, 'Fanzines & livres', 'fas fa-book'),
            MenuItem::linkTo(MerchCrudController::class, 'Merch & textiles', 'fas fa-shirt'),
            MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fas fa-folder-tree'),
            MenuItem::linkTo(LabelCrudController::class, 'Labels tiers (distro)', 'fas fa-handshake')
                ->setQueryParameter('filters', ['isDistro' => 1]),
        ]);

        yield MenuItem::subMenu('Contenu', 'fas fa-file-lines')->setSubItems([
            MenuItem::linkTo(PageCrudController::class, 'Pages', 'fas fa-file-lines'),
            MenuItem::linkTo(SocialNetworkCrudController::class, 'Réseaux sociaux', 'fas fa-share-nodes'),
            MenuItem::linkTo(ImageCrudController::class, 'Visuels', 'fas fa-image'),
        ]);

        yield MenuItem::subMenu('Configuration', 'fas fa-gear')->setSubItems([
            MenuItem::linkTo(UserCrudController::class, 'Utilisateurs & rôles', 'fas fa-user-shield'),
            MenuItem::linkTo(ShopSettingsCrudController::class, 'Paramètres de la boutique', 'fas fa-sliders'),
            MenuItem::linkTo(PaymentCrudController::class, 'Modes de paiement', 'fas fa-credit-card'),
            MenuItem::linkTo(TransporterCrudController::class, 'Modes de livraison', 'fas fa-truck'),
            MenuItem::linkTo(SupportCrudController::class, 'Rayons du catalogue', 'fas fa-layer-group'),
        ]);

        yield MenuItem::linkToRoute('Voir le site', 'fas fa-arrow-up-right-from-square', 'app_homepage');
    }
}
