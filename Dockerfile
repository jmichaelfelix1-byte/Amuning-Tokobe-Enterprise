FROM php:8.2-apache

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    mysqli \
    zip \
    pdo \
    pdo_mysql

# Enable extensions
RUN docker-php-ext-enable mysqli zip

# Disable phar readonly (allows Phar/Zip creation)
RUN echo "phar.readonly = 0" >> /usr/local/etc/php/conf.d/phar.ini

# Copy project files into container
COPY . /var/www/html/

# Set the document root to the public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Update Apache configuration to use the public folder
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enable error logging
RUN echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini

# Increase memory limit and execution time for zip operations
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini
RUN echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini
