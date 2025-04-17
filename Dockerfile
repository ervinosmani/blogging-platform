FROM php:8.2-fpm

# Instalimi i dependencave te sistemit
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    sqlite3 \
    libsqlite3-dev \
    libzip-dev \
    libpq-dev \         
    pkg-config

# Konfigurimi per GD extension
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/

# Instalimi i extensioneve
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath zip
RUN docker-php-ext-install gd

# Instalimi i Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Vendos direktorine e punes
WORKDIR /var/www

# Kopjo projektin
COPY . .

# Instalimi i paketave Laravel
RUN composer install --optimize-autoloader --no-dev

# Jep te drejta Laravel storage
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Ekspozimi i portes per Render
EXPOSE 8000

# Start Laravel Server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
