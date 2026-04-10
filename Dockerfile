FROM php:8.2-apache

# Copy project files into container
COPY . /var/www/html/

# Set the document root to the public folder (where your PHP files are)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Update Apache configuration to use the public folder
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enable error logging (helpful for debugging)
RUN echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini