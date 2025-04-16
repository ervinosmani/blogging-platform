# Perdor imazhin zyrtar te PHP me FPM
FROM php:8.2-fpm

# Instalimi i varesive sistemore
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    sqlite3 \
    libsqlite3-dev \
    pkg-config

# Instalimi i extension-eve PHP (mbstring ka nevoje per oniguruma)
RUN docker-php-ext-install pdo mbstring exif pcntl bcmath gd zip

# Instalimi i Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Direktoria e punes ne container
WORKDIR /var/www

# Kopjo te gjitha skedaret ne container
COPY . .

# Instalimi i dependencave Laravel (pa dev)
RUN composer install --optimize-autoloader --no-dev

# Vendosja e lejeve per Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Hap porta 8000
EXPOSE 8000

# Komanda e startimit
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
