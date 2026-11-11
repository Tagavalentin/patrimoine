# Dockerfile commun à tous les micro-services Laravel.
# Build : docker build -f docker/php/Dockerfile --build-arg SERVICE=asset-service .
# Le code source de chaque service est monté en volume à l'exécution
# (voir docker-compose.yml) — l'image ne contient que l'environnement PHP.

FROM php:8.3-fpm-alpine

# Dépendances système pour les extensions PHP et Composer
RUN apk add --no-cache \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        intl \
        bcmath \
        zip \
        opcache \
    && apk del $PHPIZE_DEPS

# OPcache : performances en environnement local/docker
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.revalidate_freq=0'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Composer (gestionnaire de dépendances PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Répertoire de travail des applications Laravel
WORKDIR /var/www/html

# Utilisateur non-root pour php-fpm
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www \
    && chown -R www:www /var/www/html
USER www

EXPOSE 9000

CMD ["php-fpm"]
