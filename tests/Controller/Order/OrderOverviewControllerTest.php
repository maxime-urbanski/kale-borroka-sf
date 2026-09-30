<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * The overview used to show any order to anyone who knew (or guessed) its reference.
 */
class OrderOverviewControllerTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        // Same kernel for every request, so they all see the test transaction.
        $this->client->disableReboot();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testTheBuyerSeesTheirOrder(): void
    {
        $buyer = $this->user('test@test.fr');
        $uri = $this->overviewUri($buyer);

        $this->client->loginUser($buyer);
        $this->client->request('GET', $uri);

        self::assertResponseIsSuccessful();
    }

    public function testAnotherCustomerIsDenied(): void
    {
        $uri = $this->overviewUri($this->user('maxiloud@gmail.com'));

        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', $uri);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymousVisitorIsSentToTheLoginPage(): void
    {
        $this->client->request('GET', $this->overviewUri($this->user('test@test.fr')));

        self::assertResponseRedirects('/login');
    }

    public function testUnknownReferenceIsNotFound(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', '/order/KBR-nope/overview');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPaymentPageIsGuardedLikeTheOverview(): void
    {
        $overview = $this->overviewUri($this->user('maxiloud@gmail.com'));
        $payment = str_replace('/overview', '/payment/choice', $overview);

        $this->client->request('GET', $payment);
        self::assertResponseRedirects('/login');

        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', $payment);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $this->client->request('GET', $payment);
        self::assertResponseRedirects($overview);
    }

    private function overviewUri(\App\Entity\User $buyer): string
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($buyer, [$release->getId() => 1]);

        return \sprintf('/order/%s/overview', $order->getReference());
    }
}
