<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\Article;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Service\StockManagerInterface;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

/**
 * Wantlist alerts (maquette 11): a switch per record on watch, on when the record is added;
 * one e-mail to each subscriber when the record can be bought again (back in stock, or put
 * online with stock), whether from the back office or a cancelled order.
 */
class WantlistAlertTest extends WebTestCase
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

    public function testTheSwitchTurnsTheAlertOnAndOff(): void
    {
        [$item] = $this->wantlist();
        $item->setAlertEnabled(false);
        $this->soldOut($item);
        self::assertSame('false', $this->switchOf($this->wantlistPage(), $item)->attr('aria-checked'));

        $this->client->submit($this->switchOf($this->wantlistPage(), $item)->ancestors()->filter('form')->first()->form());

        self::assertResponseRedirects('/mon-compte/wishlist', Response::HTTP_SEE_OTHER);
        $crawler = $this->client->followRedirect();
        self::assertSame('true', $this->switchOf($crawler, $item)->attr('aria-checked'));
        self::assertSelectorTextContains('.toast', 'Alerte activée');
        self::assertSelectorTextContains('.kb-account__since', '1 alerte active');

        $this->client->submit($this->switchOf($crawler, $item)->ancestors()->filter('form')->first()->form());
        self::assertSame('false', $this->switchOf($this->client->followRedirect(), $item)->attr('aria-checked'));
    }

    public function testTheSwitchSaysWhatItIsFor(): void
    {
        [$item] = $this->wantlist();
        $this->soldOut($item);

        $switch = $this->switchOf($this->wantlistPage(), $item);

        self::assertSame('switch', $switch->attr('role'));
        self::assertStringStartsWith('Alerte e-mail pour ', (string) $switch->attr('aria-label'));
    }

    /**
     * A record on sale has no alert: its card offers the cart only.
     */
    public function testARecordOnSaleHasNoSwitchButTheCart(): void
    {
        [$item] = $this->wantlist();
        $item->getArticle()?->setStock(3)->setPublished(true);
        $item->setAlertEnabled(true);
        $this->entityManager()->flush();

        $crawler = $this->wantlistPage();

        self::assertCount(0, $crawler->filter('.kb-want [role="switch"]'));
        self::assertCount(1, $crawler->filter(\sprintf('.kb-want form[action="/cart/add/%d"] button', $item->getArticle()?->getId())));
    }

    /**
     * A sold-out record has the switch where the cart would be, and no cart button.
     */
    public function testASoldOutRecordHasTheSwitchInPlaceOfTheCart(): void
    {
        [$item] = $this->wantlist();
        $this->soldOut($item);

        $crawler = $this->wantlistPage();

        self::assertCount(1, $crawler->filter(\sprintf('.kb-watch__item form[action="/wishlist/alert/%d"] [role="switch"]', $item->getArticle()?->getId())));
        self::assertCount(0, $crawler->filter(\sprintf('form[action="/cart/add/%d"]', $item->getArticle()?->getId())));
    }

    /**
     * The header counts the alerts that can still fire: sold-out records only.
     */
    public function testTheHeaderCountsOnlyTheSoldOutAlertsOn(): void
    {
        [$onSale, $soldOut] = $this->wantlist();
        $onSale->getArticle()?->setStock(3)->setPublished(true);
        $onSale->setAlertEnabled(true);
        $soldOut->setAlertEnabled(true);
        $this->soldOut($soldOut);

        $this->wantlistPage();

        self::assertSelectorTextContains('.kb-account__since', '1 alerte active');
    }

    public function testAForgedTokenChangesNothing(): void
    {
        [$item] = $this->wantlist();
        $item->setAlertEnabled(false);
        $this->entityManager()->flush();

        $this->client->request('POST', \sprintf('/wishlist/alert/%d', $item->getArticle()?->getId()), ['_token' => 'forged']);

        self::assertFalse($this->reloaded($item)->isAlertEnabled());
    }

    public function testOnlyTheOwnersOwnRecordCanBeSwitched(): void
    {
        $notInHisWantlist = $this->releasesWithStock(0)[0];
        $this->soldOut($this->wantlist()[0]);
        $crawler = $this->wantlistPage();
        $token = (string) $crawler->filter('form[action^="/wishlist/alert/"] input[name="_token"]')->attr('value');

        $this->client->request('POST', \sprintf('/wishlist/alert/%d', $notInHisWantlist->getId()), ['_token' => $token]);

        self::assertResponseRedirects('/mon-compte/wishlist', Response::HTTP_SEE_OTHER);
        self::assertNull(self::service(WishlistItemRepository::class)->findOneBy(['article' => $notInHisWantlist, 'wishlist' => $this->wishlist()]));
    }

    public function testARecordAddedToTheWantlistHasItsAlertOn(): void
    {
        $release = $this->releasesWithStock(0)[0];
        $crawler = $this->client->request('GET', \sprintf('/catalog/%s/%s', $release->getSupportType()?->value, $release->getSlug()));

        $this->client->submit($crawler->filter(\sprintf('form[action="/wishlist/add/%d"]', $release->getId()))->form());

        $item = self::service(WishlistItemRepository::class)->findOneBy(['article' => $release, 'wishlist' => $this->wishlist()]);
        self::assertNotNull($item);
        self::assertTrue($item->isAlertEnabled());
    }

    public function testBackInStockMailsTheSubscribers(): void
    {
        [$item] = $this->wantlist();
        $article = $this->onlySubscriber($item);
        $this->soldOut($item);

        $article->setStock(3);
        $this->entityManager()->flush();

        self::assertQueuedEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('test@test.fr', $email->getTo()[0]->toString());
        self::assertStringStartsWith('De retour : ', (string) $email->getSubject());
        self::assertEmailHtmlBodyContains($email, \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug()));
        self::assertEmailTextBodyContains($email, '/mon-compte/wishlist');
    }

    public function testNoMailWhenTheAlertIsOff(): void
    {
        [$item] = $this->wantlist();
        $article = $this->onlySubscriber($item);
        $this->reloaded($item)->setAlertEnabled(false);
        $this->soldOut($item);

        $article->setStock(3);
        $this->entityManager()->flush();

        self::assertQueuedEmailCount(0);
    }

    public function testNoMailWhenTheRecordWasAlreadyOnSale(): void
    {
        [$item] = $this->wantlist();
        $article = $this->onlySubscriber($item);
        $article->setStock(2)->setPublished(true);
        $this->entityManager()->flush();

        $article->setStock(5);
        $this->entityManager()->flush();

        self::assertQueuedEmailCount(0);
    }

    public function testPuttingARecordOnlineWithStockMailsTheSubscribers(): void
    {
        [$item] = $this->wantlist();
        $article = $this->onlySubscriber($item);
        $article->setStock(4)->setPublished(false);
        $this->entityManager()->flush();

        $article->setPublished(true);
        $this->entityManager()->flush();

        self::assertQueuedEmailCount(1);
    }

    public function testACancelledOrderPuttingStockBackMailsTheSubscribers(): void
    {
        [$item] = $this->wantlist();
        $article = $this->onlySubscriber($item);
        $this->soldOut($item);

        self::service(StockManagerInterface::class)->putBack($article, 1);

        self::assertQueuedEmailCount(1);
    }

    /**
     * Alert on for the given item, off for anyone else who wants the same record.
     */
    private function onlySubscriber(WishlistItem $item): Article
    {
        $article = $item->getArticle() ?? throw new \LogicException('no article');
        foreach (self::service(WishlistItemRepository::class)->findBy(['article' => $article]) as $other) {
            $other->setAlertEnabled($other === $item);
        }
        $this->entityManager()->flush();

        return $article;
    }

    private function soldOut(WishlistItem $item): void
    {
        $item->getArticle()?->setStock(0)->setPublished(true);
        $this->entityManager()->flush();
    }

    private function wantlistPage(): Crawler
    {
        $crawler = $this->client->request('GET', '/mon-compte/wishlist');
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    private function switchOf(Crawler $crawler, WishlistItem $item): Crawler
    {
        $switch = $crawler->filter(\sprintf('form[action="/wishlist/alert/%d"] button', $item->getArticle()?->getId()));
        self::assertCount(1, $switch);

        return $switch;
    }

    private function reloaded(WishlistItem $item): WishlistItem
    {
        $this->entityManager()->refresh($item);

        return $item;
    }

    private function wishlist(): ?Wishlist
    {
        return self::service(WishlistRepository::class)->findOneBy(['user' => $this->user('test@test.fr')]);
    }

    /**
     * @return list<WishlistItem>
     */
    private function wantlist(): array
    {
        $items = self::service(WishlistItemRepository::class)->findBy(['wishlist' => $this->wishlist()], ['article' => 'ASC']);
        self::assertCount(2, $items);

        return $items;
    }
}
