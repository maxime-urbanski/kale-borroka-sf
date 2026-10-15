<?php

declare(strict_types=1);

namespace App\Tests\Controller\Layout;

use App\Entity\FooterColumn;
use App\Entity\FooterLink;
use App\Entity\Homepage;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The strip above the navbar and the footer, on every page, as set in the back office.
 */
class FooterTest extends WebTestCase
{
    use DatabaseIsolationTrait;

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

    public function testTheFooterShowsItsColumnsInOrder(): void
    {
        $crawler = $this->client->request('GET', '/catalog');

        $columns = $crawler->filter('footer nav.kb-footer__col')->each(static fn ($column): array => [
            $column->filter('.kb-footer__heading')->text(),
            $column->filter('a')->each(static fn ($link): string => $link->text()),
        ]);
        self::assertSame([
            ['À propos', ['Qui sommes-nous']],
            ['Shop', ['LP', 'EP', 'CD', 'FANZINE', 'TAPE']],
        ], $columns, 'unpublished pages are left out');
        self::assertCount(1, $crawler->filter('footer a[href="/catalog/lp"]'));
        self::assertSelectorTextContains('footer .kb-footer__about', 'Fait main, sans patron.');
        self::assertSelectorTextContains('footer .kb-footer__copyright', 'Make racists afraid again');
        self::assertSelectorTextContains('header .kb-header__strip', 'Punk · antifa · DIY');
    }

    public function testColumnsAreMadeOnTheFly(): void
    {
        $column = (new FooterColumn())->setName('Les copains')->setPosition(0)
            ->addLink((new FooterLink())->setName('Bandcamp')->setUrl('https://kaleborroka.bandcamp.com')->setPosition(2))
            ->addLink((new FooterLink())->setName('Les arrivages')->setUrl('/catalog')->setPosition(1));
        $this->entityManager()->persist($column);
        // A column with nothing to follow is not shown.
        $this->entityManager()->persist((new FooterColumn())->setName('Vide')->setPosition(9));
        $this->entityManager()->flush();
        // Loaded again in link order, as on a real request.
        $this->entityManager()->clear();

        $crawler = $this->client->request('GET', '/');

        $first = $crawler->filter('footer nav.kb-footer__col')->first();
        self::assertSame('Les copains', $first->filter('.kb-footer__heading')->text());
        self::assertSame(['/catalog', 'https://kaleborroka.bandcamp.com'], $first->filter('a')->each(static fn ($link): ?string => $link->attr('href')));
        self::assertStringNotContainsString('Vide', $crawler->filter('footer')->text());
    }

    public function testTheStripAndTheFooterTextComeFromTheBackOffice(): void
    {
        $homepage = $this->entityManager()->getRepository(Homepage::class)->findOneBy([]) ?? throw new \LogicException('no homepage in the fixtures');
        $homepage->setStripLeft('Distro du Pays basque')->setStripRight(null)->setFooterText("Fait main.\nSans patron.")->setFooterTagline('Gora herria');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/production');

        self::assertSelectorTextContains('header .kb-header__strip', 'Distro du Pays basque');
        self::assertStringContainsString('Fait main.<br>', $crawler->filter('footer .kb-footer__about')->html());
        self::assertSame('© '.date('Y').' Kale Borroka Riot Records · Gora herria', $crawler->filter('footer .kb-footer__copyright')->text());

        $homepage->setStripShown(false)->setFooterText(' ')->setFooterTagline(' ');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/production');

        self::assertCount(0, $crawler->filter('.kb-header__strip'));
        self::assertCount(0, $crawler->filter('.kb-footer__about'));
        self::assertSame('© '.date('Y').' Kale Borroka Riot Records', $crawler->filter('footer .kb-footer__copyright')->text());
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::service(EntityManagerInterface::class);
    }
}
