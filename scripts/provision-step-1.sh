#!/usr/bin/env sh
# =============================================================================
# JN STEP 1 — Automated WP-CLI Provisioning & Hardening Script
# Runs inside the wpcli container (/var/www/html).
# =============================================================================
set -eu

echo "=== [1/7] Installing WordPress 7.1.3 ==="
if ! wp core is-installed; then
	wp core install \
		--url="${WP_HOME}" \
		--title="Jannie Nguyen" \
		--admin_user="${WP_ADMIN_USER}" \
		--admin_password="${WP_ADMIN_PASSWORD}" \
		--admin_email="${WP_ADMIN_EMAIL}" \
		--skip-email
fi

echo "=== [2/7] Installing & Activating Vietnamese (vi) Core Language ==="
wp language core install vi
wp site switch-language vi

echo "=== [3/7] Configuring Timezone, Date/Time Formats, Privacy & Discussion ==="
wp option update blogname "Jannie Nguyen"
wp option update timezone_string "Asia/Ho_Chi_Minh"
wp option update date_format "d/m/Y"
wp option update time_format "H:i"
wp option update start_of_week 1
wp option update blog_public 0
wp option update default_comment_status "closed"
wp option update default_ping_status "closed"
wp option update default_pingback_flag 0

echo "=== [4/7] Deleting Sample Post, Page, Comment & Default Plugins ==="
wp comment delete 1 --force 2>/dev/null || true
wp post delete 1 2 --force 2>/dev/null || true
wp plugin delete hello akismet 2>/dev/null || true

echo "=== [5/7] Setting Up Themes (Blocksy + blocksy-jn active; Twenty Twenty-Five inactive recovery) ==="
if ! wp theme is-installed blocksy; then
	wp theme install blocksy
fi
wp theme activate blocksy-jn
wp theme delete twentytwentythree twentytwentyfour 2>/dev/null || true

echo "=== [6/7] Installing WPS Hide Login & Setting LOGIN_SLUG (${WP_LOGIN_SLUG}) ==="
if ! wp plugin is-installed wps-hide-login; then
	wp plugin install wps-hide-login --activate
else
	wp plugin activate wps-hide-login
fi
wp option update whl_page "${WP_LOGIN_SLUG}"
wp option update whl_redirect_admin "404"

echo "=== [7/7] Configuring Permalinks (/%postname%/) & Writing .htaccess ==="
wp rewrite structure '/%postname%/' --hard
cat > /var/www/html/.htaccess <<'EOF'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF
wp rewrite flush --hard

echo "=== Provisioning Complete ==="
