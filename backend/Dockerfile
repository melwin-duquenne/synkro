# ─── Stage: dev (serveur PHP intégré, hot reload via volume) ─────────────────
FROM php:8.3-cli AS dev

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

CMD composer install \
    && php bin/console doctrine:migrations:migrate --no-interaction \
    && php -S 0.0.0.0:8000 -t public

# ─── Stage: prod (PHP-FPM + opcache) ────────────────────────────────────────
FROM php:8.3-fpm-alpine AS prod

WORKDIR /var/www

RUN apk add --no-cache icu-dev libpq-dev libzip-dev zip git unzip \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

# Opcache optimisé pour la production
RUN echo 'opcache.enable=1' > /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo 'opcache.memory_consumption=256' >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo 'opcache.interned_strings_buffer=16' >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo 'opcache.max_accelerated_files=20000' >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo 'opcache.validate_timestamps=0' >> /usr/local/etc/php/conf.d/opcache-prod.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer les dépendances d'abord (cache Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le code source
COPY . .

# Finaliser l'autoloader et warmup du cache
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php bin/console cache:warmup --env=prod

# Permissions correctes
RUN chown -R www-data:www-data var/

EXPOSE 9000
CMD ["php-fpm"]
