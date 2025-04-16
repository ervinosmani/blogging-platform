FROM php:8.2-fpm

# Instalo varesite e sistemit
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

# Konfiguro para instalimit te gd
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Instalimi i extension-eve PHP per Laravel
RUN docker-php-ext-install pdo mbstring exif pcntl bcmath gd zip

# Instalimi i Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Vendos directory ku punon serveri
WORKDIR /var/www

# Kopjo te gjitha fajllat ne container
COPY . .

# Instalimi i paketave Laravel
RUN composer install --optimize-autoloader --no-dev

# Jep leje Laravel storage
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Ekspozimi i portes per Render
EXPOSE 8000

# Startimi i Laravel Server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
