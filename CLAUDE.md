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

Production catalogue (Alice is `require-dev`, absent from the prod image): `data/catalog/stock.yaml` holds the label's real stock, loaded by `bin/console app:catalog:import [file] [--dry-run]` (`App\Catalog\CatalogImporter`). Create-only and idempotent: sections by code, labels/artists by name, styles among the official ones, albums by artist + name, releases by SKU; existing rows are never updated, so stock and prices changed in the admin survive a rerun, and editing the file afterwards changes nothing for an imported SKU. It also creates the missing `Support` rows (one per `SupportType` case). An album is found through the SKU of a pressing already imported before artist + name, so renaming it in the admin does not duplicate it; names match case-insensitively. Everything is validated before the single transaction commits; `ImportCatalogCommandTest::testTheLabelStockFileImports` dry-runs the real file, so a broken entry fails CI.

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
docker compose exec php vendor/bin/phpstan analyse --memory-limit=-1   # level 8 (nullability), uses phpstan.dist.neon + phpstan-phpunit
docker compose exec php vendor/bin/php-cs-fixer fix           # @Symfony ruleset
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec -T php bin/console -e test doctrine:schema:validate
```

CI (`.github/workflows/ci.yml`) runs on pull requests and on pushes to `dev` (the default branch) and `main`; on a push, once the tests pass, it calls `deploy.yml`. It also lints the `Dockerfile` with hadolint and validates the edge Caddyfile (`deploy/edge/validate.sh`).

Assets (yarn, run on the host):

```bash
yarn install && yarn dev      # dev build (CI runs this)
yarn watch                    # rebuild on change
yarn build                    # production build
```

Without a built `public/build/`, every page fails with `Asset manifest file … does not exist`. The prod image builds assets itself (`assets_builder` stage in the `Dockerfile`).

Switching between the dev and prod stacks on one machine requires dropping the `caddy_data`/`caddy_config` volumes first: the root-run dev container leaves a root-owned local CA the `www-data` prod container cannot read.

Deployment (`docs/deployment.md`): prod and preprod share one VPS behind an edge Caddy (`deploy/edge/`, TLS, basic auth + noindex on `preprod.`, one network per stack); each stack is `compose.yaml` + `compose.prod.yaml` + `deploy/compose.app.yaml` (GHCR image `sha-<commit>`, `SERVER_NAME=:80`, no published port, trusted proxies `PRIVATE_SUBNETS` with `X-Forwarded-For`/`-Proto` only, `SYMFONY_TRUSTED_HOSTS`). `dev` → preprod, `main` → prod, over SSH to `deploy/deploy.sh`, the forced command of one key per environment: it only moves forward on the branch, runs the migrations in a one-off container before replacing the stack, and refreshes the edge on main's head. Sessions are files in `var/sessions` (a volume in prod) so a deploy keeps carts and logins; `DEFAULT_URI` feeds `router.default_uri`. Mails are queued, not sent during the request: they are rendered first (`App\Mailer\RenderBeforeQueueMailer` decorates the mailer, so `url()` uses the request's checked host and the worker renders nothing), then the mailer dispatches `SendEmailMessage` on `mail.bus`, routed to the Doctrine `async` transport (table `messenger_messages`, migration) and sent by the `worker` compose service (`messenger:consume async`, same image as `php`; retries 1/3/9 min, then the `failed` queue). Tests use `in-memory://`: assert with `assertQueuedEmailCount()` (not `assertEmailCount()`), the transport and mailer events reset with each request. Mail leaves from `MAILER_DEFAULT_SENDER` (default `From` in `mailer.yaml`; never set `from()` in code): OVH's Zimbra relays nothing else.

`docker compose up` starts the worker too: no worker, no mail in Mailpit. It does not watch files: `docker compose restart worker` after changing mail transport code. Mailpit UI: http://localhost:8025 (`MAILER_DSN=smtp://mailer:1025`, `MAILPIT_PORT` to move the UI port).

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
- Homepage feature (« Encart de l'accueil », `FeaturedRelease` + `FeatureKind`: prévente, coup de cœur, à découvrir): several can be prepared, the homepage shows one, `FeaturedReleaseRepository::findCurrent()` (online, within `datePublished`/`expires`, release published, latest scheduled first). Rendered outside any `{% cache %}` block (stock, cart CSRF token).
- Homepage texts: menu « Page d'accueil » → « Blocs de l'accueil », a single `Homepage` row edited like the shop settings (`HomepageCrudController::index()` redirects to its form), read through `HomepageProviderInterface` (defaults when there is no row): one tab per section, top to bottom (« Bandeau du haut »: the strip above the navbar on every page, `Layout\HeaderStripController`; « Dernières prod »: shown, title, count, link text; « Le label »: every word and its link; « Derniers articles »: shown, title, count, link text, shelves offered; « Footer »: the text under the label's name, the motto after the copyright). The fragments reading it are tagged `homepage`.
- Footer columns: « Colonnes du footer », as many `FooterColumn` rows as wanted (title, order), each with its `FooterLink`s edited inline (`useEntryCrudForm`, `FooterLinkCrudController` has no page of its own): a CMS page, a catalogue section (`Support`) or an address, exactly one. « Tous les rayons du catalogue » (`FooterColumn::$supportsListed`) lists every `Support` first, read live (prod creates its sections with `app:catalog:import`, after the migrations). A link to an unpublished page is not shown, nor a column with nothing to follow. A page's `footerPlacement` only puts it on the bottom line now (CGV, mentions légales).

### Services: `readonly` class + interface

Every service in `src/Service` has a matching `…Interface` and is injected by interface (autowiring resolves the single implementation). Key ones:

- `CartService` — cart lives entirely in the session as `['articleId' => quantity]`; `getFullCart()` loads the lines in batches (`ArticleRepository::findForCart()`), drops unpublished, deleted and sold-out lines, caps the others to the stock and writes the corrected cart back to the session. The navbar's `countItems()` applies the same rule (`CartService::available()`) read-only, with one scalar query (`stockOfPublished()`); none for an empty cart, and none on pages that already called `getFullCart()` (it leaves the count on the main request).
- `BreadcrumbService` — builds breadcrumbs by walking the current URI segment by segment and matching each prefix to its route, skipping `page-*` segments; a prefix that is no route is left out, so give intermediate paths a real route if they should appear. Matching goes through `App\Routing\PageMatcher`, which matches as a GET whatever the current request's method (the router would otherwise use the POST of a form being handled and fail on every GET-only page); `RefererService` uses it too.
- `CustomPaginationService` — wraps KnpPaginator. Pagination is path-based, not query-string: the `{page}` route parameter is the literal string `page-N` (default `page-1`), and the service parses the digit out of it. Default 9 items/page.
- `DispatchFilterValueService` — merges the "global" filter form values into the per-page `ArticleFilterData` DTO.
- `UserDefaultAddressService` — exactly one default (`Address::$isMainAddress`, mirrored in `User::$defaultAddress`): the first address is it, a new one only when its box is ticked, `handOver()` gives it to the oldest one left when it is deleted. The address book (`App\Account\AddressBookPage`, 5 addresses at most: the add action locks the account's row, `SELECT … FOR UPDATE`, so parallel adds from several devices count one after the other) opens its form under the cards from links (`?modifier=<id>`, `?ajouter`) and shows it again with its errors (422) when the add or edit action fails.
- `RefererService`.

### Filtering flow

`src/Data/*` holds plain mutable DTOs (`ArticleFilterData`, `AddToCartWithQuantity`, `OrderDeliveryDto`, …). A catalog page instantiates the DTO, seeds it with route context (e.g. the current `Support`), binds it to a `src/Form/*FilterFormType`, passes it through `DispatchFilterValueService`, then hands it to `ArticleRepository::filterArticleQuery()` which conditionally appends `andWhere` clauses and returns a `Query` for the paginator. New filters mean touching all four layers: DTO field → form type → dispatch service → repository query.

### Doctrine / entities

- Repositories are `ServiceEntityRepository` with hand-added `save(entity, flush)` / `remove(entity, flush)` helpers. Typing comes from the `@extends ServiceEntityRepository<Entity>` docblock alone — `ServiceEntityRepository` is generic in doctrine-bundle 3, so do *not* re-add `@method find()/findBy()/...` docblocks: PHPStan 2 rejects their untyped `array` parameters.
- `Collection` properties on entities need a `@var Collection<int, Target>` docblock (PHPStan), and EasyAdmin CRUD controllers need `@extends AbstractCrudController<Entity>`.
- `QueryBuilder::setParameters()` no longer accepts an array in ORM 3 — chain `setParameter()` calls instead.
- Slugs come from `Gedmo\Slug` (StofDoctrineExtensions); most user-facing routes look up entities by `slug` or by name via `#[MapEntity(mapping: [...])]` rather than by id.
- Join entities (`WishlistItem`, `UserCollectionItems`) use **composite primary keys** made of two `#[ORM\Id] #[ORM\ManyToOne]` associations — no surrogate id, so `find()` needs an array key.
- Uploads go through VichUploaderBundle (`albums`, `media_object` and `expense_invoice` mappings, `SmartUniqueNamer`). Stored file names are bare names: get a public URL with `vich_uploader_asset()` (or `/media/<filename>`). `AlbumEventSubscriber` (`prePersist`) assigns `kbrProductionId` (`KBR#001`, `KBR#002`, …) to new KBR-produced albums, counted from existing productions — only during a POST/PATCH/PUT request, so fixtures and CLI inserts do not get one.
- Orders are snapshots taken by `PlaceOrderHandler`: lines copy name/SKU/price, `shippingAddress` copies the address as text (`Order::$address` is only a link, set to null when the entry is deleted), `shippingPrice` copies the transporter's price (0 from `ShopSettings::$freeShippingThreshold`) and `totalPrice` = lines + shipping. Display the snapshot, never the live address. The customer sees them under « Mes commandes » (`/mon-compte/mes-commandes`, maquettes 16–18): « En cours » whole, « Terminées » paginated by 9, and one page per order (`/mon-compte/mes-commandes/{reference}`) with a timeline (`App\Order\OrderProgress`: no status history is kept, so a step a cancelled or refunded order may or may not have reached shows as unknown), the next step for its status, « Remettre dans le panier » and, once delivered, « Ajouter à ma collection ». The checkout redirects there with a one-time thank-you banner (session key `AccountOrderDetailsController::JUST_PLACED`).
- Migrations in `migrations/` are the source of truth for schema; `doctrine:schema:validate` gates CI and currently passes (mapping *and* database), so keep it that way when touching associations.

### Security

Session auth via the custom `App\Security\UserAuthenticator` (login form with a CSRF badge), `app_user_provider` on `User::email`, remember-me always on (7 days, signed with the password hash, so a password change logs out every other session). Password reset uses `symfonycasts/reset-password-bundle` (`Controller/Security/ResetPasswordController`). Registration forces `ROLE_USER`; `ROLE_ADMIN` is only granted from the back office or `app:create-admin`.

Access control in `security.yaml`: `^/admin` → `ROLE_ADMIN`, `^/mon-compte` → `ROLE_USER`. On top of that, account pages declare `#[IsGranted('IS_AUTHENTICATED_FULLY')]` (a remember-me session alone does not reach them) and a non-nullable `#[CurrentUser] User $user` (`UserValueResolver` throws `AccessDeniedException`, which the firewall turns into a redirect to the login form). Every `/mon-compte/*`, `/order/*`, `/wishlist/*` and `/collection/*` route redirects anonymous visitors to `/login`. The flip side is that a **public** page must declare `#[CurrentUser] ?User $user = null`, otherwise it redirects anonymous visitors too.

Being logged in is not enough for an object loaded from the URL: check its owner with a voter in `src/Security/Voter` — `OrderVoter` (`ORDER_VIEW`, buyer or admin; `ORDER_OWN`, buyer only: the account's order pages, which mix in the viewer's collection and counters), `AddressVoter` (`ADDRESS_EDIT`, owner only) — through `#[IsGranted(Voter::X, subject: 'argument')]`. Queries for lists are scoped to the current user (`findBy(['buyer' => $user])`, `SelectUserAddressFormType` offers the user's own addresses only). `tests/Controller/User/AccountSecurityTest.php` covers the account pages.

- Forms: always `isSubmitted() && isValid()` — `isValid()` is what enforces the form's CSRF token and constraints. Constraints on a DTO (`src/Data`) do not inherit the entity's `UniqueEntity`: check uniqueness yourself (`PatchUserInformationsController::emailIsFree()`). Sensitive changes re-check the current password (`UserPassword` on `UpdatePasswordFormType` and `ConfirmEmailChangeFormType`).
- Per-object CSRF tokens (`csrf_token('address-' ~ id)` on the address book's « Par défaut » and bin buttons, `_post_button.html.twig` with `method: 'PATCH'`/`'DELETE'`, sent as `_method`): check them in the controller with `CsrfTokenManagerInterface::isTokenValid()` and redirect with a flash: a failing `#[IsCsrfTokenValid]` throws an authentication exception, which the firewall turns into a redirect to `/login`, not a 403.
- Back-office actions that change state are POST with a per-entity CSRF token (order transitions, `generateSizes`, `duplicate`).
- Shop actions that change state (cart, wishlist, collection) are POST-only with a CSRF token: render them with `partials/_post_button.html.twig` (`token_id`), check with `App\Security\ActionCsrfToken::isValid()` and redirect back when it fails. `_obfuscation_link.html.twig` (a JS `window.location`) is for navigation only. The article page's add-to-cart form posts to the article route itself and redirects (303) even when invalid (post/redirect/get).
- A new e-mail on the Informations page goes through the `email_change` limiter (3 an hour per account), then the availability check, and is only taken once the link mailed to it is opened (`App\Account\EmailChange`: `UriSigner`, 24 h, for `User::$pendingEmail` only, so a new request or a password change kills older links, bound to the address it replaces, followed logged in as the account). The current password is asked on the page the link opens (`ConfirmEmailChangeController`, GET shows the form, POST to the same signed URL), not on the Informations form: a session left open cannot move the account. The old address is told at the request and after the change. Changing the e-mail or the password signs every other device out (the session token holds the identifier and the hash) and re-issues this device's remember-me cookie (`security.authenticator.remember_me_handler.main`).
- E-mails are stored lowercased (`User::setEmail()` normalises); look users up with `UserRepository::findOneByEmail()`, which also backs the user provider (`loadUserByIdentifier()`).
- Twig: no `|raw` on user data. `Address::__toString()` returns plain text; show it with `|nl2br`, which escapes first. CMS pages go through `sanitize_html('app.rich_text_sanitizer')`.
- Private files (expense invoices) live outside `public/` and are only served by an admin route; uploads get their extension from the sniffed MIME type (`SmartUniqueNamer`), and Caddy only runs `index.php`.
- Uploads: no SVG (it runs scripts on our origin; Caddy sandboxes any old one), `SocialNetwork::$url` is http(s) only. Caddy sends nosniff, `X-Frame-Options: SAMEORIGIN`, referrer and permissions policies (HSTS in prod).
- HTML pages get a Content-Security-Policy from `App\Security\ContentSecurityPolicy`: scripts from our origin only, no inline script or `on…=""` handler — put behaviour in a Stimulus controller, or give an unavoidable inline `<script>` the page nonce (`nonce="{{ csp_nonce('script') }}"`, what EasyAdmin does). Styles stay `'unsafe-inline'` (style attributes); the style nonce EasyAdmin prints is a separate one that never unlocks scripts. Only images may come from elsewhere (https): no external script, font or stylesheet (CDN, analytics) loads until the policy allows it. No policy on the debug error page.
- Logout needs a CSRF token (`enable_csrf`, token id `logout`, field `_token`): render it with `_post_button.html.twig`. The route also accepts GET for EasyAdmin's "Sign out" link, which carries the token in the query string. A stale token redirects to the account page with a flash (`App\Security\LogoutCsrfFailure`).
- Secrets: `.env` leaves `APP_SECRET` empty, dev takes it from `.env.dev`, and `compose.prod.yaml` refuses to start without `APP_SECRET` and `POSTGRES_PASSWORD`.

Validation constraints must be built with **named arguments** — Symfony 8 rejects `new NotBlank(['message' => …])` at runtime, and PHPStan does not catch it because the constructors still accept an array.

### Cache

- **HTML pages are not HTTP-cached**: the navbar reads the session (cart, login state), so Symfony marks every page `private`. Only static files get long-lived headers, from Caddy in prod (`CADDY_SERVER_EXTRA_DIRECTIVES` in `compose.prod.yaml`): `/build/*` immutable (Encore versions file names in prod only, hence not in dev), uploads a week.
- **Fragments** that are the same for every visitor are cached with Twig's `{% cache 'key' tags([...]) %}` (`twig/cache-extra`) in the `cache.fragments` pool (filesystem in `var/share/prod/pools`, shared with the CLI; array adapter in dev/test, i.e. no caching across requests). Cached today: the strip above the navbar and the footer (key includes the year), the home sections (« Dernières prod », « Derniers articles » with one shelf per `HomeShelf` chip, toggled by `shelf-filter_controller.js`), an article page's related sections and its album's tracklist (keyed by album, shared by its pressings). The controllers pass the unexecuted `Query` and the template calls `.result` inside the block, so a hit runs no SQL.
- **Invalidation** is automatic: on every flush, `App\Cache\EntityCacheInvalidator` invalidates, for each written entity, the tag of its class and parent classes (`EntityCacheTag`: `SocialNetwork` → `social_network`, `Release` → `release` + `article`) **and** the tags of the classes holding an association to it (one level: a new `Image` clears `article`/`album`, a new `MediaObject` clears `social_network`); a collection change tags its owner. So `tags()` lists the entities a fragment reads directly, not their children. Only tags in `EntityCacheTag::FRAGMENT_TAGS` are written; `CachedTemplatesTest` fails if a template uses one missing from it. A flush inside an outer transaction (command bus) is invalidated again on `kernel.terminate`/`console.terminate`, after the commit. DBAL/DQL writes bypass it (e.g. `StockManager`), so never put stock — or anything user-specific, a CSRF token, a form or an absolute `url()` — inside a `{% cache %}` block (`CachedTemplatesTest` checks the cached partials).
- Doctrine metadata/query/result caches are the recipe's prod defaults (`doctrine.yaml`).

### Frontend

Single Encore entry `assets/app.js`; SCSS in `assets/styles` (`global.scss` + partials, Bootstrap 5). Stimulus controllers in `assets/controllers/` registered through `assets/controllers.json` — used for cart quantity, password fields, filter accordion, obfuscated links, scrollbar, the homepage shelves.

Design system (« Mini design system Kale Borroka », Claude Design handoff): three inks — `$kb-noir` #111010, `$kb-papier` #f3eee4, `$kb-rouge` #c4161c (buying and stamps only, never running text nor the only signal) — Anton (titles, prices, always uppercase), Archivo (text), IBM Plex Mono (metadata), radius 0, solid offset shadows (`6px 6px 0`).

- `_variables.scss` holds the tokens and maps them onto Bootstrap's variables; it is imported **before** Bootstrap. `_tokens.scss` exposes them as `--kb-*` custom properties. Restyle Bootstrap through its variables first, then in `components/*`; our own components are `.kb-*` (BEM: `.kb-card__cover`, `.kb-badge--rouge`).
- `$success` is black (no green: the ikurriña green is a decorative rule only), so `.btn-success`/`.alert-success` render black; buying buttons are `.btn-primary .btn-lg` (56px).
- Fonts are self-hosted through `@fontsource/*` (`assets/styles/fonts.js`, Latin + Latin Extended): the CSP allows no font from elsewhere.
- Textures are tiled SVGs in `assets/images/textures`; every `url()` to them lives in `_textures.scss`, next to the images directory, so it resolves whatever the loader. The header logo is `public/images/logo-kbr.jpg` (400px, the 2000px original is `logo.jpg`).
- Accessibility (RGAA 4.1 / WCAG 2.2 AA): `<html lang="fr">`, skip link to `main#contenu`, focus ring `3px solid noir` offset 3px + paper ring, 44px minimum targets, icons `aria-hidden` and icon buttons with an `aria-label`, state never carried by colour alone (icon + label + border style), `prefers-reduced-motion` honoured.
- Error pages (`templates/bundles/TwigBundle/Exception`) extend `layout/_error.html.twig`, which renders no sub-request and runs no query: they must still work when the database is down.

## Conventions

- New entities follow Schema.org naming (e.g. `Expense` ≈ `Invoice`: `provider`, `totalPaymentDue`, `paymentDueDate`; `EventSale` ≈ `SellAction`: `startTime`, `price`, `location`), documented in the class docblock.
- `declare(strict_types=1);` in every PHP file of `src/`, `tests/` and `migrations/` (`StrictTypesTest` fails otherwise).
- Tests reach the container through `App\Tests\ServiceTrait::service(Foo::class)` (typed, unlike `getContainer()->get()`), and keep `KernelBrowser` as a non-nullable property set in `setUp()`.
- Entity setters return `static` for chaining.
- Fixtures are Alice YAML in `fixtures/`. Generated albums, releases and songs are linked by index (`album_N` → `artist_((N - 1) % 25 + 1)`, `release_N`, `song_((N - 1) * 10 + 1..N * 10)`) so artists, labels and tracklists stay consistent; `album_1..10` are KBR productions. Alice cannot chain a dynamic reference with a property (`@album_<current()>->name` fails to parse): read a sibling property with `<($album->…)>` instead. `order.yml` holds ~13 months of orders bought by `customer.yml`'s `user_customer_*` (never by `user_admin`/`user_user`: tests expect their order history empty), with nothing paid today and at most 7 orders to process — the dashboard tests rely on both. Order lines copy the article's SKU, which is why fixture articles set `sku` explicitly. Fixture orders have no shipping cost (`shippingPrice` 0). Escape a literal `@` as `\@` (emails).
- `tests/Smoke/EveryPageRendersTest.php` requests every GET route (visitor and admin) and submits every one-click button of the shop pages, failing on any 5xx. A new route whose parameter it cannot resolve fails it: teach `valueOf()`, or add the route to `EXCLUDED` with a reason. Every EasyAdmin entity needs at least one fixture row (`fixtures/media.yml` holds the image, media object and social network ones).
- Styles are a closed list, `Style::OFFICIAL` (after Discogs' styles, no parent genre), seeded by migration `Version20261001120000` and unique by name: albums pick them in a multiple select, the Styles admin page is read-only, and `app:catalog:import` refuses a style outside the list. Adding one means adding it to `Style::OFFICIAL`, `fixtures/style.yml` and a migration (`OfficialStylesTest` checks the database holds exactly the list).
- One tracklist per album, with the vinyl positions (`Song::$position`, A1, B3; null on a CD). `App\Catalog\TracklistLayout::sides()` (Twig `tracklist_sides(tracks, format)`) groups it by side for a vinyl or a tape, by disc past two vinyl sides (C/D = disc 2), and leaves it flat for a CD or when a track has no position; the article page caches it per album and format.
- `App\Enum\SupportType` is the single source of truth for supports: `Support::$code` is typed with it, and the `{support}` route segment gets its requirement from `new EnumRequirement(SupportType::class)` in `CatalogBySupportController` and `ArticleDetailsController`. Adding a case means adding a `Support` row too: in the fixtures, and in production through `app:catalog:import`, which creates the row of every case that has none (no migration inserts them) — `CatalogControllerTest::testEverySupportTypeHasAReachableCatalogPage` guards the pairing. `Support::$name` stays the URL segment; branch on `$code`.

## Handoff — state of the work (2026-10-21)

Written so a session on another machine can pick up. `dev` = `e5a32fd` (#140), clean apart from the untracked `ui/`. Auto-memory (`~/.claude/projects/…/memory/`) does not travel with git: what matters from it is below.

### Done, merged into `dev`

- Stack: Symfony 8.1, EasyAdmin 5, FrankenPHP worker, deploy on the VPS (#132), catalogue import (#118, #121), official styles (#120), tracklists by side (#121).
- Design system « Kale Borroka » (#122), then the redesign after the mockups: homepage sections / header strip / footer editable in the admin (#133, #135), login + registration + password reset (#136, mockups 08/09), account dashboard (#138, mockup 07), wantlist + collection with e-mail alerts and « tout ajouter au panier » (#139), addresses / informations / password with confirmed e-mail change (#140).
- Mails go through a Messenger worker (#137). Label funds, expenses, event sales (#103). Fragment cache (#107). Security audit of 2026-09-30 closed (#105, #108, #109).

### Mockups (`ui/`, untracked: commit it or copy it over)

`ui/KBR Maquettes - *.dc.html` (Boutique, Parcours, Compte et Contenu, Wantlist et Collection) + `ui/KBR Design System.dc.html`. Screens: Boutique = accueil, catalogue, fiche disque, fiche merch, filtres mobile, menu mobile; Parcours = compte, panier (+ vide), tunnel; Compte et Contenu = connexion, inscription, contenu/CGV; Wantlist et Collection. Account, login, registration, wantlist, collection are done. **Not checked against the code**: whether catalogue, fiche disque/merch, filtres, panier and tunnel already follow their mockups — compare each page with its mockup before assuming either way.

### Open work, in rough priority

1. **Payment** (nothing online, no bank account, the label is not a declared entity). Planned: order placed → mail to `ShopSettings::contactEmail` + « commande reçue » to the customer; admin action « Envoyer le lien de paiement » mails `https://paypal.me/<handle>/<total>EUR` with the order reference; the existing admin `pay` transition (takes the stock) → « paiement reçu » mail. Manual validation recommended (small stock, avoids two buyers paying the last copy). Waiting on the owner: PayPal.me handle, label inbox address, manual vs immediate link. PayPal personal accounts forbid regular commercial use (freeze risk): « Biens et services », never « entre proches »; an association loi 1901 is the clean fix. Order mails are not built yet.
2. **Prod go-live** (preprod is live since 2026-10-14, VPS `193.70.3.86`, OVH VPS-1): real Zimbra `MAILER_DSN` in `prod.env` (prod and preprod currently `null`), PR `dev` → `main` (`main` is far behind; the merge deploys, a reviewer approves), then on prod `app:catalog:import` and `app:create-admin`; backups (not done); extra VPS hardening (sshd, fail2ban, auto-reboot, sysctl) handed to the owner. `gh` needs `-R maxime-urbanski/kale-borroka-sf` (several remotes). Deploy migrations still to run in prod: `Version20260930090935` (funds), `Version20261001150000` (song positions), the 3 of #108 (it aborts if two accounts differ only by e-mail case), the Messenger table.
3. **Catalogue doubts** to settle in `data/catalog/stock.yaml` BEFORE the first prod import (afterwards edits to an imported SKU do nothing, use the admin): the 3 « VA Benefit vol 2/3/4 » CDs (unidentified, unpublished, no tracks), Wasted Youth 7" (no quantity), guessed vinyl colours (« noir »), Barrikade 11 vs 12 tracks, Saccage colour/limitation, S-Molest and HVB not on Discogs. Side titles on the article page never checked in a browser.
4. **Never looked at in a browser**: the POST buttons of cart/wishlist/collection in `input-group`/`btn-group`, `address_controller.js` (delete / default address). Prod checks after deploy: `Cache-Control` on `/build/*` and uploads, an admin edit (footer page, cover) shows up at once, label funds card + invoices volume (backup it).
5. Rotate the old committed `APP_SECRET` in prod if prod ever ran without the variable. An « active » flag on transporters. Dependabot PRs (#92–#131) are open and unreviewed.

### How the owner wants work done (applies to every task)

- One topic at a time, one PR per task, branched off an up-to-date `dev`. Several asks at once → ship them one after the other, never stack topics in local commits.
- Test first, watch it fail, then fix (mutation check: revert the fix, the test must fail).
- When a task looks done, launch **without asking**: `/code-review` and a security audit (in parallel), then fix only confirmed findings (say plainly when there are none). Do this BEFORE opening the PR.
- Then drive every touched user journey in a real browser with Playwright (desktop 1440 and mobile 390; Chrome headless/daemon is fine; local HTTPS is Caddy's own CA, so ignore certificate errors through a CDP session). Screenshots go in `.playwright-mcp/` (gitignored). Follow the link inside the mails in Mailpit (host and scheme), not just their arrival.
- Commit / merge only when asked: « commit, PR avec body, si CI verte merge ». PR body = Summary + Test plan, and list what was not covered (e.g. not tried in a browser). Merge with `gh pr merge --squash --delete-branch`, subject `<type>(scope): … (#N)`, only when CI is green **on the head commit** (compare the run's `headSha` with `git rev-parse HEAD`); never on red or pending.
- New entities follow Schema.org naming. Finance features stay admin-only (never on the public site).
- Pitfalls: `composer install` is not re-run after a branch switch (a missing `html-sanitizer` produced fake test failures); a new faker call in fixtures shifts later seeded values (`AdminLayoutTest` asserts them); a failing `#[IsCsrfTokenValid]` redirects to `/login` (check tokens by hand, see Security); the `catalog-editions` branch is abandoned (SupportType on `dev` is the model).
