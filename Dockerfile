FROM php:8.2-cli

# Install dependensi sistem
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# PERBAIKAN 1: Salin semua berkas ke kontainer
COPY . .

# PERBAIKAN 2: Hapus paksa file putih 'storage' rusak jika terbawa dari Windows ke Docker
RUN rm -rf public/storage

# Install dependensi Laravel tanpa menjalankan script bawaan dulu
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# PERBAIKAN 3: Buat folder public/storage asli yang bersih dan aman di dalam kontainer
RUN mkdir -p storage bootstrap/cache public/storage && chmod -R 777 storage bootstrap/cache public/storage

# PERBAIKAN 4: Hubungkan secara resmi symlink storage di dalam lingkungan Linux Docker
RUN php artisan storage:link --force

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]