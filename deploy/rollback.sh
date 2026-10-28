#!/usr/bin/env bash
# =============================================================================
# JN Step 9 — Emergency Rollback Script (`deploy/rollback.sh`)
# Restores the pre-deploy database snapshot and the previous file release.
#
# Order of operations (each step only runs if the previous one succeeded):
#   1. Snapshot the CURRENT database (orders/bookings made since the deploy are kept
#      in that file and can be recovered by hand).
#   2. Import the pre-deploy database snapshot.
#   3. Restore the previous release files (production /media/ uploads are never touched).
#   4. Flush caches.
#
# Usage on production server:
#   WEB_ROOT=/var/www/<domain>/wordpress BACKUP_DIR=/var/www/<domain>/backups \
#     ./deploy/rollback.sh [--yes] <pre-deploy.sql.gz> [previous-release-dir]
# Without --yes the script asks for confirmation (and refuses when not run from a terminal).
# =============================================================================
set -euo pipefail
# DB snapshots contain customer PII and password hashes: keep them private to the operator.
umask 077

ASSUME_YES="no"
if [[ "${1:-}" == "--yes" ]]; then
  ASSUME_YES="yes"
  shift
fi

DB_SNAPSHOT="${1:-}"
PREV_FILES_DIR="${2:-}"
WEB_ROOT="${WEB_ROOT:-/var/www/PROD_DOMAIN/wordpress}"
WEB_ROOT="${WEB_ROOT%/}"
BACKUP_DIR="${BACKUP_DIR:-$(dirname "${WEB_ROOT}")/backups}"

if [[ -z "${DB_SNAPSHOT}" || ! -f "${DB_SNAPSHOT}" ]]; then
  echo "ERROR: Valid database snapshot (.sql.gz) is required."
  echo "Usage: ./deploy/rollback.sh [--yes] <path-to-pre-deploy.sql.gz> [path-to-previous-wordpress-files]"
  exit 1
fi

if [[ "${WEB_ROOT}" == *PROD_DOMAIN* || ! -f "${WEB_ROOT}/wp-config.php" ]]; then
  echo "ERROR: WEB_ROOT='${WEB_ROOT}' is a placeholder or not a WordPress root (no wp-config.php)."
  echo "Set WEB_ROOT to the real production web root, e.g. WEB_ROOT=/var/www/example.vn/wordpress"
  exit 1
fi

if [[ -n "${PREV_FILES_DIR}" && ! -d "${PREV_FILES_DIR}" ]]; then
  echo "ERROR: Previous release directory '${PREV_FILES_DIR}' does not exist."
  exit 1
fi

if ! command -v wp >/dev/null 2>&1; then
  echo "ERROR: WP-CLI ('wp') not found on PATH."
  exit 1
fi

echo "================================================================="
echo "JN EMERGENCY ROLLBACK"
echo "  Target Web Root : ${WEB_ROOT}"
echo "  DB Snapshot     : ${DB_SNAPSHOT}"
echo "  Previous Files  : ${PREV_FILES_DIR:-<skipping file restore>}"
echo "  Safety Snapshot : ${BACKUP_DIR}/pre-rollback-<timestamp>.sql.gz"
echo "================================================================="

if [[ "${ASSUME_YES}" != "yes" ]]; then
  if [[ ! -t 0 ]]; then
    echo "ERROR: Refusing to roll back without confirmation. Re-run interactively or pass --yes."
    exit 1
  fi
  read -r -p "This REPLACES the production database. Type 'rollback' to continue: " ANSWER
  if [[ "${ANSWER}" != "rollback" ]]; then
    echo "Aborted."
    exit 1
  fi
fi

# 1. Safety snapshot of the current database (data written since the deploy).
mkdir -p "${BACKUP_DIR}"
SAFETY_SNAPSHOT="${BACKUP_DIR}/pre-rollback-$(date -u +"%Y%m%d-%H%M%S").sql.gz"
echo "[1/4] Exporting current database to ${SAFETY_SNAPSHOT}..."
wp db export - --add-drop-table --path="${WEB_ROOT}" | gzip -9 > "${SAFETY_SNAPSHOT}"

# 2. Restore the pre-deploy database (before touching files, so a failed import leaves files intact).
echo "[2/4] Importing pre-deploy database snapshot ${DB_SNAPSHOT}..."
gzip -dc "${DB_SNAPSHOT}" | wp db import - --path="${WEB_ROOT}"

# 3. Restore previous release files. The leading slash anchors the exclude to the web root, so
#    nested code directories that happen to be named `media` are restored too.
if [[ -n "${PREV_FILES_DIR}" ]]; then
  echo "[3/4] Restoring previous files from ${PREV_FILES_DIR} to ${WEB_ROOT}..."
  rsync -a --delete --exclude='/media/' --exclude='/wp-config.php' --exclude='/.htaccess' \
    --exclude='/app/cache/' --exclude='/app/upgrade/' \
    "${PREV_FILES_DIR%/}/" "${WEB_ROOT}/"
else
  echo "[3/4] No previous files directory specified; keeping current files."
fi

# 4. Flush object cache, rewrite rules and WP Super Cache static files.
echo "[4/4] Flushing caches..."
wp cache flush --path="${WEB_ROOT}" || true
wp rewrite flush --hard --path="${WEB_ROOT}" || true
rm -rf "${WEB_ROOT}/app/cache/supercache/"* "${WEB_ROOT}/app/cache/blogs/"* 2>/dev/null || true

echo "================================================================="
echo "ROLLBACK COMPLETE. Verify site health immediately."
echo "Data written since the deploy is preserved in: ${SAFETY_SNAPSHOT}"
echo "================================================================="
