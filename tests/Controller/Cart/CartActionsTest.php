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

    private ?KernelBrowser $client = null;

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
