FROM php:8.2-apache

# 1. Install dependensi sistem dan Node.js
# Kita gunakan NodeSource agar mendapatkan versi Node.js yang stabil (LTS)
# 1. Install dependensi sistem
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zlib1g-dev \
    && curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install ekstensi PHP (Digabung agar lebih stabil)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set Working Directory
WORKDIR /var/www/html

# 5. Aktifkan mod_rewrite Apache untuk Laravel
RUN a2enmod rewrite

# 6. Salin konfigurasi Apache
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf

# 7. Salin seluruh kode aplikasi (untuk production build)
# Jika untuk development, folder ini biasanya di-mount lewat docker-compose
COPY . .

# 8. Script Entrypoint
COPY docker/php/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# 9. Permission (Penting untuk Arch/Linux)
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["/usr/local/bin/init.sh"]