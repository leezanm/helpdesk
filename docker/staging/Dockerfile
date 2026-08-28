# Staging app image for the Laravel upgrade work.
# Runs PHP 8.3 (an upgrade target) with the extensions this app needs:
# pdo_mysql (database), ldap (adldap2 / future LdapRecord), gd (image handling), zip (backups).
FROM php:8.3-cli

# System deps for the PHP extensions above.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libldap2-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && PHP_LDAP_LIBDIR="$(dirname "$(find / -name 'libldap*.so*' 2>/dev/null | head -1)")" \
    && docker-php-ext-configure ldap --with-libdir="$(echo "$PHP_LDAP_LIBDIR" | sed 's#^/usr/##')" \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql ldap gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official composer image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# The project is bind-mounted at runtime (see docker-compose.staging.yml),
# so we do not COPY the source here. This keeps rebuilds fast during the upgrade.

EXPOSE 8000

# Serve the app via PHP's built-in server for staging validation.
# PHP_CLI_SERVER_WORKERS lets the dev server handle concurrent/sub requests
# (e.g. session-based login flows) without the single-worker deadlock.
ENV PHP_CLI_SERVER_WORKERS=4
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
