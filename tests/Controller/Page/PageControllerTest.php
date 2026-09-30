<?php

declare(strict_types=1);

namespace App\Tests\Controller\Page;

use App\Entity\Page;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PageControllerTest extends WebTestCase
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

    public function testPublishedPageIsPublic(): void
    {
        $this->client->request('GET', '/page/cgv');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Conditions générales de vente');
        self::assertSelectorNotExists('.alert-warning');
    }

    public function testUnpublishedPageIsNotFoundForVisitors(): void
    {
        $this->client->request('GET', '/page/page-en-preparation');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', '/page/page-en-preparation');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminsPreviewUnpublishedPages(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $this->client->request('GET', '/page/page-en-preparation');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-warning', 'Aperçu');
        self::assertFalse($this->client->getResponse()->isCacheable());
    }

    /**
     * Content comes from the back office rich-text editor and is shown to every visitor.
     */
    public function testContentIsSanitized(): void
    {
        $page = $this->entityManager()->getRepository(Page::class)->findOneBy(['slug' => 'cgv']);
        self::assertInstanceOf(Page::class, $page);
        $page->setContent('<p>Texte <strong>gras</strong></p><script>alert(1)</script><a href="javascript:alert(2)">lien</a><img src="x" onerror="alert(3)">');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/page/cgv');
        $html = $crawler->filter('.page-content')->html();

        self::assertStringContainsString('<strong>gras</strong>', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('onerror', $html);
    }

    public function testFooterLinksPublishedPagesWhereTheyArePlaced(): void
    {
        $crawler = $this->client->request('GET', '/');

        $bottom = $crawler->filter('[data-test="footer-bottom-pages"] a')->each(fn ($link) => $link->text());
        self::assertSame(['Conditions générales de vente', 'Mentions légales'], $bottom, 'in footer order');

        $footer = $crawler->filter('footer')->text();
        self::assertStringContainsString('Qui sommes-nous', $footer);
        self::assertStringNotContainsString('Page en préparation', $footer, 'unpublished pages are not linked');
    }
}
