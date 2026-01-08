# Setup Database untuk Shoe Recommender

## Persyaratan
- MAMP terinstall dan berjalan
- MySQL/MariaDB aktif pada port 8889 (default MAMP)

## Langkah Setup

### 1. Buat Database
1. Buka phpMyAdmin: http://localhost:8888/phpMyAdmin5/index.php?route=/server/databases
2. Atau buka terminal dan jalankan:

```bash
# Login ke MySQL
mysql -u root -proot -h 127.0.0.1 -P 8889

# Buat database
CREATE DATABASE IF NOT EXISTS shoe_recommender CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import Schema
Jalankan file SQL untuk membuat tabel:

**Opsi A: Via phpMyAdmin**
1. Buka phpMyAdmin: http://localhost:8888/phpMyAdmin5/
2. Pilih database `shoe_recommender`
3. Klik tab "SQL"
4. Copy-paste isi file `database/schema.sql`
5. Klik "Go"

**Opsi B: Via Terminal**
```bash
mysql -u root -proot -h 127.0.0.1 -P 8889 shoe_recommender < database/schema.sql
```

### 3. Migrasikan Data dari JSON ke Database
Jalankan script migrasi:

**Via Browser:**
Buka: http://localhost:8888/php-shoe-recommender/migrate_to_database.php

**Via Terminal:**
```bash
cd php-shoe-recommender
php migrate_to_database.php
```

Script ini akan:
- Migrasi semua user dari `data/users.json` ke tabel `users`
- Migrasi semua produk dari `data/products.json` ke tabel `products`
- Migrasi semua interaksi dari `data/interactions.json` ke tabel `interactions`

### 4. Verifikasi
1. Buka phpMyAdmin
2. Pilih database `shoe_recommender`
3. Cek apakah tabel `users`, `products`, dan `interactions` sudah ada
4. Cek apakah data sudah terisi

## Konfigurasi Database

File konfigurasi: `config/database.php`

Default settings untuk MAMP:
- Host: localhost
- Port: 8889
- Database: shoe_recommender
- User: root
- Password: root

Jika berbeda, edit file `config/database.php`.

## Struktur Database

### Tabel: users
- `id` (VARCHAR): ID unik user
- `name` (VARCHAR): Nama user
- `email` (VARCHAR): Email user (unique)
- `password` (VARCHAR): Password hash
- `created_at`, `updated_at`: Timestamps

### Tabel: products
- `id` (VARCHAR): ID unik produk
- `product_name` (VARCHAR): Nama produk
- `brand` (VARCHAR): Brand produk
- `original_price`, `sale_price` (VARCHAR): Harga
- `image_url`, `product_detail_url` (TEXT): URL gambar dan detail
- `rating` (DECIMAL): Rating rata-rata
- `rating_count` (INT): Jumlah rating
- `category` (VARCHAR): Kategori produk
- `created_at`, `updated_at`: Timestamps

### Tabel: interactions
- `id` (INT AUTO_INCREMENT): Primary key
- `user_id` (VARCHAR): Foreign key ke users
- `product_id` (VARCHAR): Foreign key ke products
- `rating` (DECIMAL): Rating 1-5 (nullable)
- `view_count` (INT): Jumlah kali user melihat produk (default: 0)
- `view_score` (INT): Skor dari views (1 poin per view, max 5) (default: 0)
- `timestamp` (INT): Unix timestamp
- `created_at`, `updated_at`: Timestamps

**Unique constraint:** (user_id, product_id) - satu user hanya satu record per produk

## Fitur View/Click Tracking

Sistem tracking otomatis:
- Setiap kali user yang login membuka halaman detail produk, sistem akan:
  1. Mencari record interaction untuk user dan produk tersebut
  2. Jika ada: increment `view_count` (maks 5) dan update `view_score`
  3. Jika tidak ada: buat record baru dengan `view_count = 1` dan `view_score = 1`

- Scoring system:
  - 1 klik = 1 poin
  - 2 klik = 2 poin
  - ...
  - Maksimal 5 poin (maks 5 klik)
  
- Data ini digunakan untuk collaborative filtering di sistem rekomendasi

## Troubleshooting

### Error: "Database connection failed"
- Pastikan MAMP MySQL berjalan
- Cek port MySQL di MAMP (biasanya 8889)
- Cek credentials di `config/database.php`

### Error: "Table doesn't exist"
- Pastikan schema.sql sudah diimport
- Cek apakah database `shoe_recommender` sudah dibuat

### Error: "Access denied"
- Cek username dan password MySQL di `config/database.php`
- Default MAMP: user=`root`, password=`root`







