<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\Order;
use App\Entity\Page;
use App\Entity\Release;
use App\Tests\Order\OrderTestTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Every GET page answers without a server error, for a visitor and for an admin, and so
 * does every one-click button (a POST form with only a token, see _post_button.html.twig)
 * of the shop pages. Each route parameter must be resolvable from the fixtures below: a
 * new route with a new parameter fails here until it is taught how to reach it, or
 * excluded with a reason.
 */
class EveryPageRendersTest extends WebTestCase
{
    use OrderTestTrait;

    /** Routes not requested, with the reason. */
    private const array EXCLUDED = [
        'app_logout' => 'logs the admin out halfway through (covered by ContentSecurityPolicyTest)',
        'app_reset_password' => 'needs a reset token (covered by the reset password flow)',
    ];

    /** Route name suffixes not requested, with the reason. */
    private const array EXCLUDED_SUFFIXES = [
        '_autocomplete' => "EasyAdmin's JS endpoint: without the context it sends, EasyAdmin itself throws",
    ];

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
     * The action runs, then redirects back: a 500 there (#110) saved the change and still
     * showed an error page.
     */
    public function testNoOneClickButtonFails(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        [$release] = $this->releasesWithStock(5);
        $article = \sprintf('/catalog/%s/%s', $release->getSupportType()?->value, $release->getSlug());
        $this->client->submit($this->client->request('GET', $article)->filter('form[name="add_to_cart_with_quantity"]')->form());

        $errors = [];
        $submitted = 0;

        foreach ([$article, '/cart', '/mon-compte/wishlist', '/mon-compte/collection', '/mon-compte/mes-adresses'] as $page) {
            $crawler = $this->client->request('GET', $page);

            foreach ($crawler->filter('form[method="post"]')->each(static fn ($form) => $form) as $form) {
                $action = (string) $form->attr('action');
                $onlyToken = 0 === $form->filter('input:not([type="hidden"]), select, textarea')->count();

                if ('' === $action || '/logout' === $action || !$onlyToken) {
                    continue;
                }

                $this->client->request('GET', $page);
                $this->client->submit($form->form());
                ++$submitted;
                $status = $this->client->getResponse()->getStatusCode();

                if ($status >= 500) {
                    $errors[] = \sprintf('%s from %s: HTTP %d', $action, $page, $status);
                }
            }
        }

        self::assertGreaterThan(5, $submitted, 'the pages show one-click buttons');
        self::assertSame([], $errors);
    }

    /**
     * @return list<string> "route (uri): problem" for every page that failed
     */
    private function serverErrors(): array
    {
        $errors = [];

        foreach ($this->getRoutes() as $name => $route) {
            try {
                $uri = $this->uriOf($name, $route);
            } catch (\LogicException $exception) {
                $errors[] = \sprintf('%s: %s', $name, $exception->getMessage());

                continue;
            }

            $this->client->request('GET', $uri);
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
            'support' === $variable => 'lp',
            'slug' === $variable && 'app_catalog_show' === $routeName => (string) $this->release()->getSlug(),
            'slug' === $variable && 'app_page_show' === $routeName => (string) $this->entityManager()->getRepository(Page::class)->findOneBy(['published' => true])?->getSlug(),
            'orderReference' === $variable => (string) $this->entityManager()->getRepository(Order::class)->findOneBy([])?->getReference(),
            'entityId' === $variable => $this->firstIdOf($route),
            default => throw new \LogicException(\sprintf('no value for {%s}: add one to %s::valueOf() or exclude the route', $variable, self::class)),
        };
    }

    private function release(): Release
    {
        $release = $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => true]);
        self::assertInstanceOf(Release::class, $release);

        return $release;
    }

    /**
     * EasyAdmin routes: the first row of the CRUD controller's entity, or a missing id.
     */
    private function firstIdOf(Route $route): int
    {
        $crudController = explode('::', (string) $route->getDefault('_controller'))[0];

        if (!is_a($crudController, CrudControllerInterface::class, true)) {
            throw new \LogicException(\sprintf('{entityId} on %s, which is not a CRUD controller', $crudController));
        }

        $entity = $this->entityManager()->getRepository($crudController::getEntityFqcn())->findOneBy([]);

        return null === $entity ? 0 : (int) $this->entityManager()->getUnitOfWork()->getSingleIdentifierValue($entity);
    }
}
