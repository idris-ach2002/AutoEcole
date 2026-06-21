FROM composer:2 AS composer_binary

FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libpq-dev libzip-dev zlib1g-dev libonig-dev \
    && docker-php-ext-install intl pdo_pgsql zip opcache \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer_binary /usr/bin/composer /usr/bin/composer
COPY docker/apache/autoecole.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/autoecole-entrypoint

WORKDIR /var/www/html
COPY . .

RUN chmod +x /usr/local/bin/autoecole-entrypoint \
    && composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data var public

EXPOSE 80
ENTRYPOINT ["autoecole-entrypoint"]
CMD ["apache2-foreground"]
