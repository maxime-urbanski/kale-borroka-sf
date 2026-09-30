<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cart;

use App\Entity\Article;
use App\Entity\WishlistItem;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cart, wishlist and collection actions change state: POST with a CSRF token only, so
 * that another site cannot trigger them through a link or an image.
 */
class CartActionsTest extends WebTestCase
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function actions(): iterable
    {
        yield 'add' => ['/cart/add/%d'];
        yield 'add quantity' => ['/cart/add_quantity/%d'];
        yield 'remove quantity' => ['/cart/remove_quantity/%d'];
        yield 'remove' => ['/cart/remove/%d'];
        yield 'empty' => ['/cart/empty_cart'];
        yield 'wishlist add' => ['/wishlist/add/%d'];
        yield 'wishlist remove' => ['/wishlist/remove/%d'];
        yield 'collection add' => ['/collection/add/%d'];
        yield 'collection remove' => ['/collection/remove/%d'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('actions')]
    public function testActionsRefuseGet(string $uri): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);

        $this->client->request('GET', \sprintf($uri, $release->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCartActionsNeedTheToken(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addFromTheArticlePage($release, 2);
        self::assertSame(2, $this->quantityInCart($release));

        $this->client->request('POST', '/cart/empty_cart', ['_token' => 'forged']);
        self::assertSame(2, $this->quantityInCart($release));

        // The buttons of the cart page carry the token.
        $crawler = $this->client->request('GET', '/cart');
        $this->client->submit($crawler->filter(\sprintf('form[action="/cart/add_quantity/%d"]', $release->getId()))->form());
        self::assertSame(3, $this->quantityInCart($release));

        $crawler = $this->client->request('GET', '/cart');
        $this->client->submit($crawler->filter('form[action="/cart/empty_cart"]')->form());
        self::assertSame(0, $this->quantityInCart($release));
    }

    public function testQuantityStaysBetweenOneAndTheStock(): void
    {
        [$release] = $this->releasesWithStock(5);

        // Refused by the form: nothing in the cart.
        foreach (['0', '-5'] as $quantity) {
            $this->addFromTheArticlePage($release, (int) $quantity);
            self::assertSame(0, $this->quantityInCart($release), $quantity);
        }

        $this->addFromTheArticlePage($release, 99);
        self::assertSame(5, $this->quantityInCart($release), 'capped to the stock');

        // Even straight to the cart action, which skips the form's validation.
        $crawler = $this->client->request('GET', '/cart');
        $token = (string) $crawler->filter(\sprintf('form[action="/cart/add_quantity/%d"] input[name="_token"]', $release->getId()))->attr('value');
        $this->client->request('POST', \sprintf('/cart/remove/%d', $release->getId()), ['_token' => $token]);
        $this->client->request('POST', \sprintf('/cart/add/%d', $release->getId()), ['_token' => $token, 'quantity' => '-5']);
        self::assertSame(1, $this->quantityInCart($release));
    }

    public function testCartIsCappedWhenTheStockDrops(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addFromTheArticlePage($release, 4);

        // Stock changes through SQL, as when StockManager takes it for a paid order.
        $this->setStock($release, 2);
        self::assertSame(2, $this->quantityInCart($release));

        $this->setStock($release, 0);
        self::assertSame(0, $this->quantityInCart($release), 'sold out: the line is dropped');
    }

    /**
     * The actions redirect to the page they were posted from. Matching that page used to run
     * with the POST method, so every GET-only page threw and each click ended on a 500.
     */
    public function testEachButtonGoesBackToItsPage(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $article = $this->articleUri($release);
        $this->addFromTheArticlePage($release, 2);

        foreach (['add_quantity', 'remove_quantity', 'remove'] as $action) {
            $crawler = $this->client->request('GET', '/cart');
            $this->client->submit($crawler->filter(\sprintf('form[action="/cart/%s/%d"]', $action, $release->getId()))->form());
            self::assertResponseRedirects('/cart', null, $action);
        }

        foreach (['wishlist', 'collection'] as $list) {
            $crawler = $this->client->request('GET', $article);
            $this->client->submit($crawler->filter(\sprintf('form[action="/%s/add/%d"]', $list, $release->getId()))->form());
            self::assertResponseRedirects($article, null, $list.' add');

            $crawler = $this->client->request('GET', $article);
            $this->client->submit($crawler->filter(\sprintf('form[action="/%s/remove/%d"]', $list, $release->getId()))->form());
            self::assertResponseRedirects($article, null, $list.' remove');
        }
    }

    public function testAnUnknownRefererGoesHome(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addFromTheArticlePage($release, 1);
        $crawler = $this->client->request('GET', '/cart');
        $token = (string) $crawler->filter(\sprintf('form[action="/cart/add_quantity/%d"] input[name="_token"]', $release->getId()))->attr('value');

        $this->client->request('POST', \sprintf('/cart/add_quantity/%d', $release->getId()), ['_token' => $token], [], ['HTTP_REFERER' => 'https://elsewhere.example/nowhere']);
        self::assertResponseRedirects('/');
    }

    public function testWishlistNeedsTheToken(): void
    {
        $user = $this->user('test@test.fr');
        $this->client->loginUser($user);
        [$release] = $this->releasesWithStock(5);
        $items = $this->entityManager()->getRepository(WishlistItem::class);
        $before = $items->count();

        $this->client->request('POST', \sprintf('/wishlist/add/%d', $release->getId()), ['_token' => 'forged']);
        self::assertSame($before, $items->count());

        $crawler = $this->client->request('GET', $this->articleUri($release));
        $this->client->submit($crawler->filter(\sprintf('form[action="/wishlist/add/%d"]', $release->getId()))->form());
        self::assertSame($before + 1, $items->count());
    }

    private function addFromTheArticlePage(Article $article, int $quantity): void
    {
        $crawler = $this->client->request('GET', $this->articleUri($article));
        $form = $crawler->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => (string) $quantity,
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects($this->articleUri($article), Response::HTTP_SEE_OTHER);
    }

    private function setStock(Article $article, int $stock): void
    {
        $this->entityManager()->getConnection()->executeStatement('UPDATE article SET stock = ? WHERE id = ?', [$stock, $article->getId()]);
    }

    private function quantityInCart(Article $article): int
    {
        $crawler = $this->client->request('GET', '/cart');
        $row = $crawler->filter(\sprintf('form[action="/cart/remove/%d"]', $article->getId()));

        return 0 === $row->count() ? 0 : (int) $row->closest('tr')?->filter('input.form-control')->attr('value');
    }

    private function articleUri(Article $article): string
    {
        return \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug());
    }
}
