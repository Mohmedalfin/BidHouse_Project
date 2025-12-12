FROM laravelsail/php82-composer:latest

WORKDIR /var/www/html

# Copy semua source code + env khusus Docker
COPY . .
COPY .env.docker .env

# Pastikan folder storage & cache siap
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

# TIDAK ADA artisan command di tahap build

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
