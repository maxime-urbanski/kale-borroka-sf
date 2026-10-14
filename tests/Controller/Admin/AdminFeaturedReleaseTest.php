<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\FeaturedRelease;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminFeaturedReleaseTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    /**
     * The admin types shop time; the database and the clock are in UTC.
     */
    public function testDisplayDatesAreTypedInShopTime(): void
    {
        $feature = $this->entityManager()->getRepository(FeaturedRelease::class)->findOneBy([]);
        self::assertNotNull($feature);

        $form = $this->client->request('GET', \sprintf('/admin/featured-release/%d/edit', $feature->getId()))
            ->filter('form[name="FeaturedRelease"]')->form();
        $values = $form->getPhpValues();
        $values['FeaturedRelease']['datePublished'] = '2026-10-14T20:00';
        $values['FeaturedRelease']['expires'] = '2026-11-14T00:00';

        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $saved = $this->entityManager()->getRepository(FeaturedRelease::class)->find($feature->getId());
        // Paris is UTC+2 in summer time, UTC+1 in winter.
        self::assertSame('2026-10-14 18:00', $saved?->getDatePublished()?->format('Y-m-d H:i'));
        self::assertSame('2026-11-13 23:00', $saved->getExpires()?->format('Y-m-d H:i'));

        $form = $this->client->request('GET', \sprintf('/admin/featured-release/%d/edit', $feature->getId()))
            ->filter('form[name="FeaturedRelease"]')->form();
        self::assertSame('2026-10-14T20:00', $form->getPhpValues()['FeaturedRelease']['datePublished'], 'shown as typed');
    }
}
