<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use App\Cache\EntityCacheInvalidator;
use App\Entity\Image;
use App\Entity\MediaObject;
use App\Entity\Order;
use App\Entity\Page;
use App\Entity\Release;
use App\Entity\Style;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class EntityCacheInvalidatorTest extends KernelTestCase
{
    use OrderTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testFlushInvalidatesTheTagOfTheChangedClassOnly(): void
    {
        $this->warm(['page_fragment' => 'page', 'style_fragment' => 'style']);

        $page = $this->entityManager()->getRepository(Page::class)->findOneBy([]);
        self::assertInstanceOf(Page::class, $page);
        $page->setTitle('Nouveau titre');
        $this->entityManager()->flush();

        self::assertFalse($this->isCached('page_fragment'));
        self::assertTrue($this->isCached('style_fragment'));
    }

    public function testSubclassInvalidatesItsParentClassTag(): void
    {
        $this->warm(['article_fragment' => 'article']);

        $release = $this->entityManager()->getRepository(Release::class)->findOneBy([]);
        self::assertInstanceOf(Release::class, $release);
        $release->setName('Nouveau nom');
        $this->entityManager()->flush();

        self::assertFalse($this->isCached('article_fragment'));
    }

    public function testCollectionChangeInvalidatesItsOwner(): void
    {
        $release = $this->entityManager()->getRepository(Release::class)->findOneBy([]);
        $album = $release?->getAlbum();
        self::assertNotNull($album);
        $style = $this->entityManager()->getRepository(Style::class)->findOneBy([]);
        self::assertInstanceOf(Style::class, $style);

        // Only the collection changes: the album row itself is not updated.
        $album->getStyles()->contains($style) ? $album->removeStyle($style) : $album->addStyle($style);
        $this->warm(['album_fragment' => 'album']);
        $this->entityManager()->flush();

        self::assertFalse($this->isCached('album_fragment'));
    }

    public function testReplacingAPictureInvalidatesTheEntitiesShowingIt(): void
    {
        $image = (new Image())->setImageName('pochette.jpg');
        $icon = (new MediaObject())->setFilename('icone.png');
        $this->entityManager()->persist($image);
        $this->entityManager()->persist($icon);
        $this->entityManager()->flush();

        // Only the Image row changes, as when an admin uploads a new cover.
        $this->warm(['card_fragment' => 'article', 'album_fragment' => 'album', 'footer_fragment' => 'social_network']);
        $image->setImageName('nouvelle-pochette.jpg');
        $this->entityManager()->flush();

        self::assertFalse($this->isCached('card_fragment'), 'Article::$images');
        self::assertFalse($this->isCached('album_fragment'), 'Album::$images');
        self::assertTrue($this->isCached('footer_fragment'));

        $icon->setFilename('nouvelle-icone.png');
        $this->entityManager()->flush();

        self::assertFalse($this->isCached('footer_fragment'), 'SocialNetwork::$file');
    }

    public function testTagsNoFragmentUsesAreNotWritten(): void
    {
        $this->warm(['order_fragment' => 'order']);

        $order = $this->entityManager()->getRepository(Order::class)->findOneBy([]);
        self::assertInstanceOf(Order::class, $order);
        $order->setReference('TEST-REF');
        $this->entityManager()->flush();

        self::assertTrue($this->isCached('order_fragment'));
    }

    public function testFlushInsideATransactionInvalidatesAgainOnTerminate(): void
    {
        // setUp() opened a transaction: the commit comes after the flush.
        $page = $this->entityManager()->getRepository(Page::class)->findOneBy([]);
        self::assertInstanceOf(Page::class, $page);
        $page->setTitle('Nouveau titre');
        $this->entityManager()->flush();

        // A concurrent request caches the old page before the commit.
        $this->warm(['page_fragment' => 'page']);

        self::service(EntityCacheInvalidator::class)->onTerminate();

        self::assertFalse($this->isCached('page_fragment'));
    }

    /**
     * @param array<string, string> $tagsByKey
     */
    private function warm(array $tagsByKey): void
    {
        foreach ($tagsByKey as $key => $tag) {
            $this->cache()->save($this->cache()->getItem($key)->set('html')->tag($tag));
            self::assertTrue($this->isCached($key));
        }
    }

    private function isCached(string $key): bool
    {
        return $this->cache()->hasItem($key);
    }

    private function cache(): TagAwareAdapterInterface
    {
        return self::service(TagAwareAdapterInterface::class, 'cache.fragments');
    }
}
