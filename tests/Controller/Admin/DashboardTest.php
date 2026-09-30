<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Enum\OrderTransition;
use App\Order\Command\ApplyOrderTransition;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testDashboardShowsOrdersToProcessStockAlertsAndRevenue(): void
    {
        [$release, $lowStock] = $this->releasesWithStock(5, 0);
        $pending = $this->placeOrder($this->user('test@test.fr'), [(int) $release->getId() => 1]);
        $paid = $this->placeOrder($this->user('test@test.fr'), [(int) $release->getId() => 1]);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $paid->getId(), OrderTransition::PAY));

        $crawler = $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        $orders = $crawler->filter('[data-test="orders-to-process"]')->text();
        self::assertStringContainsString((string) $pending->getReference(), $orders);
        self::assertStringContainsString((string) $paid->getReference(), $orders);
        self::assertStringContainsString((string) $lowStock->getName(), $crawler->filter('[data-test="low-stock"]')->text());
        // What the buyer paid: the line and the shipping.
        self::assertStringContainsString(self::euros((int) $paid->getTotalPrice()), $crawler->filter('[data-test="revenue-day"]')->text());
    }

    public function testFinancesPageAndExport(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user('test@test.fr'), [(int) $release->getId() => 1]);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));
        $year = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y');

        $crawler = $this->client->request('GET', '/admin/finances');
        self::assertResponseIsSuccessful();
        self::assertCount(12, $crawler->filter('[data-test="finances"] tbody tr'));

        $this->client->request('GET', '/admin/finances/export?year='.$year);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('kbr-paiements-'.$year.'.csv', (string) $this->client->getResponse()->headers->get('Content-Disposition'));

        $csv = (string) $this->client->getInternalResponse()->getContent();
        self::assertStringStartsWith("\u{FEFF}\"Date de paiement\";", $csv);
        self::assertStringContainsString($order->getReference().';test@test.fr;', $csv);
        self::assertStringContainsString(';'.self::euros((int) $order->getTotalPrice()), $csv);
    }

    public function testUnknownYearFallsBackToTheCurrentOne(): void
    {
        $crawler = $this->client->request('GET', '/admin/finances?year=1789');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString((new \DateTimeImmutable())->format('Y'), $crawler->filter('h1')->text());
    }

    private static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}
