<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Order;
use App\Entity\Transporter;
use App\Enum\OrderTransition;
use App\Messenger\CommandBusInterface;
use App\Order\Command\ApplyOrderTransition;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The checkout offers no delivery mode: the label works the shipping out by hand and sets it
 * on the order (transporter + amount) before sending the payment link. Only while the order
 * waits for its payment: once paid, the amount is what was paid.
 */
class OrderShippingTest extends WebTestCase
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

    public function testAPendingOrderOffersToSetTheShipping(): void
    {
        $order = $this->pendingOrder();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter(\sprintf('a[href$="/admin/order/%d/shipping"]', $order->getId())));
    }

    public function testSettingTheShippingAddsItToTheTotal(): void
    {
        $order = $this->pendingOrder();
        $transporter = $this->transporter();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
            'order_shipping[transporter]' => (string) $transporter->getId(),
            'order_shipping[shippingPrice]' => '6,50',
        ]));

        self::assertResponseRedirects(\sprintf('/admin/order/%d', $order->getId()));
        $order = $this->reload($order);
        self::assertSame(650, $order->getShippingPrice());
        self::assertSame(2000 + 650, $order->getTotalPrice());
        self::assertSame($transporter->getId(), $order->getDelivery()?->getId());

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Port de 6,50 € enregistré');
    }

    public function testFreeShippingIsZeroNotEmpty(): void
    {
        $order = $this->pendingOrder();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
            'order_shipping[transporter]' => (string) $this->transporter()->getId(),
            'order_shipping[shippingPrice]' => '0',
        ]));

        self::assertResponseRedirects();
        self::assertSame(2000, $this->reload($order)->getTotalPrice());
        self::assertNotNull($this->reload($order)->getDelivery());
    }

    public function testAMissingOrNegativeAmountIsRefused(): void
    {
        $order = $this->pendingOrder();

        foreach (['', '-1'] as $amount) {
            $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
            $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
                'order_shipping[transporter]' => (string) $this->transporter()->getId(),
                'order_shipping[shippingPrice]' => $amount,
            ]));

            self::assertResponseIsUnprocessable($amount);
        }
        self::assertSame(2000, $this->reload($order)->getTotalPrice());
        self::assertNull($this->reload($order)->getDelivery());
    }

    public function testAPaidOrderKeepsItsShipping(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user('test@test.fr'), [self::idOf($release) => 2]);
        self::service(CommandBusInterface::class)->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));
        self::assertCount(0, $crawler->filter('a[href$="/shipping"]'));

        $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        self::assertResponseRedirects(\sprintf('/admin/order/%d', $order->getId()));
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'payée');
        self::assertSame(2000, $this->reload($order)->getTotalPrice());
    }

    /**
     * The payment link carries the total: an order cannot be paid before its shipping is set.
     */
    public function testAnOrderCannotBePaidBeforeItsShippingIsSet(): void
    {
        $order = $this->pendingOrder();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));
        self::assertCount(0, $crawler->filter('form[action*="/transition/pay"]'));
        self::assertSelectorTextContains('.alert-warning', 'Fixe le port');

        $this->setShipping($order, '6,50');

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));
        self::assertCount(1, $crawler->filter('form[action*="/transition/pay"]'));
    }

    public function testAnAmountOutOfRangeIsRefused(): void
    {
        $order = $this->pendingOrder();

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
            'order_shipping[transporter]' => (string) $this->transporter()->getId(),
            'order_shipping[shippingPrice]' => '30000000',
        ]));

        self::assertResponseIsUnprocessable();
    }

    public function testAForgedTokenSavesNothing(): void
    {
        $order = $this->pendingOrder();

        $this->client->request('POST', \sprintf('/admin/order/%d/shipping', $order->getId()), ['order_shipping' => [
            'transporter' => (string) $this->transporter()->getId(),
            'shippingPrice' => '6,50',
            '_token' => 'forged',
        ]]);

        self::assertResponseIsUnprocessable();
        self::assertNull($this->reload($order)->getDelivery());
    }

    public function testTheCustomerSeesTheAmountOnceSet(): void
    {
        $order = $this->pendingOrder();
        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
            'order_shipping[transporter]' => (string) $this->transporter()->getId(),
            'order_shipping[shippingPrice]' => '6,50',
        ]));

        $this->client->loginUser($this->user('test@test.fr'));
        $crawler = $this->client->request('GET', '/mon-compte/mes-commandes/'.$order->getReference());

        $recap = $crawler->filter('.kb-order-recap')->text();
        self::assertStringContainsString('6,50', $recap);
        self::assertStringNotContainsString('À calculer', $recap);
    }

    private function pendingOrder(): Order
    {
        [$release] = $this->releasesWithStock(5);

        return $this->placeOrder($this->user('test@test.fr'), [self::idOf($release) => 2], shippingSet: false);
    }

    private function setShipping(Order $order, string $amount): void
    {
        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d/shipping', $order->getId()));
        $this->client->submit($crawler->filter('form[name="order_shipping"]')->form([
            'order_shipping[transporter]' => (string) $this->transporter()->getId(),
            'order_shipping[shippingPrice]' => $amount,
        ]));
        self::assertResponseRedirects();
    }

    private function reload(Order $order): Order
    {
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Order::class, $order->getId());
        self::assertInstanceOf(Order::class, $reloaded);

        return $reloaded;
    }

    private function transporter(): Transporter
    {
        $transporter = $this->entityManager()->getRepository(Transporter::class)->findOneBy([]);
        self::assertInstanceOf(Transporter::class, $transporter);

        return $transporter;
    }
}
