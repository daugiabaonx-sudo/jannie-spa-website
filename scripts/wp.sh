#!/usr/bin/env bash
# Short wrapper for running WP-CLI inside the wpcli container on demand.
# Equivalent shell alias:
#   alias jnwp='docker compose run --rm wpcli wp'
set -euo pipefail
exec docker compose run --rm wpcli wp "$@"
