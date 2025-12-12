

## About BidHouse Backend API

[cite_start]Backend REST API untuk platform lelang online **BidHouse**[cite: 9]. [cite_start]Sistem ini dirancang agar pengguna dapat mengikuti proses lelang secara digital, mulai dari melihat daftar barang yang dilelang hingga memberikan penawaran harga[cite: 10]. [cite_start]Seluruh interaksi diimplementasikan melalui sebuah REST API yang harus dibangun sepenuhnya dari sisi backend[cite: 11].

[cite_start]Proyek ini dibangun menggunakan **Laravel** dan menerapkan autentikasi berbasis token  [cite_start]serta memisahkan peran (Admin dan User biasa)[cite: 14, 17].

---

## 🛠️ I. Panduan Setup dan Menjalankan Proyek Secara Lokal

Panduan ini menjelaskan langkah-langkah yang diperlukan untuk menjalankan API BidHouse di lingkungan lokal Anda.

### 1. Prasyarat

Pastikan *software* berikut sudah terinstal di sistem Anda:

* **PHP:** Versi 8.1 atau yang lebih baru.
* **Composer:** Manajer *dependency* PHP.
* **Database Server:** PostgreSQL atau MySQL (pastikan *service* berjalan).
* **Git:** Untuk mengkloning repositori.

### 2. Setup Awal (Instalasi dan Konfigurasi)

Ikuti perintah terminal berikut secara berurutan:

```bash
# A. Kloning Repositori
git clone [https://github.com/ldclabs/anda](https://github.com/ldclabs/anda)
cd bidhouse-backend 

# B. Instalasi Dependencies
composer install

# C. Konfigurasi File Lingkungan (.env)
cp .env.example .env
php artisan key:generate

# D. Pengaturan Database
# 1. Pastikan Anda telah membuat database kosong (misal: 'bidhouse_db').
# 2. Sesuaikan kredensial DB di file .env.
php artisan migrate --seed 
# Perintah ini akan menjalankan migrasi dan seeder (jika ada data admin/user awal)
