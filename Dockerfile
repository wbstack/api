FROM composer:1.10 as composer

COPY ./src/composer.json /tmp/src1/composer.json
COPY ./src/composer.lock /tmp/src1/composer.lock

WORKDIR /tmp/src1
RUN composer install --no-dev --no-progress --no-autoloader

COPY ./src /tmp/src2
RUN cp -r /tmp/src1/vendor /tmp/src2/vendor
WORKDIR /tmp/src2
RUN composer install --no-dev --no-progress --optimize-autoloader

FROM php:7.2-apache

RUN apt-get update && apt-get install -y \
	# Needed for the imagick php extension install
	libmagickwand-dev \
	&& echo "" | pecl install imagick \
	&& docker-php-ext-enable \
	imagick \
	&& docker-php-ext-install \
	# Obviously needed for mysql connection
	pdo pdo_mysql \
	&& a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Change the document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --chown=www-data:www-data --from=composer /tmp/src2 /var/www/html

WORKDIR /var/www/html

COPY ./start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

CMD ["/usr/local/bin/start"]
