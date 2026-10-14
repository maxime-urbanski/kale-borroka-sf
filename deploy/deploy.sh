#!/usr/bin/env bash
# Deploys one commit of the shop on the VPS: deploy.sh <prod|preprod> <commit SHA>.
#
# The forced command of the CI's SSH key (docs/deployment.md): that key can run nothing
# else, and only a commit of the environment's branch, whose image the CI pushed. It runs
# from a copy in /srv/kbr, outside the checkouts it moves.
set -euo pipefail

KBR_ROOT=${KBR_ROOT:-/srv/kbr}

usage() {
	echo 'usage: deploy.sh <prod|preprod> <40-character commit SHA>' >&2
	exit 64
}

# Through SSH, the arguments arrive in SSH_ORIGINAL_COMMAND.
read -r -a args <<<"${SSH_ORIGINAL_COMMAND:-$*}"
[[ ${#args[@]} -eq 2 && ${args[1]} =~ ^[0-9a-f]{40}$ ]] || usage
target=${args[0]}
sha=${args[1]}
case $target in
prod) branch=main ;;
preprod) branch=dev ;;
*) usage ;;
esac

checkout=$KBR_ROOT/$target
git -C "$checkout" fetch --quiet origin "$branch"
if ! git -C "$checkout" merge-base --is-ancestor "$sha" "origin/$branch"; then
	echo "$sha is not on $branch" >&2
	exit 65
fi
git -C "$checkout" checkout --quiet --detach "$sha"
cd "$checkout"

export IMAGE_TAG=sha-$sha EDGE_ALIAS=kbr-$target
app=(docker compose --project-name "kbr-$target" --env-file "$KBR_ROOT/$target.env"
	-f compose.yaml -f compose.prod.yaml -f deploy/compose.app.yaml)
"${app[@]}" pull --quiet php
# The entrypoint runs the migrations before FrankenPHP starts; --wait fails the deploy
# when the container does not become healthy.
"${app[@]}" up --detach --wait --remove-orphans

# The edge follows main: a routing change reaches it with the next prod deploy.
if [[ $target == prod ]]; then
	cp deploy/edge/compose.yaml deploy/edge/Caddyfile "$KBR_ROOT/edge/"
	edge=(docker compose --env-file "$KBR_ROOT/edge.env" -f "$KBR_ROOT/edge/compose.yaml")
	"${edge[@]}" up --detach --wait
	"${edge[@]}" exec -T caddy caddy reload --config /etc/caddy/Caddyfile
fi

# Unused images built more than two weeks ago (earlier commits): the rest stays at hand
# for a rollback.
docker image prune --all --force --filter 'until=336h' >/dev/null
echo "kbr-$target is at $sha"
