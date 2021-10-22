FROM php:7.4-apache

WORKDIR /var/www/manager

# Install dependencies
RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install -y git unzip libzip-dev libpq-dev

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) zip pdo_pgsql

# Apache configuration
COPY .docker/apache/manager.conf /etc/apache2/sites-available
RUN a2enmod rewrite alias
RUN a2dissite 000-default
RUN a2ensite manager
RUN apache2ctl restart

# Copy application source code
COPY --chown=www-data . .

# Install composer
COPY .docker/composer.sh .
RUN chmod +x ./composer.sh
RUN ./composer.sh
RUN rm ./composer.sh

# Install application dependencies
RUN php composer.phar install --no-progress

EXPOSE 80