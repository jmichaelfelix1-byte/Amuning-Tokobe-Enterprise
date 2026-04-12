FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libssl-dev \
    git \
    && docker-php-ext-install \
    mysqli \
    zip \
    pdo \
    pdo_mysql

RUN docker-php-ext-enable mysqli zip
RUN echo "phar.readonly = 0" >> /usr/local/etc/php/conf.d/phar.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy project files
COPY . /var/www/html/

# Install PHP dependencies (Google Client Library, PHPMailer, etc.)
WORKDIR /var/www/html/send_email
RUN composer update --no-dev --optimize-autoloader --prefer-dist

# Set document root to public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Images stay in /images
RUN ln -s /var/www/html/images /var/www/html/public/images
RUN ln -s /var/www/html/fonts /var/www/html/public/fonts
RUN ln -s /var/www/html/uploads /var/www/html/public/uploads 2>/dev/null || true

RUN a2enmod rewrite
RUN chown -R www-data:www-data /var/www/html

# PHP Configuration
RUN echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini
RUN echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini
RUN echo "default_socket_timeout = 10" >> /usr/local/etc/php/conf.d/timeout.ini
