#!/usr/bin/env bash
# =============================================================================
# JN Step 11b — Code-review fixes: deploy & operations safety (H1, H2, M6, M7, M16, M17).
#
# Run from the repository root (local Docker stack running):
#   bash scripts/verify-step-11b-deploy.sh
#
# Every behavioural check runs against throw-away directories and stubbed `wp` binaries,
# so no real database or web root is touched.
# =============================================================================
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/jn11b.XXXXXX")"
trap 'rm -rf "${WORK}"' EXIT
PASSED=0
FAILED=0

check() {
  local label="$1"
  shift
  if "$@" >/dev/null 2>&1; then
    PASSED=$((PASSED + 1))
    echo "[PASS] ${label}"
  else
    FAILED=$((FAILED + 1))
    echo "[FAIL] ${label}"
  fi
}

skip() {
  echo "[SKIP] $1"
}

section() {
  echo
  echo "--- $1 ---"
}

cd "${ROOT}" || exit 1

# -----------------------------------------------------------------------------
section "H1 — rsync deploy never deletes or overwrites production-only files"
SRC="${WORK}/rsync-src"
DST="${WORK}/rsync-dst"
mkdir -p "${SRC}/app/themes/jannie" "${SRC}/media" "${DST}/media/2026/10" "${DST}/app/cache/supercache/site" "${DST}/app/upgrade"
echo "dev"  > "${SRC}/wp-config.php"
echo "dev"  > "${SRC}/.htaccess"
echo "new"  > "${SRC}/app/themes/jannie/style.css"
echo "dev"  > "${SRC}/media/dev-only.jpg"
echo "PROD" > "${DST}/wp-config.php"
echo "PROD" > "${DST}/.htaccess"
echo "PROD" > "${DST}/media/2026/10/customer-upload.jpg"
echo "PROD" > "${DST}/app/cache/supercache/site/index.html"
echo "PROD" > "${DST}/app/upgrade/lock"
rsync -a --delete --exclude-from=deploy/rsync-exclude.txt "${SRC}/" "${DST}/" >/dev/null 2>&1

check "production media uploads survive --delete" test -f "${DST}/media/2026/10/customer-upload.jpg"
check "dev media is not pushed by the code deploy" test ! -e "${DST}/media/dev-only.jpg"
check "production wp-config.php is not overwritten" grep -qx PROD "${DST}/wp-config.php"
check "production .htaccess is not overwritten" grep -qx PROD "${DST}/.htaccess"
check "page cache directory is left alone" test -f "${DST}/app/cache/supercache/site/index.html"
check "code changes are still deployed" grep -qx new "${DST}/app/themes/jannie/style.css"
check "runbook requires a --dry-run before the real rsync" grep -q -- "rsync -avz --delete --dry-run" docs/jn/deploy/runbook-trien-khai-va-rollback.md

# -----------------------------------------------------------------------------
section "H2 — destructive scripts refuse to run against production"
DESTRUCTIVE=(
  check-launch-blockers.php
  step-4-e2e-helper.php
  step-5-e2e-helper.php
  step-6-e2e-helper.php
  test-step-5-booking.php
  test-step-6-customers.php
  test-step-7-hardening.php
  test-step-8-performance.php
  provision-step-2.php
  setup-step-4-woocommerce.php
  build-step-3.php
  build-step-8.php
  import-step-3-media.php
  trigger-step-9-all-emails.php
)
for script in "${DESTRUCTIVE[@]}"; do
  check "${script} calls jn_require_non_production()" grep -q "jn_require_non_production(" "scripts/${script}"
done

GUARD_OUT="$(docker compose run --rm -e WP_ENVIRONMENT_TYPE=production wpcli wp eval 'require "/scripts/lib/jn-env-guard.php"; jn_require_non_production( "probe" ); echo "RAN-ANYWAY";' 2>&1)"
GUARD_RC=$?
check "guard exits non-zero under WP_ENVIRONMENT_TYPE=production" test "${GUARD_RC}" -ne 0
check "guard stops execution before the script body" bash -c "! grep -q RAN-ANYWAY <<<\"\$1\"" _ "${GUARD_OUT}"

# -----------------------------------------------------------------------------
section "M16 — production wp-config loads .env.production and fails closed"
CFG="${WORK}/cfg"
mkdir -p "${CFG}/site"
cp deploy/wp-config.production.php "${CFG}/site/wp-config.php"
cat > "${CFG}/site/wp-settings.php" <<'PHP'
<?php
echo 'BOOT OK db=' . DB_NAME . ' home=' . WP_HOME . ' key=' . ( '' !== AUTH_KEY ? 'set' : 'empty' ) . "\n";
PHP
cat > "${CFG}/.env.production" <<'ENV'
# sample
DB_NAME=jn_prod
DB_USER=jn_user
DB_PASSWORD='p@ss#word=with$chars'
export DB_HOST=localhost
WP_HOME="https://example.test"
WP_SITEURL="https://example.test"
AUTH_KEY='a+b/c='
SECURE_AUTH_KEY='k2'
LOGGED_IN_KEY='k3'
NONCE_KEY='k4'
AUTH_SALT='k5'
SECURE_AUTH_SALT='k6'
LOGGED_IN_SALT='k7'
NONCE_SALT='k8'
WP_LOGIN_SLUG=sample-login
ENV

OUT_OK="$(docker run --rm -v "${CFG}:/srv" -w /srv/site php:8.3-cli php wp-config.php 2>&1)"
check "config boots from ../.env.production next to the web root" grep -q "BOOT OK db=jn_prod home=https://example.test key=set" <<<"${OUT_OK}"

OUT_ENV="$(docker run --rm -v "${CFG}:/srv" -w /srv/site -e JN_ENV_FILE=/nonexistent php:8.3-cli php wp-config.php 2>&1)"
RC_ENV=$?
check "config exits non-zero when required variables are missing" test "${RC_ENV}" -ne 0
check "config names the missing variables instead of booting" bash -c "grep -q DB_NAME <<<\"\$1\" && ! grep -q 'BOOT OK' <<<\"\$1\"" _ "${OUT_ENV}"

OUT_PH="$(docker run --rm -v "${CFG}:/srv" -w /srv/site -e AUTH_KEY=REPLACE_WITH_FRESH_SALT_FROM_GENERATE_SALTS_SH php:8.3-cli php wp-config.php 2>&1)"
check "config rejects the REPLACE_WITH_ placeholders copied from .env.production.example" bash -c "grep -q AUTH_KEY <<<\"\$1\" && ! grep -q 'BOOT OK' <<<\"\$1\"" _ "${OUT_PH}"

OUT_PRE="$(docker run --rm -v "${CFG}:/srv" -w /srv/site -e DB_NAME=from_fpm php:8.3-cli php wp-config.php 2>&1)"
check "variables already provided by the process environment win over the file" grep -q "db=from_fpm" <<<"${OUT_PRE}"

check "cron logs failures instead of discarding them" bash -c "! grep -q '>/dev/null 2>&1' deploy/cron.d-jannie-wordpress"

# -----------------------------------------------------------------------------
section "M16/M17 — db-search-replace.sh refuses --apply against the local Docker DB"
SR="${WORK}/sr"
mkdir -p "${SR}/deploy" "${SR}/scripts"
cp deploy/db-search-replace.sh "${SR}/deploy/"
cat > "${SR}/scripts/wp.sh" <<'SH'
#!/usr/bin/env bash
echo "$*" >> "$(dirname "$0")/../wp-calls.log"
SH
chmod +x "${SR}/scripts/wp.sh" "${SR}/deploy/db-search-replace.sh"
( cd "${SR}" && ./deploy/db-search-replace.sh example.test --apply ) >/dev/null 2>&1
SR_RC=$?
check "--apply via the local Docker wrapper exits non-zero" test "${SR_RC}" -ne 0
check "--apply via the local Docker wrapper never runs a live search-replace" bash -c "! grep -E '^search-replace' \"\$1\" 2>/dev/null | grep -vq -- '--dry-run'" _ "${SR}/wp-calls.log"
check "snapshot files are created with a private umask" grep -q "umask 077" deploy/db-search-replace.sh

# -----------------------------------------------------------------------------
section "M17 — rollback.sh is confirmed, snapshots first and keeps /media/"
RB="${WORK}/rb"
mkdir -p "${RB}/bin" "${RB}/web/media" "${RB}/web/app/modules/x/media" "${RB}/prev/app/modules/x/media"
cat > "${RB}/bin/wp" <<'SH'
#!/usr/bin/env bash
echo "$*" >> "${RB_LOG}"
if [[ "$1 $2" == "db export" ]]; then echo "-- dump"; fi
SH
chmod +x "${RB}/bin/wp"
echo "PROD" > "${RB}/web/wp-config.php"
echo "PROD" > "${RB}/web/media/upload.jpg"
echo "new"  > "${RB}/web/app/modules/x/media/new.js"
echo "old"  > "${RB}/prev/app/modules/x/media/old.js"
printf 'select 1;\n' | gzip > "${RB}/pre-deploy.sql.gz"

( PATH="${RB}/bin:${PATH}" RB_LOG="${RB}/calls.log" WEB_ROOT="${RB}/web" BACKUP_DIR="${RB}" \
  ./deploy/rollback.sh "${RB}/pre-deploy.sql.gz" "${RB}/prev" </dev/null ) >/dev/null 2>&1
check "rollback refuses to run without confirmation (non-interactive, no --yes)" test ! -s "${RB}/calls.log"

( PATH="${RB}/bin:${PATH}" RB_LOG="${RB}/calls.log" WEB_ROOT="/var/www/PROD_DOMAIN/wordpress" \
  ./deploy/rollback.sh --yes "${RB}/pre-deploy.sql.gz" "${RB}/prev" </dev/null ) >/dev/null 2>&1
check "rollback refuses the placeholder WEB_ROOT" test ! -s "${RB}/calls.log"

( PATH="${RB}/bin:${PATH}" RB_LOG="${RB}/calls.log" WEB_ROOT="${RB}/web" BACKUP_DIR="${RB}" \
  ./deploy/rollback.sh --yes "${RB}/pre-deploy.sql.gz" "${RB}/prev" </dev/null ) >/dev/null 2>&1
check "rollback exports a snapshot of the current DB before importing" bash -c "grep -n '' \"\$1\" | grep -m1 -E 'db (export|import)' | grep -q 'db export'" _ "${RB}/calls.log"
check "rollback imports the pre-deploy snapshot" grep -q "db import" "${RB}/calls.log"
check "rollback keeps production /media/ uploads" test -f "${RB}/web/media/upload.jpg"
check "rollback keeps the production wp-config.php" grep -qx PROD "${RB}/web/wp-config.php"
check "rollback restores nested */media/ code directories from the previous release" bash -c "test -f \"\$1/web/app/modules/x/media/old.js\" && test ! -e \"\$1/web/app/modules/x/media/new.js\"" _ "${RB}"
check "rollback.sh and runbook use the anchored --exclude='/media/'" bash -c "grep -q \"exclude='/media/'\" deploy/rollback.sh && ! grep -q \"exclude='media/'\" docs/jn/deploy/runbook-trien-khai-va-rollback.md"

# -----------------------------------------------------------------------------
section "M7 — local Docker services listen on loopback only"
check "every published port in docker-compose.yml is bound to 127.0.0.1" bash -c "! grep -E '^\s+- \"[0-9]+:[0-9]+\"' docker-compose.yml && grep -qE '127\.0\.0\.1:8080:80' docker-compose.yml"

# -----------------------------------------------------------------------------
section "M6 — nginx static assets keep the security headers"
check "production nginx includes the shared security-header snippet in the static location" bash -c "awk '/location ~\\* \\\\.\\(\\?:css/,/}/' deploy/nginx-production.conf | grep -q 'include .*jn-security-headers.conf'"
if docker image inspect nginx:1.27-alpine >/dev/null 2>&1 || docker pull -q nginx:1.27-alpine >/dev/null 2>&1; then
  NG="${WORK}/nginx"
  mkdir -p "${NG}/conf.d" "${NG}/snippets" "${NG}/certs" "${NG}/root"
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 -subj "/CN=example.test" \
    -keyout "${NG}/certs/privkey.pem" -out "${NG}/certs/fullchain.pem" >/dev/null 2>&1
  sed -e 's/PROD_DOMAIN/example.test/g' \
      -e 's#/etc/letsencrypt/live/example.test#/etc/nginx/certs#g' \
      -e 's#/var/www/example.test/wordpress#/srv/root#g' \
      -e 's#fastcgi_pass unix:/run/php/php8.3-fpm.sock;#fastcgi_pass 127.0.0.1:9000;#' \
      deploy/nginx-production.conf > "${NG}/conf.d/site.conf"
  cp deploy/nginx-security-headers.conf "${NG}/snippets/jn-security-headers.conf"
  printf 'fastcgi_split_path_info ^(.+\\.php)(/.+)$;\ninclude fastcgi_params;\n' > "${NG}/snippets/fastcgi-php.conf"
  echo "body{}" > "${NG}/root/style.css"
  mkdir -p "${NG}/root/media/jn-ics-tmp"
  echo "BEGIN:VCALENDAR" > "${NG}/root/media/jn-ics-tmp/lich-hen-jn-202610-0001.ics"
  NG_ID="$(docker run -d --rm -v "${NG}/conf.d:/etc/nginx/conf.d:ro" -v "${NG}/snippets:/etc/nginx/snippets:ro" \
    -v "${NG}/certs:/etc/nginx/certs:ro" -v "${NG}/root:/srv/root:ro" nginx:1.27-alpine 2>/dev/null)"
  sleep 2
  check "nginx -t accepts the production config" docker exec "${NG_ID}" nginx -t
  HEADERS="$(docker exec "${NG_ID}" wget -S -q -O /dev/null --no-check-certificate --header 'Host: example.test' https://127.0.0.1/style.css 2>&1)"
  check "static .css response carries X-Frame-Options" grep -qi "X-Frame-Options" <<<"${HEADERS}"
  check "static .css response carries Referrer-Policy" grep -qi "Referrer-Policy" <<<"${HEADERS}"
  check "static .css response keeps the immutable Cache-Control" grep -qi "immutable" <<<"${HEADERS}"
  ICS_STATUS="$(docker exec "${NG_ID}" wget -S -q -O /dev/null --no-check-certificate --header 'Host: example.test' https://127.0.0.1/media/jn-ics-tmp/lich-hen-jn-202610-0001.ics 2>&1 | grep -m1 'HTTP/' )"
  check "legacy /media/jn-ics-tmp/*.ics files are never served (H3)" grep -q " 404" <<<"${ICS_STATUS}"
  docker stop "${NG_ID}" >/dev/null 2>&1
else
  skip "nginx image unavailable — runtime header checks skipped"
fi

# -----------------------------------------------------------------------------
section "M8 — login slug and admin username are not committed"
env_value() { grep -E "^$1=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d "\"'"; }
REAL_SLUG="$(env_value WP_LOGIN_SLUG)"
REAL_ADMIN="$(env_value WP_ADMIN_USER)"
if [[ -n "${REAL_SLUG}" && -n "${REAL_ADMIN}" ]]; then
  check "no tracked file contains the real login slug" bash -c "! git grep -q -F -- \"\$1\"" _ "${REAL_SLUG}"
  check "no tracked file contains the real admin username" bash -c "! git grep -q -F -- \"\$1\"" _ "${REAL_ADMIN}"
  SLUG_OUT="$(docker compose run --rm -e WP_LOGIN_SLUG=jn11-probe-login wpcli wp eval 'echo get_option( "whl_page" );' 2>/dev/null | tail -1)"
  check "WPS Hide Login slug comes from the WP_LOGIN_SLUG environment variable" test "${SLUG_OUT}" = "jn11-probe-login"
  rm -rf "wordpress/app/cache/supercache/localhost/${REAL_SLUG}" 2>/dev/null
  curl -s -o /dev/null "http://localhost:8080/${REAL_SLUG}/"
  curl -s -o /dev/null "http://localhost:8080/${REAL_SLUG}/"
  check "the login screen is never written to the page cache" test ! -e "wordpress/app/cache/supercache/localhost/${REAL_SLUG}"
  check "check-launch-blockers reads the admin login from WP_ADMIN_USER and aborts when it is missing" bash -c "grep -q \"getenv( 'WP_ADMIN_USER' )\" scripts/check-launch-blockers.php"
else
  skip "WP_LOGIN_SLUG / WP_ADMIN_USER not set in .env — M8 checks skipped"
fi

# -----------------------------------------------------------------------------
section "Shell syntax"
for sh in deploy/*.sh scripts/*.sh; do
  check "bash -n ${sh}" bash -n "${sh}"
done

echo
echo "RESULT: ${PASSED} passed, ${FAILED} failed"
[[ "${FAILED}" -eq 0 ]]
