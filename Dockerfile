FROM php:8.2-apache

# Install MySQLi extension and other dependencies
RUN docker-php-ext-install mysqli

# Enable mysqli extension
RUN docker-php-ext-enable mysqli

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

# Display PHP info (optional - remove after testing)
RUN echo "<?php phpinfo(); ?>" > /var/www/html/public/phpinfo.php
