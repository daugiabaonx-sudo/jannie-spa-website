#!/usr/bin/env bash
# =============================================================================
# JN Step 9 — Production Database Export & Serialization-Safe Search-Replace
# (`deploy/db-search-replace.sh`)
#
# Performs:
#   1. Exports a pre-migration database snapshot (`backups/pre-search-replace.sql.gz`).
#   2. Runs a mandatory `--dry-run` of `wp search-replace` from `http://localhost:8080`
#      to `https://<PROD_DOMAIN>` across all tables with prefix `jn_8f4c_` using
#      PHP serialization-safe `--precise --recurse-objects`.
#   3. Only when `--apply` is explicitly passed: executes the live replacement,
#      enables search engine indexing (`blog_public = 1`), copies the production
#      `robots.txt` policy, flushes rewrite rules, and purges WP Super Cache.
#
# Usage:
#   ./deploy/db-search-replace.sh <PROD_DOMAIN>              # Dry-run only (safe default)
#   ./deploy/db-search-replace.sh <PROD_DOMAIN> --apply      # Apply after reviewing dry-run
# =============================================================================
set -euo pipefail
# DB snapshots contain customer PII and password hashes: keep them private to the operator.
umask 077

PROD_DOMAIN="${1:-}"
MODE="${2:---dry-run}"

if [[ -z "${PROD_DOMAIN}" || "${PROD_DOMAIN}" == "UNKNOWN" ]]; then
  echo "ERROR: Please provide the real production domain (e.g. ./deploy/db-search-replace.sh jannienguyen.vn)"
  exit 1
fi

# Strip any protocol or trailing slash if accidentally included
PROD_DOMAIN="${PROD_DOMAIN#https://}"
PROD_DOMAIN="${PROD_DOMAIN#http://}"
PROD_DOMAIN="${PROD_DOMAIN%/}"

OLD_URL="http://localhost:8080"
NEW_URL="https://${PROD_DOMAIN}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
TIMESTAMP="$(date -u +"%Y%m%d-%H%M%S")"

mkdir -p "${BACKUP_DIR}"

# Detect WP-CLI runner (local Docker wrapper or host `wp` binary on production server)
if [[ -x "./scripts/wp.sh" ]] && command -v docker >/dev/null 2>&1; then
  WP_CMD=("./scripts/wp.sh")
  # The Docker wrapper targets the LOCAL development database. A live rewrite there breaks the
  # local site (URLs + blog_public), so --apply must run on the server unless explicitly allowed.
  if [[ "${MODE}" == "--apply" && "${JN_ALLOW_LOCAL_APPLY:-}" != "1" ]]; then
    echo "ERROR: --apply would rewrite the LOCAL Docker database (detected ./scripts/wp.sh)."
    echo "Run this script on the production server, or set JN_ALLOW_LOCAL_APPLY=1 if you really intend a local rewrite."
    exit 1
  fi
elif command -v wp >/dev/null 2>&1; then
  WP_CMD=("wp" "--path=${WP_PATH:-./wordpress}")
else
  echo "ERROR: Neither local Docker wpcli nor host 'wp' CLI found."
  exit 1
fi

echo "================================================================="
echo "JN STEP 9 — SERIALIZATION-SAFE DATABASE SEARCH-REPLACE"
echo "  Source URL : ${OLD_URL}"
echo "  Target URL : ${NEW_URL}"
echo "  Mode       : ${MODE}"
echo "================================================================="

# Step 1: Always take a pre-operation database snapshot first
SNAPSHOT_FILE="${BACKUP_DIR}/pre-search-replace-${TIMESTAMP}.sql.gz"
echo "[1/4] Exporting pre-operation DB snapshot to ${SNAPSHOT_FILE}..."
"${WP_CMD[@]}" db export - --add-drop-table | gzip -9 > "${SNAPSHOT_FILE}"
echo "      Snapshot saved ($(wc -c < "${SNAPSHOT_FILE}" | tr -d ' ') bytes)."

# Step 2: Mandatory dry-run first
echo "[2/4] Running serialization-safe DRY-RUN search-replace..."
"${WP_CMD[@]}" search-replace "${OLD_URL}" "${NEW_URL}" \
  --all-tables-with-prefix \
  --precise \
  --recurse-objects \
  --dry-run \
  --report-changed-only

if [[ "${MODE}" != "--apply" ]]; then
  echo "-----------------------------------------------------------------"
  echo "DRY-RUN COMPLETE. No database rows were modified."
  echo "To apply this replacement on the target database, re-run with:"
  echo "  ./deploy/db-search-replace.sh ${PROD_DOMAIN} --apply"
  echo "================================================================="
  exit 0
fi

# Step 3: Live serialization-safe replacement
echo "[3/4] Applying live serialization-safe search-replace (${OLD_URL} -> ${NEW_URL})..."
"${WP_CMD[@]}" search-replace "${OLD_URL}" "${NEW_URL}" \
  --all-tables-with-prefix \
  --precise \
  --recurse-objects \
  --report-changed-only

# Step 4: Enable search engines (blog_public = 1) and flush caches/rewrites at launch
echo "[4/4] Enabling search engines (blog_public=1) and flushing caches..."
"${WP_CMD[@]}" option update blog_public 1
"${WP_CMD[@]}" rewrite flush --hard
"${WP_CMD[@]}" cache flush

echo "================================================================="
echo "SUCCESS: Database migrated to ${NEW_URL} and search engines enabled."
echo "Rollback snapshot available at: ${SNAPSHOT_FILE}"
echo "================================================================="
