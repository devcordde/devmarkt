FROM php:8-apache

# Install system dependencies
RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev \
        libxml2-dev \
        libssl-dev \
        libfreetype-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        unzip \
        git

# Install PHP extensions
RUN /usr/local/bin/docker-php-ext-install mysqli pdo pdo_mysql

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache configuration
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Application setup
COPY . /var/www/
WORKDIR /var/www

# Install dependencies (including dev for debug mode), then delete Composer files
RUN composer install --no-interaction
RUN rm composer.*

RUN chown -R www-data:www-data /var/www

COPY docker/start-apache /usr/local/bin

ENV APP_PATH=/var/www
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

CMD ["start-apache"]
