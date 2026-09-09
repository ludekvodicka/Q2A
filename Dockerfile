FROM php:8.3-apache-bookworm@sha256:fa8852a2e01747ffe8c8768bfd6bbc2f296f974aa0de90ee157a66664996d263

RUN apt-get update \
 && apt-get upgrade -y \
 && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libonig-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd mysqli mbstring zip \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/q2a.ini
COPY docker/apache.conf /etc/apache2/conf-enabled/q2a.conf
COPY . /var/www/html/
COPY docker/qa-config.php /var/www/html/qa-config.php
COPY .htaccess-example /var/www/html/.htaccess

RUN install -d -o www-data -g www-data /var/lib/q2a/cache /var/www/html/qa-uploads /var/www/html/upfiles \
 && rm -rf /var/www/html/docker /var/www/html/docs /var/www/html/qa-external-example

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s \
  CMD curl --fail --silent http://127.0.0.1/ >/dev/null || exit 1
