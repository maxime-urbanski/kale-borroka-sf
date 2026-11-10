<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cart;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Step 1 of the checkout (mockup « Tunnel d'achat »): stepper, lines, recap, empty state,
 * « vider » confirmation, visitor variant.
 */
class CartPageTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testTheStepperMarksTheCartStep(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $this->put($release, 2);

        $crawler = $this->client->request('GET', '/cart');

        self::assertResponseIsSuccessful();
        self::assertCount(3, $crawler->filter('.kb-steps__step'));
        self::assertSame('Panier', trim($crawler->filter('.kb-steps__step[aria-current="step"] .kb-steps__label')->text()));
    }

    public function testTheRecapLeadsAMemberToTheOrderStep(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $this->put($release, 2);

        $crawler = $this->client->request('GET', '/cart');

        self::assertCount(1, $crawler->filter('.kb-recap a:contains("Passer commande")'));
        self::assertCount(0, $crawler->filter('.kb-recap a:contains("Se connecter pour commander")'));
    }

    public function testTheRecapAsksAVisitorToLogIn(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->put($release, 1);

        $crawler = $this->client->request('GET', '/cart');

        self::assertSame('/login', $crawler->filter('.kb-recap a:contains("Se connecter pour commander")')->attr('href'));
        self::assertSame('/register', $crawler->filter('.kb-recap a:contains("Créer un compte")')->attr('href'));
        self::assertCount(0, $crawler->filter('.kb-recap a:contains("Passer commande")'));
    }

    public function testTheHeaderAndTheConfirmationCountTheCopies(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->put($release, 3);

        self::assertStringContainsString('3 articles', $this->client->request('GET', '/cart')->filter('.kb-cart__count')->text());
        self::assertStringContainsString('3 articles retournent', $this->client->request('GET', '/cart?vider')->filter('.kb-cart-confirm')->text());
    }

    public function testTheLastCopyIsFlagged(): void
    {
        [$release] = $this->releasesWithStock(1);
        $this->put($release, 1);

        $crawler = $this->client->request('GET', '/cart');

        self::assertStringContainsString('Dernier exemplaire', $crawler->filter('.kb-cart-line')->text());
    }

    public function testAnEmptyCartHasItsStampAndWaysOut(): void
    {
        $crawler = $this->client->request('GET', '/cart');

        self::assertSame('Vide', trim($crawler->filter('.kb-stamp')->text()));
        self::assertCount(1, $crawler->filter('.kb-cart-empty a[href="/catalog"]:contains("Fouiller le catalogue")'));
        self::assertCount(1, $crawler->filter('.kb-cart-empty a[href="/production"]:contains("Nos productions")'));
        self::assertCount(0, $crawler->filter('.kb-recap'));
    }

    public function testEmptyingTheCartIsConfirmed(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->put($release, 2);

        $crawler = $this->client->request('GET', '/cart');
        self::assertStringNotContainsString('Vider ton panier ?', $crawler->text());
        self::assertSame('/cart?vider', $crawler->filter('a:contains("Vider le panier")')->attr('href'));

        $crawler = $this->client->request('GET', '/cart?vider');
        self::assertStringContainsString('Vider ton panier ?', $crawler->text());
        self::assertSame('/cart', $crawler->filter('a:contains("Non, garder mon panier")')->attr('href'));

        $this->client->submit($crawler->filter('form[action="/cart/empty_cart"]')->form());
        $crawler = $this->client->request('GET', '/cart');
        self::assertCount(0, $crawler->filter('.kb-cart-line'));
    }

    private function put(Article $article, int $quantity): void
    {
        $crawler = $this->client->request('GET', \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug()));
        $this->client->submit($crawler->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => (string) $quantity,
        ]));
    }
}
