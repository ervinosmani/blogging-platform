# Perdor imazhin PHP me FPM
FROM php:8.2-fpm

# Instalo varesite e nevojshme te sistemit
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

# RENDI I KETIJ URDHERI ESHTE I RENDESISHEM
# Konfiguro para instalimit te gd per te mos dhene error
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Tani instalo te gjitha extension-et PHP per Laravel
RUN docker-php-ext-install pdo mbstring exif pcntl bcmath gd zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Vendos direktorine e punes
WORKDIR /var/www

# Kopjo te gjitha skedaret e projektit
COPY . .

# Instalo Laravel dependencies pa dev
RUN composer install --optimize-autoloader --no-dev

# Jep leje Laravel-it per storage
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Trego qe porta 8000 do perdoret
EXPOSE 8000

# Start Laravel server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
