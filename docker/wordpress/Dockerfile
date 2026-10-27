FROM wordpress:php8.3-apache

# Enable Apache modules and configure port 8080 alongside port 80 so internal
# WordPress Site Health loopback & REST API checks to http://localhost:8080 succeed.
RUN a2enmod rewrite headers expires \
    && grep -q "Listen 8080" /etc/apache2/ports.conf || echo "Listen 8080" >> /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:80 *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-available/jn-security.conf \
    && a2enconf jn-security \
    && mkdir -p /var/log/wordpress \
    && chown -R www-data:www-data /var/log/wordpress

# Bypass the default wordpress docker-entrypoint.sh so the container never
# overwrites the bind-mounted ./wordpress (7.1.3) or generates wp-config.php.
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]
