# Kale Borroka Records

E-commerce site of Kale Borroka Records, an antifascist record label and distro.

Built on Symfony, running on [FrankenPHP](https://frankenphp.dev) and
[Caddy](https://caddyserver.com/) with the [Docker](https://www.docker.com/)-based
runtime from [symfony-docker](https://github.com/dunglas/symfony-docker).

![CI](https://github.com/Maxiloud/kale-borroka-sf/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --pull` to build the images
3. Run `docker compose up --wait` to start the project
4. Run `yarn install && yarn dev` on the host to build the assets — without them
   every page fails with `Asset manifest file … does not exist`
5. Open `https://localhost` and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
6. Run `docker compose down --remove-orphans` to stop the containers

The HTTP port defaults to `81` (`HTTP_PORT`) so it does not clash with other
projects; HTTPS and HTTP/3 use `443` (`HTTPS_PORT`, `HTTP3_PORT`).

Mails sent in dev are caught by [Mailpit](https://mailpit.axllent.org) on
<http://localhost:8025> (`MAILPIT_PORT`).

## Common Commands

Everything PHP runs inside the `php` container, which serves the app *and* runs
the CLI — there is no separate web server container.

```bash
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console hautelook:fixtures:load   # dev + test only
docker compose exec php bin/console app:create-admin [email]  # create/promote a ROLE_ADMIN user

docker compose exec -T php bin/phpunit
docker compose exec -T php vendor/bin/phpstan analyse --memory-limit=-1
docker compose exec -T php vendor/bin/php-cs-fixer fix
```

Assets are built on the host: `yarn dev`, `yarn watch`, `yarn build`.

Dependencies are installed by the entrypoint only when `vendor/` is empty. After
changing `composer.json`, run `docker compose exec php composer install` yourself.

## Deploying in Production

```bash
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
SERVER_NAME=your-domain-name.example.com \
APP_SECRET=<random> \
  docker compose -f compose.yaml -f compose.prod.yaml up --wait
```

The production image builds the Webpack Encore assets itself (`assets_builder`
stage) and runs as `www-data`. Uploaded files live in the `uploads` and `media`
volumes declared in `compose.prod.yaml`.

When switching between the dev and prod stacks on the same machine, drop the Caddy
volumes first — the dev container runs as root and leaves a root-owned local CA in
them, which the `www-data` prod container cannot read:

```bash
docker volume rm kale-borroka-sf_caddy_data kale-borroka-sf_caddy_config
```

Note that `MAILER_DSN` in `.env` points at the dev Mailpit container; override it
in production before `composer dump-env prod` bakes it in.

## Docs

The upstream template documentation applies to this stack:
[options](https://github.com/dunglas/symfony-docker/blob/main/docs/options.md),
[production](https://github.com/dunglas/symfony-docker/blob/main/docs/production.md),
[Xdebug](https://github.com/dunglas/symfony-docker/blob/main/docs/xdebug.md),
[TLS](https://github.com/dunglas/symfony-docker/blob/main/docs/tls.md),
[troubleshooting](https://github.com/dunglas/symfony-docker/blob/main/docs/troubleshooting.md).

## Credits

Runtime based on [symfony-docker](https://github.com/dunglas/symfony-docker),
created by [Kévin Dunglas](https://dunglas.dev), co-maintained by
[Maxime Helias](https://twitter.com/maxhelias) and sponsored by
[Les-Tilleuls.coop](https://les-tilleuls.coop).
