FROM php:8-apache

RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev

RUN apt-get install -y \
        libxml2-dev

RUN /usr/local/bin/docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get install libssl-dev -y

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

COPY . /var/www/
RUN chown -R www-data:www-data /var/www

COPY docker/start-apache /usr/local/bin

ENV APP_PATH=/var/www

RUN composer install

CMD ["start-apache"]