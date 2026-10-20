<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * « Tout ajouter au panier » (maquette 11): every wantlist record on sale goes to the cart
 * once; sold-out ones are left out, and the wantlist keeps them all.
 */
class WantlistAddAllToCartTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('test@test.fr'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testEveryRecordOnSaleGoesToTheCartOnce(): void
    {
        [$onSale, $soldOut] = $this->wantlist();
        $this->stock($onSale, 4);
        $this->stock($soldOut, 0);

        $this->submitAddAll();

        self::assertResponseRedirects('/mon-compte/wishlist', Response::HTTP_SEE_OTHER);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.toast', '1 article ajouté au panier.');
        self::assertSame(1, $this->quantityInCart($onSale));
        self::assertSame(0, $this->quantityInCart($soldOut));
        self::assertCount(2, $this->wantlist(), 'the wantlist keeps them');
    }

    public function testARecordAlreadyInTheCartIsNotAddedTwice(): void
    {
        [$first, $second] = $this->wantlist();
        $this->stock($first, 5);
        $this->stock($second, 5);
        $crawler = $this->client->request('GET', '/mon-compte/wishlist');
        $this->client->submit($crawler->filter(\sprintf('.kb-want form[action="/cart/add/%d"]', $first->getArticle()?->getId()))->form());

        $this->submitAddAll();

        self::assertSame(1, $this->quantityInCart($first));
        self::assertSame(1, $this->quantityInCart($second));
    }

    public function testTheButtonShowsTheTotalOfWhatIsOnSale(): void
    {
        [$first, $second] = $this->wantlist();
        $this->stock($first, 1, 1500);
        $this->stock($second, 2, 1779);

        $crawler = $this->client->request('GET', '/mon-compte/wishlist');

        $button = $crawler->filter('form[action="/wishlist/add-all-to-cart"] button')->text();
        self::assertStringContainsString('Tout ajouter au panier · 32,79 €', preg_replace('/\s+/u', ' ', $button) ?? '');
    }

    public function testTheTotalLeavesOutWhatIsAlreadyInTheCart(): void
    {
        [$first, $second] = $this->wantlist();
        $this->stock($first, 1, 1500);
        $this->stock($second, 2, 1779);
        $crawler = $this->client->request('GET', '/mon-compte/wishlist');
        $this->client->submit($crawler->filter(\sprintf('.kb-want form[action="/cart/add/%d"]', $first->getArticle()?->getId()))->form());

        $crawler = $this->client->request('GET', '/mon-compte/wishlist');

        $button = $crawler->filter('form[action="/wishlist/add-all-to-cart"] button')->text();
        self::assertStringContainsString('Tout ajouter au panier · 17,79 €', preg_replace('/\s+/u', ' ', $button) ?? '');
    }

    public function testOnceEverythingIsInTheCartTheButtonLeadsToIt(): void
    {
        [$first, $second] = $this->wantlist();
        $this->stock($first, 1);
        $this->stock($second, 2);
        $this->submitAddAll();

        $crawler = $this->client->request('GET', '/mon-compte/wishlist');

        self::assertSelectorNotExists('form[action="/wishlist/add-all-to-cart"]');
        self::assertSame('Tout est dans ton panier', trim($crawler->filter('.kb-wants__all')->text()));
        self::assertSame('/cart', $crawler->filter('.kb-wants__all')->attr('href'));
    }

    public function testNoButtonWhenNothingIsOnSale(): void
    {
        foreach ($this->wantlist() as $item) {
            $this->stock($item, 0);
        }

        $this->client->request('GET', '/mon-compte/wishlist');

        self::assertSelectorNotExists('form[action="/wishlist/add-all-to-cart"]');
        self::assertSelectorNotExists('.kb-wants__all');
    }

    public function testAForgedTokenAddsNothing(): void
    {
        [$onSale] = $this->wantlist();
        $this->stock($onSale, 4);

        $this->client->request('POST', '/wishlist/add-all-to-cart', ['_token' => 'forged']);

        self::assertSame(0, $this->quantityInCart($onSale));
    }

    public function testGetIsRefused(): void
    {
        $this->client->request('GET', '/wishlist/add-all-to-cart');

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testAVisitorIsSentToTheLoginForm(): void
    {
        $this->client->getCookieJar()->clear();

        $this->client->request('POST', '/wishlist/add-all-to-cart', ['_token' => 'whatever']);

        self::assertResponseRedirects('/login');
    }

    private function submitAddAll(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/wishlist');
        $this->client->submit($crawler->filter('form[action="/wishlist/add-all-to-cart"]')->form());
    }

    /**
     * @return list<WishlistItem>
     */
    private function wantlist(): array
    {
        $wishlist = self::service(WishlistRepository::class)->findOneBy(['user' => $this->user('test@test.fr')]);
        $items = self::service(WishlistItemRepository::class)->findBy(['wishlist' => $wishlist], ['article' => 'ASC']);
        self::assertCount(2, $items);

        return $items;
    }

    private function stock(WishlistItem $item, int $stock, ?int $price = null): void
    {
        $article = $item->getArticle() ?? throw new \LogicException('no article');
        $article->setStock($stock)->setPublished(true);
        if (null !== $price) {
            $article->setPrice($price);
        }
        $this->entityManager()->flush();
    }

    private function quantityInCart(WishlistItem $item): int
    {
        $article = $item->getArticle();
        $crawler = $this->client->request('GET', '/cart');
        $row = $crawler->filter(\sprintf('form[action="/cart/remove/%d"]', $article?->getId()));

        return 0 === $row->count() ? 0 : (int) $row->closest('tr')?->filter('input.form-control')->attr('value');
    }
}
