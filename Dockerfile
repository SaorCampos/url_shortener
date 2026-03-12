FROM php:8.4-fpm

# set your user name, ex: user=joe
ARG user=saor
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql
# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
 && mkdir -p /home/$user/.composer \
 && chown -R $user:$user /home/$user

WORKDIR /var/www

# Cache-friendly: deps before code
COPY composer.json composer.lock ./
USER root
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-plugins

# Copy the project and custom.ini
COPY . .
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/

USER $user
