FROM php:8.2-apache

# Installera nödvändiga extensions för scraping och MySQL
RUN apt-get update && apt-get install -y \
      libxml2-dev libzip-dev curl git unzip \
    && docker-php-ext-install \
      pdo_mysql mysqli dom \
    && a2enmod rewrite

# (valfritt) timezone och php.ini
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Låt Apache serva /var/www/html
WORKDIR /var/www/html