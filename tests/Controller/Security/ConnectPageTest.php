<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Entity\Article;
use App\Entity\Order;
use App\Repository\UserRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Response;

/**
 * /login and /register show both forms (App\Security\ConnectPage): the route picks the h1.
 */
class ConnectPageTest extends WebTestCase
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

    public function testTheRouteGivesTheHeading(): void
    {
        $this->client->request('GET', '/login');
        self::assertSelectorTextSame('h1', 'Connexion');
        self::assertSelectorTextSame('h2#connect-register-title', "Nouveau ici\u{a0}?");

        $this->client->request('GET', '/register');
        self::assertSelectorTextSame('h1', "Nouveau ici\u{a0}?");
        self::assertSelectorTextSame('h2#connect-login-title', 'Connexion');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function weakPasswords(): iterable
    {
        yield 'too short' => ['Kale-2026', 'au moins 12 caractères'];
        yield 'letters only' => ['kaleborrokaa', 'un chiffre ou un symbole'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('weakPasswords')]
    public function testAWeakPasswordIsRefused(string $password, string $message): void
    {
        $users = self::service(UserRepository::class);
        $count = $users->count();

        $this->register('weak@example.org', $password);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSelectorTextContains('#registration_form_plainPassword_error1', $message);
        self::assertSelectorExists('#registration_form_plainPassword[aria-invalid="true"][aria-describedby~="registration_form_plainPassword_error1"]');
        self::assertSame($count, $users->count());
    }

    public function testRegisteringLogsIn(): void
    {
        $this->register('new@example.org', 'Kale-Borroka-2026');

        self::assertResponseRedirects('/');
        $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful();
    }

    public function testNoCartBannerOutsideTheOrderTunnel(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addToCart($release, 2);

        $this->client->request('GET', '/login');

        self::assertSelectorNotExists('.kb-connect__cart');
        self::assertSelectorTextSame('form[name="login"] button[type="submit"]', 'Se connecter');
    }

    public function testAnotherOrderPageIsNotTheCart(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addToCart($release, 2);

        $order = $this->entityManager()->getRepository(Order::class)->findOneBy([]);
        self::assertNotNull($order);

        $this->client->request('GET', \sprintf('/order/%s/overview', $order->getReference()));
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        self::assertSelectorNotExists('.kb-connect__cart');
    }

    public function testComingFromTheCartShowsItAndGoesBackToTheTunnel(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addToCart($release, 2);

        $this->client->request('GET', '/order/delivery');
        self::assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();

        self::assertSelectorTextContains('.kb-connect__cart', '(2 articles · ');
        self::assertSelectorTextContains('.breadcrumb', 'Panier');

        $this->client->submit($crawler->filter('form[name="login"]')->form([
            'email' => 'test@test.fr',
            'password' => 'password123',
        ]));
        self::assertResponseRedirects('http://localhost/order/delivery');
    }

    private function register(string $email, string $password): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[firstname]' => 'Maialen',
            'registration_form[lastname]' => 'Etxeberria',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => $password,
        ]);
        $agreeTerms = $form['registration_form[agreeTerms]'];
        self::assertInstanceOf(ChoiceFormField::class, $agreeTerms);
        $agreeTerms->tick();
        $this->client->submit($form);
    }

    private function addToCart(Article $article, int $quantity): void
    {
        $uri = \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug());
        $crawler = $this->client->request('GET', $uri);
        $this->client->submit($crawler->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => (string) $quantity,
        ]));
        self::assertResponseRedirects($uri, Response::HTTP_SEE_OTHER);
    }
}
