FROM php:8-apache

RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev

RUN apt-get install -y \
        libxml2-dev

RUN /usr/local/bin/docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get install libssl-dev -y

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd

RUN apt-get update && apt-get install -y \
		libfreetype-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

COPY . /var/www/
RUN chown -R www-data:www-data /var/www

COPY docker/start-apache /usr/local/bin

ENV APP_PATH=/var/www

CMD ["start-apache"]