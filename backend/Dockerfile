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

RUN apk add --no-cache icu-dev libpq-dev libzip-dev zip git unzip openssl \
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

# Variables d'env minimales pour le cache:warmup (les vraies valeurs sont injectées au runtime)
ENV APP_ENV=prod \
    APP_SECRET=build-placeholder \
    DATABASE_URL=postgresql://placeholder:placeholder@localhost:5432/placeholder \
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem \
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem \
    JWT_PASSPHRASE=placeholder \
    CORS_ALLOW_ORIGIN='^https?://localhost$' \
    MAILER_DSN=null://null \
    APP_FRONTEND_URL=http://localhost \
    GOOGLE_CLIENT_ID=placeholder \
    GOOGLE_CLIENT_SECRET=placeholder \
    GOOGLE_REDIRECT_URI=http://localhost

# Écraser le .env du repo (contient des ${} shell non interprétables par Symfony)
# Les vraies valeurs sont dans les ENV ci-dessus et seront overridées au runtime
RUN rm -f .env
# Créer les répertoires requis
RUN mkdir -p var/share

# Générer des clés JWT placeholder pour le cache:warmup (les vraies clés sont montées au runtime)
RUN mkdir -p config/jwt \
    && openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 -pass pass:placeholder \
    && openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:placeholder

# Finaliser l'autoloader et warmup du cache
RUN composer dump-autoload --optimize --classmap-authoritative
# Permissions correctes
RUN chown -R www-data:www-data var/

EXPOSE 9000
CMD ["sh", "-c", "\
    php bin/console cache:warmup --env=prod && \
    php-fpm \
"]
