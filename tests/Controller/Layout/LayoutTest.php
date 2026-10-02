<?php

declare(strict_types=1);

namespace App\Tests\Controller\Layout;

use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\ErrorRenderer\TwigErrorRenderer;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * The page shell of the design system: language, skip link, landmarks, active section, errors.
 */
class LayoutTest extends WebTestCase
{
    use ServiceTrait;

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testEveryPageStartsWithASkipLinkToTheMainContent(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame('fr', $crawler->filter('html')->attr('lang'));
        self::assertSame('#contenu', $crawler->filter('body a')->first()->attr('href'));
        self::assertCount(1, $crawler->filter('main#contenu'));
        self::assertCount(1, $crawler->filter('header nav[aria-label="Navigation principale"]'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sections(): iterable
    {
        yield 'productions' => ['/production', 'Nos productions'];
        yield 'catalogue' => ['/catalog', 'Catalogue'];
    }

    #[DataProvider('sections')]
    public function testTheNavbarMarksTheCurrentSection(string $uri, string $link): void
    {
        $crawler = $this->client->request('GET', $uri);

        self::assertResponseIsSuccessful();
        $active = $crawler->filter('header .nav-link.active');
        self::assertCount(1, $active);
        self::assertSame($link, trim($active->text()));
    }

    public function testTheHomePageMarksNoSection(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertCount(0, $crawler->filter('header .nav-link.active'));
    }

    /**
     * Error pages render without sub-request nor query: they still work when the database is down.
     *
     * @return iterable<string, array{\Throwable, string}>
     */
    public static function errors(): iterable
    {
        yield '404' => [new NotFoundHttpException(), 'Affiche arrachée.'];
        yield '403' => [new AccessDeniedHttpException(), 'Accès refusé.'];
        yield '500' => [new \RuntimeException(), 'La photocopieuse a calé.'];
    }

    #[DataProvider('errors')]
    public function testErrorPagesFollowTheDesignSystem(\Throwable $exception, string $title): void
    {
        $renderer = new TwigErrorRenderer(self::service(Environment::class), null, false);

        $html = $renderer->render($exception)->getAsString();

        self::assertStringContainsString('<html lang="fr">', $html);
        self::assertStringContainsString($title, $html);
        self::assertStringContainsString('kb-error', $html);
    }
}
