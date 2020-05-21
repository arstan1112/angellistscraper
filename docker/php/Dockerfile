FROM php:7.2-fpm

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install apcu

RUN apt-get update

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

WORKDIR /var/www/angel