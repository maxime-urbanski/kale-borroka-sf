#!/usr/bin/env bash
# Deploys one commit of the shop on the VPS.
#
# By hand, as deploy: deploy.sh <prod|preprod> <commit SHA>, any commit of the environment's
# branch (main or dev), which is how to roll back.
#
# From the CI: each environment has its own SSH key, whose forced command pins the target,
# command="/srv/kbr/deploy.sh prod": the key only sends the SHA (SSH_ORIGINAL_COMMAND), and
# only moves the environment forward on its branch.
#
# Runs from a copy in /srv/kbr, outside the checkouts it moves (docs/deployment.md), and
# always as deploy: as root, it would leave root-owned git objects that break the next CI
# deploy.
set -euo pipefail

KBR_ROOT=${KBR_ROOT:-/srv/kbr}

fail() {
	echo "$2" >&2
	exit "$1"
}
usage() {
	fail 64 'usage: deploy.sh <prod|preprod> <40-character commit SHA>'
}

target=${1:-}
case $target in
prod) branch=main ;;
preprod) branch=dev ;;
*) usage ;;
esac
if [[ -v SSH_ORIGINAL_COMMAND ]]; then
	[[ $# -eq 1 ]] || usage
	sha=$SSH_ORIGINAL_COMMAND
	from_ci=true
else
	[[ $# -eq 2 ]] || usage
	sha=$2
	from_ci=false
fi
[[ $sha =~ ^[0-9a-f]{40}$ ]] || usage

# One deploy at a time on the VPS: a rollback by hand and a CI run would mix their checkouts.
# The lock is on the script itself, read-only: no lock file to own.
exec 9<"$0"
flock --wait 900 9 || fail 75 'another deploy is still running'

checkout=$KBR_ROOT/$target
git -C "$checkout" fetch --quiet origin "$branch"
git -C "$checkout" merge-base --is-ancestor "$sha" "origin/$branch" ||
	fail 65 "$sha is not on $branch"
deployed=$(git -C "$checkout" rev-parse HEAD)
if $from_ci; then
	git -C "$checkout" merge-base --is-ancestor "$deployed" "$sha" ||
		fail 65 "$sha is older than the deployed $deployed: roll back by hand"
fi
git -C "$checkout" cat-file -e "$sha:deploy/compose.app.yaml" 2>/dev/null ||
	fail 65 "$sha predates deploy/compose.app.yaml: it cannot be deployed this way"

[[ $(docker network ls --quiet --filter "name=^edge-$target\$") ]] ||
	fail 69 "no edge-$target network: start the edge first (docs/deployment.md)"

# Until the new containers replace the running ones, a failure puts the checkout back on the
# commit that runs.
git -C "$checkout" checkout --quiet --detach "$sha"
trap 'git -C "$checkout" checkout --quiet --detach "$deployed"' EXIT
cd "$checkout"
# A broken edge config fails here, on preprod first, before it can reach the live edge.
deploy/edge/validate.sh

export IMAGE_TAG=sha-$sha EDGE_ALIAS=kbr-$target EDGE_NETWORK=edge-$target
app=(docker compose --project-name "kbr-$target" --env-file "$KBR_ROOT/$target.env"
	-f compose.yaml -f compose.prod.yaml -f deploy/compose.app.yaml)
"${app[@]}" pull --quiet php
"${app[@]}" up --detach --wait database
# Migrations first, in a container of their own: its entrypoint runs them (all or nothing),
# then the command checks none is left. When one fails, the running version stays online.
"${app[@]}" run --rm --no-deps -T php bin/console doctrine:migrations:up-to-date
trap - EXIT
"${app[@]}" up --detach --wait --remove-orphans

# The edge follows main's head: neither preprod nor a prod rollback changes it.
if [[ $target == prod && $sha == "$(git rev-parse "origin/$branch")" ]]; then
	cp deploy/edge/compose.yaml deploy/edge/Caddyfile "$KBR_ROOT/edge/"
	edge=(docker compose --env-file "$KBR_ROOT/edge.env" -f "$KBR_ROOT/edge/compose.yaml")
	"${edge[@]}" up --detach --wait
	"${edge[@]}" exec -T caddy caddy reload --config /etc/caddy/Caddyfile
fi

# Unused images built more than two weeks ago (earlier commits): the rest stays at hand
# for a rollback.
docker image prune --all --force --filter 'until=336h' >/dev/null
echo "kbr-$target is at $sha"
