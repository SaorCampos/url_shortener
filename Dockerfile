FROM php:8.4-fpm

ARG user=saor
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# PHP Extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    sockets

# Redis (PECL)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# User
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
 && mkdir -p /home/$user/.composer \
 && chown -R $user:$user /home/$user

WORKDIR /var/www

# Install dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy project
COPY . .

# Config PHP
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/

USER $user
