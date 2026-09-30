<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\Page;
use App\Entity\Release;
use App\Enum\SupportType;
use App\Tests\Order\OrderTestTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Every GET page answers without a server error, for a visitor and for an admin, and so
 * does every one-click button (a POST form with only a token, see _post_button.html.twig)
 * of the shop pages. Each route parameter must be resolvable from the fixtures below: a
 * new route with a new parameter fails here until it is taught how to reach it, or
 * excluded with a reason.
 *
 * Every GET runs in its own savepoint, rolled back after it: one SQL error cannot abort
 * the shared transaction and fail every page after it.
 */
class EveryPageRendersTest extends WebTestCase
{
    use OrderTestTrait;

    /** Routes not requested, with the reason. */
    private const array EXCLUDED = [
        'app_logout' => 'logs the admin out halfway through (covered by ContentSecurityPolicyTest)',
        'app_reset_password' => 'needs a reset token (covered by the reset password flow)',
        'admin_expense_invoice' => 'serves a stored file, 404 without one (covered by AdminFundsTest)',
    ];

    /** Route name suffixes not requested, with the reason. */
    private const array EXCLUDED_SUFFIXES = [
        '_autocomplete' => "EasyAdmin's JS endpoint: without the context it sends, EasyAdmin itself throws",
    ];

    /** Flash shown when an action refused its CSRF token (ActionCsrfToken). */
    private const string EXPIRED = 'La page a expiré';

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

    public function testNoGetPageFailsForAVisitor(): void
    {
        self::assertSame([], $this->serverErrors(), 'as a visitor');
    }

    public function testNoGetPageFailsForAnAdmin(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));

        self::assertSame([], $this->serverErrors(), 'as an admin');
    }

    /**
     * Each button runs with the article in the cart, then its redirect is followed: a 500
     * there (#110) saved the change and still showed an error page. Buttons revealed by an
     * earlier one ("Retirer de la wishlist" after "Ajouter") are submitted too.
     */
    public function testNoOneClickButtonFails(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $router = self::getContainer()->get(RouterInterface::class);
        $article = $this->articleUri($release);
        $pages = [$article, $router->generate('app_cart_index'), $router->generate('app_user_wishlist'), $router->generate('app_user_collection')];

        $errors = [];
        $done = [$router->generate('app_logout') => true];

        foreach ($pages as $page) {
            while (null !== $form = $this->nextButton($release, $page, array_keys($done))) {
                $action = (string) parse_url($form->getUri(), \PHP_URL_PATH);
                $done[$action] = true;

                $this->client->submit($form);
                $status = $this->client->getResponse()->getStatusCode();

                if ($status >= 300 && $status < 400) {
                    $this->client->followRedirect();
                }

                $final = $this->client->getResponse();

                if ($status >= 500 || $final->getStatusCode() >= 500) {
                    $errors[] = \sprintf('%s from %s: HTTP %d, then %d', $action, $page, $status, $final->getStatusCode());
                } elseif (str_contains((string) $final->getContent(), self::EXPIRED)) {
                    $errors[] = \sprintf('%s from %s: token refused', $action, $page);
                }
            }
        }

        // cart +, −, remove, empty; wishlist and collection add then remove.
        self::assertGreaterThanOrEqual(8, \count($done) - 1, 'buttons submitted: '.implode(', ', array_keys($done)));
        self::assertSame([], $errors);
    }

    /**
     * The first one-click button of the page not submitted yet, rendered with the article
     * back in the cart so that each button acts on something.
     *
     * @param list<string> $done paths of the actions already submitted, or never to submit
     */
    private function nextButton(Article $release, string $page, array $done): ?Form
    {
        $this->client->submit($this->client->request('GET', $this->articleUri($release))->filter('form[name="add_to_cart_with_quantity"]')->form());
        self::assertResponseRedirects();

        $crawler = $this->client->request('GET', $page);

        foreach ($crawler->filter('form[method="post"]')->each(static fn ($form) => $form) as $node) {
            $action = (string) $node->attr('action');
            $onlyToken = 0 === $node->filter('input:not([type="hidden"]), select, textarea')->count();

            if ('' !== $action && $onlyToken && !\in_array($action, $done, true)) {
                return $node->form();
            }
        }

        return null;
    }

    /**
     * @return list<string> "route (uri): problem" for every page that failed
     */
    private function serverErrors(): array
    {
        $errors = [];
        $connection = $this->entityManager()->getConnection();

        foreach ($this->getRoutes() as $name => $route) {
            try {
                $uri = $this->uriOf($name, $route);
            } catch (\LogicException|RoutingException $exception) {
                $errors[] = \sprintf('%s: %s', $name, $exception->getMessage());

                continue;
            }

            $connection->createSavepoint('smoke');

            try {
                $this->client->request('GET', $uri);
            } finally {
                $connection->rollbackSavepoint('smoke');
            }

            $status = $this->client->getResponse()->getStatusCode();

            if ($status >= 500) {
                $errors[] = \sprintf('%s (%s): HTTP %d', $name, $uri, $status);
            }
        }

        return $errors;
    }

    /**
     * @return iterable<string, Route>
     */
    private function getRoutes(): iterable
    {
        foreach (self::getContainer()->get(RouterInterface::class)->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods();

            if (str_starts_with($name, '_') || isset(self::EXCLUDED[$name]) || ([] !== $methods && !\in_array('GET', $methods, true))) {
                continue;
            }

            foreach (array_keys(self::EXCLUDED_SUFFIXES) as $suffix) {
                if (str_ends_with($name, $suffix)) {
                    continue 2;
                }
            }

            yield $name => $route;
        }
    }

    private function uriOf(string $name, Route $route): string
    {
        $parameters = [];

        foreach ($route->compile()->getPathVariables() as $variable) {
            $parameters[$variable] = $this->valueOf($name, $variable, $route);
        }

        return self::getContainer()->get(RouterInterface::class)->generate($name, $parameters);
    }

    private function valueOf(string $routeName, string $variable, Route $route): string|int
    {
        return match (true) {
            'page' === $variable => 'page-1',
            'support' === $variable && 'app_catalog_show' === $routeName => (string) $this->release()->getSupportType()?->value,
            'support' === $variable => SupportType::LP->value,
            'slug' === $variable && 'app_catalog_show' === $routeName => (string) $this->release()->getSlug(),
            'slug' === $variable && 'app_page_show' === $routeName => (string) ($this->entityManager()->getRepository(Page::class)->findOneBy(['published' => true])?->getSlug()
                ?? throw new \LogicException('no published Page in the fixtures')),
            'orderReference' === $variable => (string) ($this->entityManager()->getRepository(Order::class)->findOneBy([])?->getReference()
                ?? throw new \LogicException('no Order in the fixtures')),
            'entityId' === $variable => $this->firstIdOf($route),
            default => throw new \LogicException(\sprintf('no value for {%s}: add one to %s::valueOf() or exclude the route', $variable, self::class)),
        };
    }

    private function release(): Release
    {
        return $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => true])
            ?? throw new \LogicException('no published Release in the fixtures');
    }

    private function articleUri(Article $article): string
    {
        return self::getContainer()->get(RouterInterface::class)->generate('app_catalog_show', [
            'support' => $article->getSupportType()?->value,
            'slug' => $article->getSlug(),
        ]);
    }

    /**
     * EasyAdmin routes: the first row of the CRUD controller's entity. An empty table
     * fails: its detail and edit pages would otherwise answer 404 without being rendered.
     */
    private function firstIdOf(Route $route): int
    {
        $crudController = (string) $route->getDefault('crudControllerFqcn');

        if (!is_a($crudController, CrudControllerInterface::class, true)) {
            throw new \LogicException(\sprintf('{entityId} on "%s", which is not a CRUD controller', $crudController));
        }

        $entityClass = $crudController::getEntityFqcn();
        $entity = $this->entityManager()->getRepository($entityClass)->findOneBy([])
            ?? throw new \LogicException(\sprintf('no %s in the fixtures: add one, its admin pages cannot be rendered', $entityClass));

        return (int) $this->entityManager()->getUnitOfWork()->getSingleIdentifierValue($entity);
    }
}
