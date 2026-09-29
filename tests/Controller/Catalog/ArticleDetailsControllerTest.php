<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Release;
use App\Enum\SupportType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ArticleDetailsControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    /**
     * The article page is public. A non-nullable #[CurrentUser] argument used to make
     * UserValueResolver throw an AccessDeniedException here, which the firewall turned
     * into a redirect to the login page for every anonymous visitor.
     */
    public function testAnonymousVisitorCanSeeAnArticle(): void
    {
        $this->client->request('GET', $this->uriOf($this->published(Release::class)));

        self::assertResponseIsSuccessful();
    }

    public function testLoggedInUserCanSeeAnArticle(): void
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([]);
        self::assertNotNull($user, 'the fixtures should provide at least one user');

        $this->client->loginUser($user);
        $this->client->request('GET', $this->uriOf($this->published(Release::class)));

        self::assertResponseIsSuccessful();
    }

    public function testBookPageRendersWithoutAlbum(): void
    {
        $this->client->request('GET', $this->uriOf($this->published(Book::class)));

        self::assertResponseIsSuccessful();
    }

    /**
     * A release listed under several sections has a single URL: the others redirect to it.
     */
    public function testWrongSectionRedirectsToTheCanonicalUrl(): void
    {
        $release = $this->published(Release::class);
        $otherSupport = SupportType::FANZINE;

        $this->client->request('GET', \sprintf('/catalog/%s/%s', $otherSupport->value, $release->getSlug()));

        self::assertResponseStatusCodeSame(Response::HTTP_MOVED_PERMANENTLY);
        self::assertResponseRedirects($this->uriOf($release));
    }

    public function testUnpublishedArticleIsNotFound(): void
    {
        $draft = self::getContainer()->get(ArticleRepository::class)->findOneBy(['published' => false]);
        self::assertInstanceOf(Release::class, $draft, 'the fixtures should provide an unpublished release');

        $this->client->request('GET', $this->uriOf($draft));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param class-string<Article> $class
     */
    private function published(string $class): Article
    {
        $article = self::getContainer()->get('doctrine')->getRepository($class)->findOneBy(['published' => true]);
        self::assertInstanceOf($class, $article, \sprintf('the fixtures should provide a published %s', $class));

        return $article;
    }

    private function uriOf(Article $article): string
    {
        return \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug());
    }
}
