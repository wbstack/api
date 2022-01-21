FROM composer@sha256:d374b2e1f715621e9d9929575d6b35b11cf4a6dc237d4a08f2e6d1611f534675 as composer
# composer is pinned at a PHP 7 version

COPY ./composer.json /tmp/src1/composer.json
COPY ./composer.lock /tmp/src1/composer.lock

WORKDIR /tmp/src1
RUN composer install --no-dev --no-progress --no-autoloader

COPY ./ /tmp/src2
RUN cp -r /tmp/src1/vendor /tmp/src2/vendor
WORKDIR /tmp/src2
RUN composer install --no-dev --no-progress --optimize-autoloader


FROM php:7.4-apache

RUN apt-get update \
	# Needed for the imagick php extension install
	&& apt-get install -y --no-install-recommends libmagickwand-dev \
	&& echo "" | pecl install imagick \
	&& docker-php-ext-enable imagick \
	# Obviously needed for mysql connection
	&& docker-php-ext-install pdo pdo_mysql \
	# For rewrite rules
	&& a2enmod rewrite \
	# Needed for gluedev/laravel-stackdriver
	&& pecl install opencensus-alpha \
	&& docker-php-ext-enable opencensus \
	&& rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN echo "zend_extension=xdebug\n\n\
[xdebug]\n\
xdebug.mode=develop,debug\n\
xdebug.client_host=host.docker.internal\n\
xdebug.client_port=9003\n\
" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Change the document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --chown=www-data:www-data --from=composer /tmp/src2 /var/www/html

WORKDIR /var/www/html

COPY ./start.sh /usr/local/bin/start

CMD ["/usr/local/bin/start"]

LABEL org.opencontainers.image.source="https://github.com/wbstack/api"
