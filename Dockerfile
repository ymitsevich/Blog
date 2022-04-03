FROM php:8.1-fpm-alpine

RUN apk add -U --no-cache git bzip2-dev libxml2-dev libpng-dev g++ icu-dev procps
RUN apk add -U --no-cache openssh-client autoconf gcc make libc-dev libzip-dev
RUN pecl install zip
RUN docker-php-ext-install bcmath soap gd intl calendar bz2 pdo pdo_mysql
RUN docker-php-ext-enable  calendar pdo_mysql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# xdebug install
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug