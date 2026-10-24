<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Entity\User;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * The payment placeholder is guarded like the order's page (AccountOrdersTest): it used to
 * be reachable by anyone who knew (or guessed) a reference.
 */
class OrderPaymentControllerTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

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

    public function testPaymentPageIsGuardedLikeTheOrdersPage(): void
    {
        $reference = $this->referenceOf($this->user('maxiloud@gmail.com'));
        $payment = \sprintf('/order/%s/payment/choice', $reference);

        $this->client->request('GET', $payment);
        self::assertResponseRedirects('/login');

        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', $payment);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $this->client->request('GET', $payment);
        self::assertResponseRedirects('/mon-compte/mes-commandes/'.$reference);
    }

    private function referenceOf(User $buyer): string
    {
        [$release] = $this->releasesWithStock(5);

        return (string) $this->placeOrder($buyer, [self::idOf($release) => 1])->getReference();
    }
}
