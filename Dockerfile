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
    pkg-config

# Konfigurimi per GD extension
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/

# Instalimi i extensioneve pa GD fillimisht
RUN docker-php-ext-install pdo mbstring exif pcntl bcmath zip

# Instalimi i GD extension veçmas
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
