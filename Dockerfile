FROM php:8.2-apache

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY public/ /var/www/html/

# Make uploads writable
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

# Apache runs on port 80
EXPOSE 80
