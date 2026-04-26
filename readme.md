# Baca Saya 
## Detail Skema 
Ini adalah skema docker compose yang bisa digunakan untuk menjalankan laravel. 
Beberapa program yang digunakan adalah:
- Apache
- PHP 8.2
- Laravel Versi 12
- MySQL 8.0

## Cara Menggunakan
1. Pastikan anda sudah menginstall docker dan docker-compose di komputer anda.
2. Download atau clone repository ini.
3. Buka terminal dan masuk ke direktori tempat anda menyimpan file ini.
4. Jalankan perintah berikut untuk membangun dan menjalankan container:
   ```bash
   docker-compose up -d
   ```
5. Setelah semua terinstall, jangan lupa generate key untuk memiliki akses projek ini.
   ```bash
   docker compose exec app php artisan key:generate
   ```
6. Kemudian masuk ke folder projek, hingga laravel_app untuk menyalakan npm
   ```bash
   cd /path/to/laravel_app
   docker compose exec app npm run build
   ```
7. Setelah container berjalan, buka browser anda dan akses `http://localhost:8000` untuk melihat aplikasi Laravel yang sedang berjalan.
8. Jangan lupa untuk mengatur file `.env` sesuai dengan kebutuhan anda, terutama bagian database.

## Deskripsi
Sistem Pendukung Keputusan untuk manajemen komisi atau tugas kuliah dengan perbandingan 3 metode.

## Fitur Utama
1. Impor data dari Excel.
2. 3 Metode Pembobotan: Manual, AHP (dengan pengecekan CR), dan BWM.
3. 3 Metode SPK: SAW, ARAS, dan EDAS.
4. Grafik perbandingan skor menggunakan Chart.js.
