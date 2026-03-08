FROM php:8.2-fpm

# installer dépendances système
RUN apt-get update \
    && apt-get install -y git unzip libzip-dev libpng-dev libicu-dev

# extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    gd \
    intl

# installer redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# installer composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-scripts

CMD ["php-fpm"]