# Gunakan PHP 8.2 CLI
FROM php:8.2-cli

# Install ekstensi yang umum dipakai Laravel
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev libpng-dev \
    && docker-php-ext-install intl pdo pdo_mysql opcache zip gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set direktori kerja di dalam container
WORKDIR /var/www/html

# Copy file composer dulu
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Baru copy semua source code
COPY . .

# (Optional) Optimasi Laravel, pakai || true biar gak gagal kalau env belum ada
RUN php artisan config:cache || true \
    && php artisan route:cache || true

# Container expose port 8000
EXPOSE 8000

# Jalankan Laravel pakai artisan serve
CMD php artisan serve --host=0.0.0.0 --port=8000
