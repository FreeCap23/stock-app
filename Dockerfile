# Using PHP 8.3 as Apache base image
FROM php:8.3-apache

# Install development packages
# Composer needs 'git' and 'unzip' to download dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache module rewrite
RUN a2enmod rewrite

# Install PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo_mysql pdo

# Install Composer
# Copy the Composer binary from the official Composer Docker image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
