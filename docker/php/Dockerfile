FROM php:7.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install apcu

RUN apt-get update

RUN apt-get install -y libzip-dev zip

RUN docker-php-ext-install bcmath

RUN docker-php-ext-configure zip --with-libzip

RUN docker-php-ext-install zip

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

WORKDIR /var/www/angel
