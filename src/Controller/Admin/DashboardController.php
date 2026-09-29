<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Query\DashboardMetricsInterface;
use App\Admin\Query\FinancialReportInterface;
use App\Admin\Query\RevenuePeriod;
use App\Entity\Article;
use App\Entity\Book;
use App\Entity\MerchVariant;
use App\Entity\Release;
use App\Repository\ArticleRepository;
use App\Service\ShopSettingsProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly DashboardMetricsInterface $dashboardMetrics,
        private readonly FinancialReportInterface $financialReport,
        private readonly ArticleRepository $articleRepository,
        private readonly ShopSettingsProviderInterface $shopSettingsProvider,
        private readonly ClockInterface $clock,
    ) {
    }

    public function index(): Response
    {
        $now = $this->clock->now();
        $threshold = $this->shopSettingsProvider->get()->getLowStockThreshold();

        return $this->render('admin/dashboard.html.twig', [
            'ordersToProcess' => $this->dashboardMetrics->ordersToProcess(),
            'orderCounts' => $this->dashboardMetrics->countOrdersToProcess(),
            'lowStock' => array_map(fn (Article $article): array => [
                'article' => $article,
                'editUrl' => $this->articleEditUrl($article),
            ], $this->articleRepository->findLowStock($threshold)),
            'lowStockThreshold' => $threshold,
            'revenues' => array_map(
                fn (RevenuePeriod $period) => $this->dashboardMetrics->revenue($period, $now),
                RevenuePeriod::cases(),
            ),
            'payments' => $this->dashboardMetrics->paymentBreakdown($now->modify('-30 days')),
        ]);
    }

    #[AdminRoute(path: '/finances', name: 'finances', options: ['methods' => ['GET']])]
    public function finances(Request $request): Response
    {
        $years = $this->financialReport->years($this->clock->now());
        $year = $this->selectedYear($request, $years);
        $months = $this->financialReport->monthly($year);

        return $this->render('admin/finances.html.twig', [
            'year' => $year,
            'years' => $years,
            'months' => $months,
            'totals' => [
                'orders' => array_sum(array_column($months, 'orders')),
                'revenue' => array_sum(array_column($months, 'revenue')),
                'refunded' => array_sum(array_column($months, 'refunded')),
            ],
        ]);
    }

    /**
     * Semicolons and a byte order mark so that the file opens as columns in a French Excel.
     */
    #[AdminRoute(path: '/finances/export', name: 'finances_export', options: ['methods' => ['GET']])]
    public function exportFinances(Request $request): StreamedResponse
    {
        $year = $this->selectedYear($request, $this->financialReport->years($this->clock->now()));
        $payments = $this->financialReport->payments($year);

        $response = new StreamedResponse(static function () use ($payments): void {
            $output = fopen('php://output', 'w');
            \assert(false !== $output);
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Date de paiement', 'Référence', 'Client', 'Mode de paiement', 'Paiement', 'Statut', 'Montant (€)'], ';', '"', '');

            foreach ($payments as $payment) {
                fputcsv($output, [
                    $payment['paidAt']->format('d/m/Y H:i'),
                    $payment['reference'],
                    $payment['buyer'],
                    $payment['paymentMethod'],
                    $payment['paymentStatus'],
                    $payment['status'],
                    number_format($payment['total'] / 100, 2, ',', ''),
                ], ';', '"', '');
            }

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            \sprintf('kbr-paiements-%d.csv', $year),
        ));

        return $response;
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
            MenuItem::linkToRoute('Synthèse financière', 'fas fa-chart-column', 'admin_finances'),
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

    /**
     * @param list<int> $years
     */
    private function selectedYear(Request $request, array $years): int
    {
        $year = $request->query->getInt('year', $years[0]);

        return \in_array($year, $years, true) ? $year : $years[0];
    }

    /**
     * Variants are edited inside their merch design.
     */
    private function articleEditUrl(Article $article): string
    {
        [$controller, $entityId] = match (true) {
            $article instanceof Release => [ReleaseCrudController::class, $article->getId()],
            $article instanceof Book => [BookCrudController::class, $article->getId()],
            $article instanceof MerchVariant => [MerchCrudController::class, $article->getMerch()?->getId()],
            default => throw new \LogicException(\sprintf('No back-office page for %s.', $article::class)),
        };

        return $this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController($controller)
            ->setAction(Action::EDIT)
            ->setEntityId($entityId)
            ->generateUrl();
    }
}
