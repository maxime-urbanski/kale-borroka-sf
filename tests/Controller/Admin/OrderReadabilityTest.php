<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Order;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The label handles an order from its admin page: who bought (name, e-mail, phone), in French,
 * the buyer's message first, and how much of each line is left on the shelf.
 */
class OrderReadabilityTest extends WebTestCase
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

    public function testTheListNamesTheBuyerAndSpeaksFrench(): void
    {
        $this->order();

        $crawler = $this->client->request('GET', '/admin/order');

        self::assertResponseIsSuccessful();
        $row = $crawler->filter('tbody tr')->first()->text();
        self::assertStringContainsString('Test Test', $row);
        self::assertStringContainsString('test@test.fr', $row);
        self::assertStringNotContainsString('User #', $row);
        self::assertStringContainsString('En attente de paiement', $row);
        self::assertStringNotContainsString('pending', $row);
        self::assertStringNotContainsString('awaiting', $row);
    }

    public function testTheDetailShowsHowToReachTheBuyer(): void
    {
        $order = $this->order();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href="mailto:test@test.fr"]'));
        self::assertStringContainsString('Test Test', $crawler->filter('body')->text());
    }

    public function testTheMessageComesFirst(): void
    {
        $order = $this->order('Emballez bien le vinyle svp');

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));

        $message = $crawler->filter('.kb-admin-message');
        self::assertCount(1, $message);
        self::assertStringContainsString('Emballez bien le vinyle svp', $message->text());
        // Above the tabs, not hidden in the Livraison one.
        self::assertCount(0, $crawler->filter('.nav-tabs .kb-admin-message, .tab-pane .kb-admin-message'));
    }

    public function testEachLineShowsTheStockLeft(): void
    {
        $order = $this->order();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));

        $lines = $crawler->filter('.kb-order-lines');
        self::assertStringContainsString('Stock', $lines->filter('thead')->text());
        self::assertStringContainsString('7', $lines->filter('tbody tr')->first()->filter('.badge')->text());
    }

    private function order(?string $message = null): Order
    {
        [$release] = $this->releasesWithStock(7);
        $order = $this->placeOrder($this->user('test@test.fr'), [self::idOf($release) => 2]);

        if (null !== $message) {
            $order->setCustomerMessage($message);
            $this->entityManager()->flush();
        }

        return $order;
    }
}
