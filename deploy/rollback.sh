#!/usr/bin/env bash
# =============================================================================
# JN Step 9 — Emergency Rollback Script (`deploy/rollback.sh`)
# Restores the previous file release directory and imports the pre-deploy
# database snapshot in < 60 seconds.
#
# Usage on production server:
#   ./deploy/rollback.sh /var/www/PROD_DOMAIN/backups/pre-deploy.sql.gz /var/www/PROD_DOMAIN/releases/previous
# =============================================================================
set -euo pipefail

DB_SNAPSHOT="${1:-}"
PREV_FILES_DIR="${2:-}"
WEB_ROOT="${WEB_ROOT:-/var/www/PROD_DOMAIN/wordpress}"

if [[ -z "${DB_SNAPSHOT}" || ! -f "${DB_SNAPSHOT}" ]]; then
  echo "ERROR: Valid database snapshot (.sql.gz) is required as argument 1."
  echo "Usage: ./deploy/rollback.sh <path-to-pre-deploy.sql.gz> [path-to-previous-wordpress-files]"
  exit 1
fi

echo "================================================================="
echo "JN EMERGENCY ROLLBACK INITIATED"
echo "  Target Web Root : ${WEB_ROOT}"
echo "  DB Snapshot     : ${DB_SNAPSHOT}"
echo "  Previous Files  : ${PREV_FILES_DIR:-<skipping file restore>}"
echo "================================================================="

# 1. Restore previous WordPress files if provided
if [[ -n "${PREV_FILES_DIR}" && -d "${PREV_FILES_DIR}" ]]; then
  echo "[1/3] Restoring previous files from ${PREV_FILES_DIR} to ${WEB_ROOT}..."
  rsync -a --delete --exclude='media/' "${PREV_FILES_DIR}/" "${WEB_ROOT}/"
else
  echo "[1/3] No previous files directory specified; keeping current files."
fi

# 2. Restore database snapshot
echo "[2/3] Importing pre-deploy database snapshot ${DB_SNAPSHOT}..."
if command -v wp >/dev/null 2>&1; then
  gzip -dc "${DB_SNAPSHOT}" | wp db import - --path="${WEB_ROOT}"
  wp cache flush --path="${WEB_ROOT}" || true
  wp rewrite flush --hard --path="${WEB_ROOT}" || true
else
  echo "ERROR: WP-CLI ('wp') not found on PATH."
  exit 1
fi

# 3. Clear WP Super Cache static files
echo "[3/3] Purging static page cache..."
rm -rf "${WEB_ROOT}/app/cache/supercache/"* "${WEB_ROOT}/app/cache/blogs/"* 2>/dev/null || true

echo "================================================================="
echo "ROLLBACK COMPLETE. Verify site health immediately."
echo "================================================================="
