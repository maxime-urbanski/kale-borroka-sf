<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cart;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * A one-click action whose target changed meanwhile (an article unpublished, already in
 * the list, already removed from another tab): the visitor goes back to the page with a
 * French message, never a 500 nor the text of an exception.
 */
class ActionMessagesTest extends WebTestCase
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

    public function testAddingAnArticleUnpublishedMeanwhile(): void
    {
        [$release] = $this->releasesWithStock(5);
        $token = (string) $this->client->request('GET', '/cart')->filter('form[action="/cart/empty_cart"] input[name="_token"]')->attr('value');
        $this->unpublish($release);

        $this->client->request('POST', \sprintf('/cart/add/%d', $release->getId()), ['_token' => $token], [], ['HTTP_REFERER' => '/cart']);

        $this->assertFriendlyMessage('Cet article n\'est plus disponible.');
    }

    public function testRaisingTheQuantityOfAnArticleUnpublishedMeanwhile(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->client->submit($this->client->request('GET', $this->articleUri($release))->filter('form[name="add_to_cart_with_quantity"]')->form());
        $plus = $this->button('/cart', \sprintf('/cart/add_quantity/%d', $release->getId()));
        $this->unpublish($release);

        $this->client->submit($plus);

        $this->assertFriendlyMessage('Cet article n\'est plus disponible.');
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function lists(): iterable
    {
        yield 'wishlist' => ['wishlist', 'Cet article est déjà dans ta wantlist.', 'Cet article n\'est pas dans ta wantlist.'];
        yield 'collection' => ['collection', 'Cet article est déjà dans ta collection.', 'Cet article n\'est pas dans ta collection.'];
    }

    /**
     * Two tabs open on the article page: "Ajouter" clicked in both.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function testAddingTwice(string $list, string $alreadyIn, string $notIn): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $add = $this->button($this->articleUri($release), \sprintf('/%s/add/%d', $list, $release->getId()));

        $this->client->submit($add);
        $this->client->submit($add);

        $this->assertFriendlyMessage($alreadyIn);
    }

    /**
     * Two tabs open on the article page: "Retirer" clicked in both.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function testRemovingTwice(string $list, string $alreadyIn, string $notIn): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $this->client->submit($this->button($this->articleUri($release), \sprintf('/%s/add/%d', $list, $release->getId())));
        $remove = $this->button($this->articleUri($release), \sprintf('/%s/remove/%d', $list, $release->getId()));

        $this->client->submit($remove);
        $this->client->submit($remove);

        $this->assertFriendlyMessage($notIn);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function testAddingAnArticleUnpublishedMeanwhileToAList(string $list, string $alreadyIn, string $notIn): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $add = $this->button($this->articleUri($release), \sprintf('/%s/add/%d', $list, $release->getId()));
        $this->unpublish($release);

        $this->client->submit($add);

        $this->assertFriendlyMessage('Cet article n\'est plus disponible.');
    }

    /**
     * Accounts created before the lists existed have no wishlist or collection row: the
     * first add creates it.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function testAnAccountWithoutTheListGetsItOnTheFirstAdd(string $list, string $alreadyIn, string $notIn): void
    {
        $this->client->loginUser($user = $this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $table = 'wishlist' === $list ? 'wishlist' : 'user_collection';
        $items = 'wishlist' === $list ? 'wishlist_item' : 'user_collection_items';
        $foreignKey = 'wishlist' === $list ? 'wishlist_id' : 'collection_id';
        $connection = $this->entityManager()->getConnection();
        $connection->executeStatement(\sprintf('DELETE FROM %s WHERE %s IN (SELECT id FROM %s WHERE user_id = ?)', $items, $foreignKey, $table), [$user->getId()]);
        $connection->executeStatement(\sprintf('DELETE FROM %s WHERE user_id = ?', $table), [$user->getId()]);
        $remove = \sprintf('/%s/remove/%d', $list, $release->getId());
        $token = (string) $this->client->request('GET', $this->articleUri($release))->filter(\sprintf('form[action="/%s/add/%d"] input[name="_token"]', $list, $release->getId()))->attr('value');

        $this->client->request('POST', $remove, ['_token' => $token], [], ['HTTP_REFERER' => $this->articleUri($release)]);
        $this->assertFriendlyMessage($notIn);

        $this->client->submit($this->button($this->articleUri($release), \sprintf('/%s/add/%d', $list, $release->getId())));
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertStringContainsString('a bien été ajouté', $this->toasts());
        self::assertSame(1, (int) $connection->fetchOne(\sprintf('SELECT count(*) FROM %s WHERE user_id = ?', $table), [$user->getId()]));
    }

    public function testAddingASoldOutArticle(): void
    {
        [$release] = $this->releasesWithStock(0);
        $token = (string) $this->client->request('GET', '/cart')->filter('form[action="/cart/empty_cart"] input[name="_token"]')->attr('value');

        $this->client->request('POST', \sprintf('/cart/add/%d', $release->getId()), ['_token' => $token], [], ['HTTP_REFERER' => '/cart']);

        $this->assertFriendlyMessage('Cet article est épuisé.');
    }

    public function testRaisingTheQuantityAboveTheStock(): void
    {
        [$release] = $this->releasesWithStock(1);
        $this->client->submit($this->client->request('GET', $this->articleUri($release))->filter('form[name="add_to_cart_with_quantity"]')->form());

        $this->client->submit($this->button('/cart', \sprintf('/cart/add_quantity/%d', $release->getId())));

        $this->assertFriendlyMessage('Il n\'y en a pas plus en stock.');
    }

    public function testRemovingFromTheCartTwice(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->client->submit($this->client->request('GET', $this->articleUri($release))->filter('form[name="add_to_cart_with_quantity"]')->form());
        $remove = $this->button('/cart', \sprintf('/cart/remove/%d', $release->getId()));

        $this->client->submit($remove);
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertStringContainsString('Article retiré du panier.', $this->toasts());

        $this->client->submit($remove);
        $this->assertFriendlyMessage('Cet article n\'est plus dans ton panier.');
    }

    private function button(string $page, string $action): Form
    {
        return $this->client->request('GET', $page)->filter(\sprintf('form[action="%s"]', $action))->form();
    }

    private function articleUri(Article $article): string
    {
        return \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug());
    }

    private function unpublish(Article $article): void
    {
        $this->entityManager()->getConnection()->executeStatement('UPDATE article SET published = false WHERE id = ?', [$article->getId()]);
    }

    private function assertFriendlyMessage(string $message): void
    {
        self::assertResponseRedirects();
        $this->client->followRedirect();

        $toasts = $this->toasts();
        self::assertStringContainsString($message, $toasts);
        self::assertStringNotContainsString('Exception', $toasts);
        self::assertCount(0, $this->client->getCrawler()->filter('.toast-error'), 'a toast type the stylesheet does not know');
    }

    private function toasts(): string
    {
        return implode("\n", $this->client->getCrawler()->filter('.toast-body')->each(static fn ($toast): string => $toast->text()));
    }
}
