FROM php:8.2-apache

# 1. Install system dependencies and PHP extensions required by Laravel & Composer
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath mbstring \
    && rm -rf /var/lib/apt/lists/*

# 2. Get official Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# 4. Point Apache DocumentRoot to Laravel's /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Set working directory and copy project files into container
WORKDIR /var/www/html
COPY . /var/www/html/

# 6. Install PHP dependencies (disabling scripts so it doesn't try running artisan without DB/ENV)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# 7. Set correct permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# 8. Run optimizations, database migrations, and start Apache at runtime
CMD php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground