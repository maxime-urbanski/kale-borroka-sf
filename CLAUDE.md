# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Kale Borroka Records — e-commerce site (antifascist record label/distro) built on Symfony 8.1 / PHP 8.5 + Doctrine ORM 3 / PostgreSQL + Twig + Webpack Encore (Bootstrap 5 / Stimulus). Runs on the `dunglas/symfony-docker` stack: a **single `php` service** based on FrankenPHP (Caddy embedded in the PHP binary), in **worker mode** (the kernel stays in memory between requests — never keep request state in service properties), HTTPS/HTTP3 by default. UI strings and user-facing messages are in French.

## Commands

Everything PHP runs inside the `php` container — it serves HTTP *and* runs the CLI; there is no separate web server container. Prefix with `docker compose exec -T php` (or `docker compose exec php` when interactive).

```bash
docker compose build --pull              # build images
docker compose up --wait                 # start (https://localhost, HTTP on :81)
docker compose down --remove-orphans     # stop
```

Docker config lives in `Dockerfile`, `compose.yaml`, `compose.override.yaml` (dev), `compose.prod.yaml` and `frankenphp/` (`Caddyfile`, `docker-entrypoint.sh`, `conf.d/*.ini`). The container workdir is `/app`. To pull in later template changes, use [template-sync](https://github.com/coopTilleuls/template-sync) against `https://github.com/dunglas/symfony-docker`. Keep the local deviations: Postgres + Mailpit services, the Encore `assets_builder` stage, the VichUploader dirs/volumes, HTTP on port 81, and no Mercure/Vulcain.

The entrypoint runs `composer install` only when `vendor/` is empty — after editing `composer.json`, run it yourself.

In dev, `FRANKENPHP_WORKER_CONFIG: watch` restarts the worker when a source file changes, so PHP edits apply without restarting the container.

Dev database + fixtures:

```bash
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console hautelook:fixtures:load   # loads fixtures/*.yml (dev + test only)
# Reloading onto a non-empty database needs --purge-with-truncate: the default purger
# deletes rows in an order that violates the address -> user foreign key.
docker compose exec php bin/console app:create-admin [email]  # create/promote a ROLE_ADMIN user
```

Tests (WebTestCase hits a real DB — create/migrate/seed the `_test` database first, as CI does):

```bash
docker compose exec -T php bin/console -e test doctrine:database:create
docker compose exec -T php bin/console -e test doctrine:migrations:migrate --no-interaction
docker compose exec -T php bin/console -e test hautelook:fixtures:load --no-interaction
docker compose exec -T php bin/phpunit
docker compose exec -T php bin/phpunit tests/Controller/Catalog/CatalogControllerTest.php
docker compose exec -T php bin/phpunit --filter testCatalogPage
```

Static analysis / style / schema (all gate CI):

```bash
docker compose exec php vendor/bin/phpstan analyse --memory-limit=-1   # level 6, uses phpstan.dist.neon
docker compose exec php vendor/bin/php-cs-fixer fix           # @Symfony ruleset
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec -T php bin/console -e test doctrine:schema:validate
```

CI (`.github/workflows/ci.yml`) runs on pull requests and on pushes to `main` only — pushes to `dev` (the default branch) are not checked unless they go through a PR. It also lints the `Dockerfile` with hadolint.

Assets (yarn, run on the host):

```bash
yarn install && yarn dev      # dev build (CI runs this)
yarn watch                    # rebuild on change
yarn build                    # production build
```

Without a built `public/build/`, every page fails with `Asset manifest file … does not exist`. The prod image builds assets itself (`assets_builder` stage in the `Dockerfile`).

Switching between the dev and prod stacks on one machine requires dropping the `caddy_data`/`caddy_config` volumes first: the root-run dev container leaves a root-owned local CA the `www-data` prod container cannot read.

Mailpit UI: http://localhost:8025 (`MAILER_DSN=smtp://mailer:1025`, `MAILPIT_PORT` to move the UI port).

## Architecture

### Controllers: one action per class

The dominant pattern is a single-action invokable controller: `#[AsController]`, no base class, `__invoke()` with everything autowired as method arguments, `Twig\Environment` injected explicitly, returning `new Response($twig->render(...))`. Routes are attribute-based, grouped in folders by domain (`Controller/Cart`, `Controller/Catalog`, `Controller/Order`, `Controller/User/...`), named `app_<domain>_<action>`. Follow this when adding pages — do not reach for `AbstractController` unless you need its helpers.

Exceptions: `MenuController` and `Layout\FooterController` extend `AbstractController` and are *not* routed — `templates/base.html.twig` renders them as sub-requests via `render(controller(...))`. `Controller/Admin/*` are EasyAdmin CRUD controllers.

### EasyAdmin (v5)

Admin routes are generated by EasyAdmin's own route loader (`config/routes/easyadmin.yaml`), not by `#[Route]`. Consequences:

- `DashboardController` carries `#[AdminDashboard(routePath: '/admin', routeName: 'admin')]`; a plain `#[Route]` on `index()` is rejected at compile time.
- URLs and route names are derived from the *controller* name in kebab-case (`MediaObjectCrudController` → `/admin/media-object`, `admin_media_object_index`), so renaming a CRUD controller changes its admin URL. Use `#[AdminRoute(path: …, name: …)]` to decouple them.
- The menu uses `MenuItem::linkTo(SomeCrudController::class, …)` — it takes the controller, not the entity. Redirect between admin pages with `redirectToRoute('admin_<controller>_index')` rather than `AdminUrlGenerator`.
- A custom CRUD action needs `#[AdminRoute]` to be routed at all.
- Label funds (dashboard "Fonds disponibles", `App\Admin\Query\FundsBalance`) = `ShopSettings::$openingBalance` + paid shop orders + `EventSale` − `Expense`, counted from `openingBalanceDate` up to today (Paris); later-dated expenses show apart as « frais à venir ». Expense invoices use the `expense_invoice` Vich mapping in `private/invoices` (outside `public/`, own volume in prod) and are only served by `ExpenseCrudController::invoice()`.
- Fields are type-checked: a `TextField` on a non-string property throws at render time (use `IntegerField` and friends).

### Services: `readonly` class + interface

Every service in `src/Service` has a matching `…Interface` and is injected by interface (autowiring resolves the single implementation). Key ones:

- `CartService` — cart lives entirely in the session as `['articleId' => quantity]`; `getFullCart()` re-hydrates `Article` entities and clamps to available stock.
- `BreadcrumbService` — builds breadcrumbs by walking the current URI segment by segment and calling `RouterInterface::match()` on each prefix, skipping `page-*` segments. Consequence: **every intermediate URL path must resolve to a real route**, or the breadcrumb breaks.
- `CustomPaginationService` — wraps KnpPaginator. Pagination is path-based, not query-string: the `{page}` route parameter is the literal string `page-N` (default `page-1`), and the service parses the digit out of it. Default 9 items/page.
- `DispatchFilterValueService` — merges the "global" filter form values into the per-page `ArticleFilterData` DTO.
- `UserDefaultAddressService`, `RefererService`.

### Filtering flow

`src/Data/*` holds plain mutable DTOs (`ArticleFilterData`, `AddToCartWithQuantity`, `OrderDeliveryDto`, …). A catalog page instantiates the DTO, seeds it with route context (e.g. the current `Support`), binds it to a `src/Form/*FilterFormType`, passes it through `DispatchFilterValueService`, then hands it to `ArticleRepository::filterArticleQuery()` which conditionally appends `andWhere` clauses and returns a `Query` for the paginator. New filters mean touching all four layers: DTO field → form type → dispatch service → repository query.

### Doctrine / entities

- Repositories are `ServiceEntityRepository` with hand-added `save(entity, flush)` / `remove(entity, flush)` helpers. Typing comes from the `@extends ServiceEntityRepository<Entity>` docblock alone — `ServiceEntityRepository` is generic in doctrine-bundle 3, so do *not* re-add `@method find()/findBy()/...` docblocks: PHPStan 2 rejects their untyped `array` parameters.
- `Collection` properties on entities need a `@var Collection<int, Target>` docblock (PHPStan level 6), and EasyAdmin CRUD controllers need `@extends AbstractCrudController<Entity>`.
- `QueryBuilder::setParameters()` no longer accepts an array in ORM 3 — chain `setParameter()` calls instead.
- Slugs come from `Gedmo\Slug` (StofDoctrineExtensions); most user-facing routes look up entities by `slug` or by name via `#[MapEntity(mapping: [...])]` rather than by id.
- Join entities (`WishlistItem`, `UserCollectionItems`) use **composite primary keys** made of two `#[ORM\Id] #[ORM\ManyToOne]` associations — no surrogate id, so `find()` needs an array key.
- Uploads go through VichUploaderBundle (`albums`, `media_object` and `expense_invoice` mappings, `SmartUniqueNamer`). Stored file names are bare names: get a public URL with `vich_uploader_asset()` (or `/media/<filename>`). `AlbumEventSubscriber` (`prePersist`) assigns `kbrProductionId` (`KBR#001`, `KBR#002`, …) to new KBR-produced albums, counted from existing productions — only during a POST/PATCH/PUT request, so fixtures and CLI inserts do not get one.
- Orders are snapshots taken by `PlaceOrderHandler`: lines copy name/SKU/price, `shippingAddress` copies the address as text (`Order::$address` is only a link, set to null when the entry is deleted), `shippingPrice` copies the transporter's price (0 from `ShopSettings::$freeShippingThreshold`) and `totalPrice` = lines + shipping. Display the snapshot, never the live address.
- Migrations in `migrations/` are the source of truth for schema; `doctrine:schema:validate` gates CI and currently passes (mapping *and* database), so keep it that way when touching associations.

### Security

Session auth via the custom `App\Security\UserAuthenticator` (login form with a CSRF badge), `app_user_provider` on `User::email`, remember-me always on (7 days, signed with the password hash, so a password change logs out every other session). Password reset uses `symfonycasts/reset-password-bundle` (`Controller/Security/ResetPasswordController`). Registration forces `ROLE_USER`; `ROLE_ADMIN` is only granted from the back office or `app:create-admin`.

Access control in `security.yaml`: `^/admin` → `ROLE_ADMIN`, `^/mon-compte` → `ROLE_USER`. On top of that, account pages declare `#[IsGranted('IS_AUTHENTICATED_FULLY')]` (a remember-me session alone does not reach them) and a non-nullable `#[CurrentUser] User $user` (`UserValueResolver` throws `AccessDeniedException`, which the firewall turns into a redirect to the login form). Every `/mon-compte/*`, `/order/*`, `/wishlist/*` and `/collection/*` route redirects anonymous visitors to `/login`. The flip side is that a **public** page must declare `#[CurrentUser] ?User $user = null`, otherwise it redirects anonymous visitors too.

Being logged in is not enough for an object loaded from the URL: check its owner with a voter in `src/Security/Voter` — `OrderVoter` (`ORDER_VIEW`, buyer or admin), `AddressVoter` (`ADDRESS_EDIT`, owner only) — through `#[IsGranted(Voter::X, subject: 'argument')]`. Queries for lists are scoped to the current user (`findBy(['buyer' => $user])`, `SelectUserAddressFormType` offers the user's own addresses only). `tests/Controller/User/AccountSecurityTest.php` covers the account pages.

- Forms: always `isSubmitted() && isValid()` — `isValid()` is what enforces the form's CSRF token and constraints. Constraints on a DTO (`src/Data`) do not inherit the entity's `UniqueEntity`: check uniqueness yourself (`PatchUserInformationsController::emailIsFree()`). Sensitive changes re-check the current password (`UserPassword` on `UpdatePasswordFormType`).
- `fetch()` calls from Stimulus (e.g. `address_controller.js`) send a per-object CSRF token in the `X-CSRF-Token` header, rendered by the template with `csrf_token('address-' ~ id)`. Check it in the controller with `CsrfTokenManagerInterface::isTokenValid()` and redirect with a flash: a failing `#[IsCsrfTokenValid]` throws an authentication exception, which the firewall turns into a redirect to `/login`, not a 403.
- Back-office actions that change state are POST with a per-entity CSRF token (order transitions, `generateSizes`, `duplicate`).
- Shop actions that change state (cart, wishlist, collection) are POST-only with a CSRF token: render them with `partials/_post_button.html.twig` (`token_id`), check with `App\Security\ActionCsrfToken::isValid()` and redirect back when it fails. `_obfuscation_link.html.twig` (a JS `window.location`) is for navigation only. The article page's add-to-cart form posts to the article route itself and redirects (303) even when invalid: rendering during a POST breaks `BreadcrumbService`, whose `router->match()` on the GET-only parent routes fails.
- E-mails are stored lowercased (`User::setEmail()` normalises); look users up with `UserRepository::findOneByEmail()`, which also backs the user provider (`loadUserByIdentifier()`).
- Twig: no `|raw` on user data. `Address::__toString()` returns plain text; show it with `|nl2br`, which escapes first. CMS pages go through `sanitize_html('app.rich_text_sanitizer')`.
- Private files (expense invoices) live outside `public/` and are only served by an admin route; uploads get their extension from the sniffed MIME type (`SmartUniqueNamer`), and Caddy only runs `index.php`.
- Uploads: no SVG (it runs scripts on our origin; Caddy sandboxes any old one), `SocialNetwork::$url` is http(s) only. Caddy sends nosniff, `X-Frame-Options: SAMEORIGIN`, referrer and permissions policies (HSTS in prod).
- HTML pages get a Content-Security-Policy from `App\Security\ContentSecurityPolicy`: scripts from our origin only, no inline script or `on…=""` handler — put behaviour in a Stimulus controller, or give an unavoidable inline `<script>` the page nonce (`nonce="{{ csp_nonce('script') }}"`, what EasyAdmin does). Styles stay `'unsafe-inline'` (style attributes). Nothing external (CDN, fonts, analytics) loads until the policy allows it.
- Logout needs a CSRF token (`enable_csrf`, token id `logout`, field `_token`): render it with `_post_button.html.twig`. The route also accepts GET for EasyAdmin's "Sign out" link, which carries the token in the query string.
- Secrets: `.env` leaves `APP_SECRET` empty, dev takes it from `.env.dev`, and `compose.prod.yaml` refuses to start without `APP_SECRET` and `POSTGRES_PASSWORD`.

Validation constraints must be built with **named arguments** — Symfony 8 rejects `new NotBlank(['message' => …])` at runtime, and PHPStan does not catch it because the constructors still accept an array.

### Cache

- **HTML pages are not HTTP-cached**: the navbar reads the session (cart, login state), so Symfony marks every page `private`. Only static files get long-lived headers, from Caddy in prod (`CADDY_SERVER_EXTRA_DIRECTIVES` in `compose.prod.yaml`): `/build/*` immutable (Encore versions file names in prod only, hence not in dev), uploads a week.
- **Fragments** that are the same for every visitor are cached with Twig's `{% cache 'key' tags([...]) %}` (`twig/cache-extra`) in the `cache.fragments` pool (filesystem in `var/share/prod/pools`, shared with the CLI; array adapter in dev/test, i.e. no caching across requests). Cached today: the footer (key includes the year), the home sections, and an article page's related sections. The controllers pass the unexecuted `Query` and the template calls `.result` inside the block, so a hit runs no SQL.
- **Invalidation** is automatic: on every flush, `App\Cache\EntityCacheInvalidator` invalidates, for each written entity, the tag of its class and parent classes (`EntityCacheTag`: `SocialNetwork` → `social_network`, `Release` → `release` + `article`) **and** the tags of the classes holding an association to it (one level: a new `Image` clears `article`/`album`, a new `MediaObject` clears `social_network`); a collection change tags its owner. So `tags()` lists the entities a fragment reads directly, not their children. Only tags in `EntityCacheTag::FRAGMENT_TAGS` are written; `CachedTemplatesTest` fails if a template uses one missing from it. A flush inside an outer transaction (command bus) is invalidated again on `kernel.terminate`/`console.terminate`, after the commit. DBAL/DQL writes bypass it (e.g. `StockManager`), so never put stock — or anything user-specific, a CSRF token, a form or an absolute `url()` — inside a `{% cache %}` block (`CachedTemplatesTest` checks the cached partials).
- Doctrine metadata/query/result caches are the recipe's prod defaults (`doctrine.yaml`).

### Frontend

Single Encore entry `assets/app.js`; SCSS in `assets/styles` (`app.scss` + partials, Bootstrap 5). Stimulus controllers in `assets/controllers/` registered through `assets/controllers.json` — used for cart quantity, address selection, filter accordion, obfuscated links, scrollbar.

## Conventions

- New entities follow Schema.org naming (e.g. `Expense` ≈ `Invoice`: `provider`, `totalPaymentDue`, `paymentDueDate`; `EventSale` ≈ `SellAction`: `startTime`, `price`, `location`), documented in the class docblock.
- `declare(strict_types=1);` in new PHP files (most of `src/` has it; a few older entities/enums do not).
- Entity setters return `static` for chaining.
- Fixtures are Alice YAML in `fixtures/`. Generated albums, releases and songs are linked by index (`album_N` → `artist_((N - 1) % 25 + 1)`, `release_N`, `song_((N - 1) * 10 + 1..N * 10)`) so artists, labels and tracklists stay consistent; `album_1..10` are KBR productions. Alice cannot chain a dynamic reference with a property (`@album_<current()>->name` fails to parse): read a sibling property with `<($album->…)>` instead. `order.yml` holds ~13 months of orders bought by `customer.yml`'s `user_customer_*` (never by `user_admin`/`user_user`: tests expect their order history empty), with nothing paid today and at most 7 orders to process — the dashboard tests rely on both. Order lines copy the article's SKU, which is why fixture articles set `sku` explicitly. Fixture orders have no shipping cost (`shippingPrice` 0). Escape a literal `@` as `\@` (emails).
- `App\Enum\SupportType` is the single source of truth for supports: `Support::$code` is typed with it, and the `{support}` route segment gets its requirement from `new EnumRequirement(SupportType::class)` in `CatalogBySupportController` and `ArticleDetailsController`. Adding a case means adding a `Support` row too (fixtures + a migration) — `CatalogControllerTest::testEverySupportTypeHasAReachableCatalogPage` guards the pairing. `Support::$name` stays the URL segment; branch on `$code`.
