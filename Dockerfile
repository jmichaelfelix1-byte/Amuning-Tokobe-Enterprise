FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    mysqli \
    zip \
    pdo \
    pdo_mysql

RUN docker-php-ext-enable mysqli zip
RUN echo "phar.readonly = 0" >> /usr/local/etc/php/conf.d/phar.ini

# Copy project files
COPY . /var/www/html/

# ===== CREATE UPLOAD DIRECTORIES (FIX FOR FILE UPLOADS) =====
# Create actual upload directories
RUN mkdir -p /var/www/html/public/uploads/printing
RUN mkdir -p /var/www/html/public/uploads/receipts
RUN mkdir -p /var/www/html/public/uploads/temp
RUN mkdir -p /var/www/html/uploads/printing
RUN mkdir -p /var/www/html/sessions

# Set proper permissions (www-data is Apache user)
RUN chown -R www-data:www-data /var/www/html/public/uploads
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chown -R www-data:www-data /var/www/html/sessions
RUN chmod -R 755 /var/www/html/public/uploads
RUN chmod -R 755 /var/www/html/uploads
RUN chmod -R 755 /var/www/html/sessions
# ============================================================

# Set document root to public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create symlinks for assets
RUN ln -s /var/www/html/images /var/www/html/public/images
RUN ln -s /var/www/html/fonts /var/www/html/public/fonts
RUN ln -s /var/www/html/public/uploads /var/www/html/uploads 2>/dev/null || true

# Enable Apache rewrite
RUN a2enmod rewrite

# ===== PHP CONFIGURATION FOR FILE UPLOADS =====
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "max_file_uploads = 20" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini
RUN echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini
RUN echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini

# Fix session save path
RUN echo "session.save_path = /var/www/html/sessions" >> /usr/local/etc/php/conf.d/sessions.ini
# =============================================

# Final permissions fix
RUN chown -R www-data:www-data /var/www/html
