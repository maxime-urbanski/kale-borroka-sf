<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
        $this->client->request('GET', $this->firstArticleUri());

        self::assertResponseIsSuccessful();
    }

    public function testLoggedInUserCanSeeAnArticle(): void
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([]);
        self::assertNotNull($user, 'the fixtures should provide at least one user');

        $this->client->loginUser($user);
        $this->client->request('GET', $this->firstArticleUri());

        self::assertResponseIsSuccessful();
    }

    private function firstArticleUri(): string
    {
        $article = self::getContainer()->get(ArticleRepository::class)->findOneBy([]);
        self::assertNotNull($article, 'the fixtures should provide at least one article');

        return \sprintf('/catalog/%s/%s', $article->getSupport()?->getName(), $article->getSlug());
    }
}
