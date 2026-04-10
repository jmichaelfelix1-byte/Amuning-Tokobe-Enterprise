FROM php:8.2-apache

# Copy project files into container
COPY . /var/www/html/

# Enable Apache rewrite (important for many PHP apps)
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html