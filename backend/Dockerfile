FROM php:8.3-fpm

WORKDIR /var/www

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libpq-dev libzip-dev libxml2-dev zip \
    && docker-php-ext-install pdo pdo_pgsql intl xml zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

# Installer les dépendances Composer pendant la construction
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-ansi \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# Vérifier que vendor/ est bien présent
RUN test -d vendor || (echo "ERROR: vendor/ not found!" && exit 1)

# Créer les répertoires nécessaires à l'exécution Symfony
RUN mkdir -p var/log var/cache && chmod -R 777 var/

CMD ["php-fpm"]
