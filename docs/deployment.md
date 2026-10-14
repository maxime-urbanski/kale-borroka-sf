# Deploying on the VPS

Prod and preprod run side by side on one VPS (OVH VPS-1, Ubuntu 26.04):

```
Internet ─► edge Caddy (80/443, Let's Encrypt)            /srv/kbr/edge
              ├─ kale-borroka-records.com         ─► kbr-prod     (php + database)  /srv/kbr/prod     main
              ├─ www.…                            ─► redirect to the bare domain
              └─ preprod.kale-borroka-records.com ─► kbr-preprod  (php + database)  /srv/kbr/preprod  dev
                 basic auth, noindex
```

- `deploy/edge/`: the only container publishing ports. It holds the certificates and proxies
  each domain to `kbr-prod:80` / `kbr-preprod:80`, each over its own network (`edge-prod`,
  `edge-preprod`): preprod cannot reach prod.
- `deploy/compose.app.yaml`, on top of `compose.yaml` + `compose.prod.yaml`: the shop from the
  GHCR image, plain HTTP (`SERVER_NAME=:80`), no published port, trusting the edge's
  `X-Forwarded-For`/`-Proto` and only its own domain (`TRUSTED_HOSTS`). Each stack has its own
  database, upload and session volumes.
- `.github/workflows/ci.yml` runs on pushes to `dev` and `main` too; once the tests pass, it
  calls `deploy.yml`, which builds the `frankenphp_prod` image as
  `ghcr.io/maxime-urbanski/kale-borroka-sf:sha-<commit>` from scratch (no cache, no reuse of an
  existing tag: nothing else on the repository chooses the prod image), deploys it over SSH,
  then moves the `preprod`/`prod` tag. Pushes to a branch deploy one after the other, in order.
- `deploy/deploy.sh`: each CI key's forced command. It only takes a commit of the
  environment's branch, newer than the deployed one, and validates the edge Caddyfile. It
  pulls the image, runs the migrations in a one-off container, then replaces the stack; a
  failure before that leaves the running version online and the checkout on it. Deploying
  main's head also refreshes the edge.

## One-time setup

### DNS (OVH zone)

`A` and `AAAA` records for `@`, `www` and `preprod` → the VPS. Caddy cannot get a
certificate before they resolve.

### Server

As root, after installing Docker (docs.docker.com/engine/install/ubuntu):

```console
adduser --disabled-password --gecos '' deploy
usermod -aG docker deploy
install -d -o deploy -g deploy /srv/kbr /srv/kbr/edge
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443 && ufw enable
```

SSH stays open to the world (GitHub's runners deploy from changing addresses): keys only. In
`/etc/ssh/sshd_config.d/10-kbr.conf`, then `systemctl reload ssh`:

```
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin prohibit-password
```

Docker log rotation, in `/etc/docker/daemon.json` (then `systemctl restart docker`):

```json
{ "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "3" } }
```

The edge networks get IPv6 (`enable_ipv6`, Docker ≥ 27 picks the subnet): without it, every
IPv6 visitor would reach Caddy from Docker's gateway and share one address.

As `deploy`:

```console
cd /srv/kbr
git clone --branch main https://github.com/maxime-urbanski/kale-borroka-sf.git prod
git clone --branch dev https://github.com/maxime-urbanski/kale-borroka-sf.git preprod
cp preprod/deploy/deploy.sh deploy.sh   # a copy: the checkouts move under it
cp preprod/deploy/edge/compose.yaml preprod/deploy/edge/Caddyfile edge/
```

`deploy.sh` is not updated by deploys: copy it again by hand when it changes. Never run it
as root: it would leave root-owned files in the checkouts, and the next CI deploy would fail.

### Secrets

Three files in `/srv/kbr`, `chmod 600`, never committed. Single-quote any value holding a `$`
or a `\`, or Compose expands it.

`prod.env` and `preprod.env` (different values in each):

```dotenv
APP_SECRET=          # openssl rand -hex 32
POSTGRES_PASSWORD=   # openssl rand -hex 24
DEFAULT_URI=https://kale-borroka-records.com             # https://preprod.… for preprod
TRUSTED_HOSTS='^kale-borroka-records\.com$'              # '^preprod\.kale-borroka-records\.com$'
MAILER_DSN=smtp://contact%40kale-borroka-records.com:URL_ENCODED_PASSWORD@smtp.mail.ovh.net:465
MAILER_DEFAULT_SENDER=contact@kale-borroka-records.com
```

Preprod sends no mail: `MAILER_DSN=null://null`. The sender must be the SMTP mailbox itself
(Zimbra relays nothing else); check the SMTP host in the OVH manager.

`edge.env`:

```dotenv
ACME_EMAIL=contact@kale-borroka-records.com
PREPROD_BASIC_AUTH_USER=kbr
PREPROD_BASIC_AUTH_HASH='…'   # docker run --rm caddy:2-alpine caddy hash-password --plaintext '…'
```

Caddy does not rate-limit basic auth: give preprod a long random password.

### Edge

Before any stack, it creates their networks:

```console
docker compose --env-file /srv/kbr/edge.env -f /srv/kbr/edge/compose.yaml up -d --wait
```

It answers 502 for a stack that is not running yet.

### Deploy keys

One key per environment, so that the preprod one cannot deploy prod. On your machine:

```console
ssh-keygen -t ed25519 -N '' -C kbr-deploy-prod -f kbr-deploy-prod
ssh-keygen -t ed25519 -N '' -C kbr-deploy-preprod -f kbr-deploy-preprod
```

On the VPS, in `~deploy/.ssh/authorized_keys`:

```
command="/srv/kbr/deploy.sh prod",restrict ssh-ed25519 AAAA… kbr-deploy-prod
command="/srv/kbr/deploy.sh preprod",restrict ssh-ed25519 AAAA… kbr-deploy-preprod
```

`restrict` takes away the shell, port forwarding and the TTY. The forced command pins the
environment; the key only sends a commit SHA, and can only move that environment forward on
its branch.

### GitHub

- Environments (Settings → Environments) `preprod` and `production`:
  - deployment branches limited to `dev` and `main` respectively;
  - in each, the secret `DEPLOY_SSH_KEY` (its private key);
  - on `production`, a required reviewer to approve each prod deploy.
- Repository secrets: `DEPLOY_SSH_HOST` (the VPS address) and `DEPLOY_SSH_KNOWN_HOSTS` (the
  output of `ssh-keyscan <vps>`, checked against the VPS's own fingerprints).
- Branch protection on `dev` and `main`: changes through a pull request with green CI. Preprod
  runs `dev`'s compose files as `deploy`, who is in the docker group (root on the VPS): a
  commit on `dev` can reach prod's data without the production approval.
- After the first image push, check the package `kale-borroka-sf` is public (Packages →
  Package settings); otherwise the VPS needs `docker login ghcr.io` with a `read:packages` token.

### First deploy

Push to `dev` (or run the Deploy workflow by hand on `dev`), then, as `deploy` in
`/srv/kbr/preprod`:

```console
alias kbr='docker compose -p kbr-preprod --env-file /srv/kbr/preprod.env -f compose.yaml -f compose.prod.yaml -f deploy/compose.app.yaml'
export IMAGE_TAG=sha-$(git rev-parse HEAD) EDGE_ALIAS=kbr-preprod EDGE_NETWORK=edge-preprod
kbr exec php bin/console app:catalog:import --dry-run
kbr exec php bin/console app:catalog:import
kbr exec php bin/console app:create-admin you@example.org
```

Same for prod once `main` holds the deploy files (`kbr-prod`, `/srv/kbr/prod`, `prod.env`,
`edge-prod`).

## Operating

- **Rollback**: `/srv/kbr/deploy.sh prod <older sha>` as `deploy` (the CI keys only go
  forward). The image comes back, the database does not: migrations are not reverted. The
  edge keeps main's head.
- **Downtime**: a deploy restarts the php container, a few seconds of 502. Sessions (logins,
  carts) are on a volume and survive it.
- **Images** of earlier commits are pruned two weeks after they were built.
- **Backups** (`pg_dump` and the `uploads`, `media`, `invoices` volumes, off the VPS) are not
  set up by this.
