# Image de production de WearAgain : PHP 8.4 + Apache, front-end compilé par Vite.
# Construite par la CI (GitHub Actions) et déployée sur Render.

# 1. Dépendances PHP
#    Les outils de développement sont gardés : le seeder de démonstration utilise Faker.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# 2. Front-end : CSS et JavaScript compilés par Vite (Tailwind lit aussi les vues de pagination de Laravel)
FROM node:20-alpine AS front
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# 3. Image finale
FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip intl bcmath pdo_mysql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache sert le dossier public/ de Laravel et lit son .htaccess (réécriture des URL).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Réglages de production par défaut : l'hébergeur n'a qu'à fournir GEMINI_API_KEY (chacun reste modifiable).
ENV APP_NAME=WearAgain \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_LOCALE=fr \
    APP_FALLBACK_LOCALE=fr \
    APP_FAKER_LOCALE=fr_FR \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    LOG_CHANNEL=stderr \
    COLLECTE_IA_FOURNISSEUR=gemini \
    GEMINI_MODEL=gemini-2.5-flash \
    GROQ_MODEL=llama-3.3-70b-versatile

COPY docker/php.ini /usr/local/etc/php/conf.d/wearagain.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=front /app/public/build ./public/build

RUN chmod +x /usr/local/bin/entrypoint \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && mkdir -p public/uploads/transformations \
    && chown -R www-data:www-data storage bootstrap/cache database public/uploads

EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
