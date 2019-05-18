FROM composer as composer

COPY ./src /tmp/src
RUN \
rm /tmp/src/.env

RUN \
cd /tmp/src && \
composer install --no-dev --no-progress --optimize-autoloader


FROM php:7.2-apache

# Install and enabled plugins
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Change the document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer /tmp/src /var/www/html

RUN chown www-data:www-data /var/www/html
