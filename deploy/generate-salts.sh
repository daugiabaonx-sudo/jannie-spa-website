#!/usr/bin/env bash
# =============================================================================
# JN Step 9 — Fresh Cryptographic Salts Generator (`deploy/generate-salts.sh`)
# Generates 8 unique, high-entropy 64-byte base64 salts for `.env.production`.
# Usage:
#   ./deploy/generate-salts.sh
# =============================================================================
set -euo pipefail

KEYS=(
  "AUTH_KEY"
  "SECURE_AUTH_KEY"
  "LOGGED_IN_KEY"
  "NONCE_KEY"
  "AUTH_SALT"
  "SECURE_AUTH_SALT"
  "LOGGED_IN_SALT"
  "NONCE_SALT"
)

echo "# Generated fresh WordPress cryptographic salts at $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
for KEY in "${KEYS[@]}"; do
  VAL="$(openssl rand -base64 48 | tr -d '\n\r')"
  echo "${KEY}='${VAL}'"
done
