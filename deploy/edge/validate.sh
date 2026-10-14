#!/usr/bin/env bash
# Checks the edge Caddyfile next to this script, with stand-in values for the secrets of
# edge.env: run by CI and by deploy.sh before the file can reach the live edge.
set -euo pipefail

dir=$(cd "$(dirname "$0")" && pwd)
if ! output=$(docker run --rm --log-driver none \
	-e KBR_DOMAIN=kale-borroka-records.com \
	-e ACME_EMAIL=validate@example.org \
	-e PREPROD_BASIC_AUTH_USER=validate \
	-e 'PREPROD_BASIC_AUTH_HASH=$2a$14$nID/s8XRs.VCra3dH7qekexUbhFMhMR.Wa0jStor8FtGd59jPYvl2' \
	-v "$dir:/etc/caddy:ro" \
	caddy:2-alpine caddy validate --config /etc/caddy/Caddyfile 2>&1); then
	echo "$output" >&2
	exit 1
fi
