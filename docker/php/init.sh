#!/bin/bash
set -e

# Masuk ke direktori kerja
cd /var/www/html

if [ ! -f /var/www/html/composer.json ]; then
  echo "Laravel belum ada, instalasi dimulai..."
  composer create-project laravel/laravel="12.*" .
else
  echo "Laravel sudah terpasang, skip instalasi."
fi

# Pastikan file .env ada sebelum melakukan sed
if [ ! -f .env ]; then
    echo "Membuat file .env dari .env.example..."
    cp .env.example .env
    php artisan key:generate
fi

# Ganti nilai DB_HOST, DB_DATABASE, DLL dari ENV Docker
echo "Menyesuaikan .env dengan variabel environment..."
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION}/" .env
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env

# --- OTOMATISASI NPM UNTUK TAILWIND ---
if [ -f "package.json" ]; then
    echo "Membersihkan cache NPM dan menginstal ulang dependensi..."
    # Hapus folder node_modules lama jika ada (mencegah korupsi path)
    # rm -rf node_modules 
    
    npm install
    
    echo "Menjalankan Vite Build..."
    npm run build
    
    # Jalankan build jika di production, atau biarkan dev yang handle di local
    if [ "$APP_ENV" = "production" ]; then
        echo "Membangun aset untuk produksi..."
        npm run build
    fi
fi
# ---------------------------------------

# Atur hak akses direktori
echo "Mengatur hak akses direktori storage dan bootstrap/cache..."
# Di dalam init.sh
echo "Memastikan folder storage dan cache ada..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

echo "Mengatur hak akses..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Menjalankan optimisasi Laravel..."
# Jika local, kita tetap butuh dependensi dev (seperti Pest/Faker/Vite)
if [ "$APP_ENV" = "production" ]; then
    composer install --optimize-autoloader --no-dev
else
    composer install
fi

# Tunggu database siap (opsional tapi disarankan jika tidak pakai healthcheck)
# Tunggu database siap
echo "Menunggu database di ${DB_HOST} siap..."
until curl -s --head  --request GET http://${DB_HOST}:3306 | grep "52" > /dev/null; do
  # Catatan: 3306 bukan HTTP, jadi kita bisa pakai nc (netcat) yang lebih akurat
  if command -v nc >/dev/null 2>&1; then
    while ! nc -z $DB_HOST 3306; do
      echo "Database belum siap, menunggu 2 detik..."
      sleep 2
    done
  else
    echo "Netcat tidak ditemukan, menggunakan sleep manual 10 detik..."
    sleep 10
    break
  fi
  break
done
echo "Database sudah siap!"
# sleep 5 

# Migrate database
echo "Menjalankan migrasi database..."
php artisan migrate --force

# Caching logic
if [ "$APP_ENV" = "production" ]; then
    echo "Mode production: menjalankan caching..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "Mode development: membersihkan cache..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
fi

# NPM Dependencies & Build
if [ "$APP_ENV" = "production" ]; then
    echo "Membangun aset untuk produksi..."
    npm run build
else
    # Tambahkan ini agar di local PC orang lain, aset tetap tersedia 
    # meskipun mereka tidak menjalankan npm run dev secara manual
    if [ ! -d "public/build" ]; then
        echo "Aset build tidak ditemukan, menjalankan build awal..."
        npm run build
    fi
fi

echo "Memastikan storage link terpasang..."
php artisan storage:link --force || true

# Jalankan Apache agar container tetap aktif
echo "Sistem siap!"
exec apache2-foreground