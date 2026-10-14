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
  each domain to `kbr-prod:80` / `kbr-preprod:80` over the shared `edge` Docker network.
- `deploy/compose.app.yaml`, on top of `compose.yaml` + `compose.prod.yaml`: the shop from the
  GHCR image, plain HTTP (`SERVER_NAME=:80`), no published port, trusting the edge's
  `X-Forwarded-*` headers. Each stack has its own database and upload volumes.
- `.github/workflows/deploy.yml`: on every push to `dev` (→ preprod) or `main` (→ prod), builds
  the `frankenphp_prod` image as `ghcr.io/maxime-urbanski/kale-borroka-sf:sha-<commit>` (or
  reuses it when main reaches a commit preprod already runs), then connects over SSH.
- `deploy/deploy.sh <prod|preprod> <sha>`: the SSH key's forced command. It checks the commit is
  on the environment's branch, checks it out, pulls its image and restarts the stack (the
  entrypoint runs the migrations). A prod deploy also refreshes the edge from `deploy/edge/`.

## One-time setup

### DNS (OVH zone)

`A` (and `AAAA`) records for `@`, `www` and `preprod` → the VPS. Caddy cannot get a
certificate before they resolve.

### Server

As root:

```console
adduser --disabled-password --gecos '' deploy
usermod -aG docker deploy            # once Docker is installed (docs.docker.com/engine/install/ubuntu)
install -d -o deploy -g deploy /srv/kbr /srv/kbr/edge
```

Docker log rotation, in `/etc/docker/daemon.json` (then `systemctl restart docker`):

```json
{ "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "3" } }
```

As `deploy`:

```console
cd /srv/kbr
git clone https://github.com/maxime-urbanski/kale-borroka-sf.git prod
git clone https://github.com/maxime-urbanski/kale-borroka-sf.git preprod
cp preprod/deploy/deploy.sh deploy.sh   # a copy: the checkouts move under it
cp preprod/deploy/edge/compose.yaml preprod/deploy/edge/Caddyfile edge/
```

`deploy.sh` is not updated by deploys: copy it again by hand when it changes.

### Secrets

Three files in `/srv/kbr`, `chmod 600`, never committed. Single-quote any value holding a `$`,
or Compose expands it.

`prod.env` and `preprod.env` (different values in each):

```dotenv
APP_SECRET=          # openssl rand -hex 32
POSTGRES_PASSWORD=   # openssl rand -hex 24
DEFAULT_URI=https://kale-borroka-records.com          # https://preprod.… for preprod
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

### Edge

```console
docker compose --env-file /srv/kbr/edge.env -f /srv/kbr/edge/compose.yaml up -d --wait
```

It answers 502 for a stack that is not running yet.

### Deploy key

On your machine: `ssh-keygen -t ed25519 -N '' -C github-deploy -f kbr-deploy`. On the VPS, in
`~deploy/.ssh/authorized_keys`, one line:

```
command="/srv/kbr/deploy.sh",restrict ssh-ed25519 AAAA… github-deploy
```

`restrict` takes away the shell, port forwarding and the TTY: the key can only run
`deploy.sh`, with the two words the workflow sends.

### GitHub

- Secrets (Settings → Secrets and variables → Actions): `DEPLOY_SSH_HOST` (the VPS address),
  `DEPLOY_SSH_KEY` (the private key `kbr-deploy`), `DEPLOY_SSH_KNOWN_HOSTS` (the output of
  `ssh-keyscan <vps>`, checked against the VPS's own fingerprints).
- Environments `preprod` and `production` are created by the first run; give `production`
  a required reviewer to approve each prod deploy by hand.
- After the first image push, check the package `kale-borroka-sf` is public (Packages →
  Package settings); otherwise the VPS needs `docker login ghcr.io` with a `read:packages` token.

### First deploy

Push to `dev` (or run the workflow by hand), then, as `deploy` in `/srv/kbr/preprod`:

```console
alias kbr='docker compose -p kbr-preprod --env-file /srv/kbr/preprod.env -f compose.yaml -f compose.prod.yaml -f deploy/compose.app.yaml'
export IMAGE_TAG=sha-$(git rev-parse HEAD) EDGE_ALIAS=kbr-preprod
kbr exec php bin/console app:catalog:import --dry-run
kbr exec php bin/console app:catalog:import
kbr exec php bin/console app:create-admin you@example.org
```

Same for prod once `main` holds the deploy files (`kbr-prod`, `/srv/kbr/prod`, `prod.env`).

## Operating

- **Rollback**: `/srv/kbr/deploy.sh prod <older sha>` as `deploy`.
  The image comes back, the database does not: migrations are not reverted.
- **Images** of earlier commits are pruned two weeks after they were built.
- **Backups** (`pg_dump` and the `uploads`, `media`, `invoices` volumes, off the VPS) are not
  set up by this.
