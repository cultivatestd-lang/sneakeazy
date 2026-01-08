# 📊 Database Setup Guide - SneakEazy

Panduan lengkap untuk setup database SneakEazy di local dan cloud.

## 🎯 Quick Setup (Recommended)

### Opsi 1: Auto-Seeding (Paling Mudah!)

Aplikasi akan otomatis membuat database saat pertama kali diakses:

1. Pastikan MAMP/XAMPP sudah running
2. Jalankan aplikasi: `php -S localhost:8000`
3. Buka browser: `http://localhost:8000`
4. Database akan otomatis dibuat dan di-seed dengan data

### Opsi 2: Manual Import (Full Control)

```bash
# 1. Masuk ke MySQL
mysql -u root -p -P 8889

# 2. Import complete database
source database/sneakeazy_complete.sql

# 3. Verifikasi
USE sneakeazy;
SHOW TABLES;
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM users;
```

## 📋 Database Configuration

### Local Development (MAMP)

File: `config/database.php`

```php
$db_host = '127.0.0.1';
$db_port = '8889';           // MAMP MySQL port
$db_name = 'sneakeazy';
$db_user = 'root';
$db_pass = 'root';
```

### Local Development (XAMPP)

```php
$db_host = '127.0.0.1';
$db_port = '3306';           // XAMPP MySQL port
$db_name = 'sneakeazy';
$db_user = 'root';
$db_pass = '';               // Default XAMPP password is empty
```

### Cloud (Google Cloud SQL)

Environment variables di `app.yaml`:

```yaml
env_variables:
  DB_USER: "root"
  DB_PASS: "your_secure_password"
  DB_NAME: "sneakeazy"
  INSTANCE_CONNECTION_NAME: "project-id:region:instance-name"
```

## 🗂️ Database Structure

### Tables Overview

| Table | Records | Description |
|-------|---------|-------------|
| `users` | 54 | User accounts (dummy + real) |
| `products` | 600+ | Shoe products from JSON |
| `interactions` | 1700+ | User-product interactions |

### Schema Details

#### Table: `users`
```sql
CREATE TABLE users (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Table: `products`
```sql
CREATE TABLE products (
    id VARCHAR(255) PRIMARY KEY,
    product_name VARCHAR(500),
    brand VARCHAR(255),
    original_price VARCHAR(100),
    sale_price VARCHAR(100),
    image_url TEXT,
    product_detail_url TEXT,
    rating DECIMAL(3,1),
    rating_count INT,
    category VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Table: `interactions`
```sql
CREATE TABLE interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255),
    product_id VARCHAR(255),
    rating DECIMAL(2,1),
    view_count INT DEFAULT 0,
    view_score INT DEFAULT 0,
    timestamp INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE (user_id, product_id)
);
```

## 🔧 Troubleshooting

### Error: "Access denied for user"

```bash
# Reset MySQL password (MAMP)
# 1. Stop MySQL
# 2. Start MySQL with skip-grant-tables
# 3. Reset password
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root';
FLUSH PRIVILEGES;
```

### Error: "Database does not exist"

```bash
# Create database manually
mysql -u root -p -P 8889
CREATE DATABASE sneakeazy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Error: "Table doesn't exist"

```bash
# Re-import schema
mysql -u root -p -P 8889 sneakeazy < database/sneakeazy_complete.sql
```

### Products tidak muncul

```bash
# Check if products table has data
mysql -u root -p -P 8889
USE sneakeazy;
SELECT COUNT(*) FROM products;

# If empty, aplikasi akan auto-seed dari products.json
# Atau import manual via phpMyAdmin
```

## 📊 Verify Installation

### Via MySQL Command Line

```sql
USE sneakeazy;

-- Check tables
SHOW TABLES;

-- Check record counts
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'interactions', COUNT(*) FROM interactions;

-- Sample data
SELECT * FROM products LIMIT 5;
SELECT * FROM users LIMIT 5;
SELECT * FROM interactions LIMIT 10;
```

### Via phpMyAdmin

1. Buka `http://localhost:8889/phpMyAdmin/`
2. Login: `root` / `root`
3. Pilih database `sneakeazy`
4. Check semua tabel ada dan berisi data

## 🌐 Cloud Database Setup

### Google Cloud SQL

```bash
# 1. Create Cloud SQL instance
gcloud sql instances create sneakeazy-sql \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=asia-southeast2

# 2. Set root password
gcloud sql users set-password root \
  --host=% \
  --instance=sneakeazy-sql \
  --password=YOUR_SECURE_PASSWORD

# 3. Create database
gcloud sql databases create sneakeazy \
  --instance=sneakeazy-sql

# 4. Import data
gcloud sql import sql sneakeazy-sql \
  gs://YOUR_BUCKET/sneakeazy_complete.sql \
  --database=sneakeazy
```

## 📝 Data Files

### Location

```
data/
├── products.json        # 600+ produk sepatu
├── users.json          # 54 dummy users
└── interactions.json   # 1700+ interaksi
```

### Import via PHP

Aplikasi otomatis import dari JSON saat:
- Database kosong
- Tabel products kosong
- First access ke aplikasi

### Manual Import

Gunakan script di `proc/`:
- `generate_interactions_sql.py` - Generate SQL dari JSON
- `seed_cloud_db.php` - Seed ke cloud database

## ✅ Checklist

Sebelum push ke GitHub, pastikan:

- [ ] Database `sneakeazy` sudah dibuat
- [ ] Semua tabel (users, products, interactions) ada
- [ ] Products table berisi 600+ records
- [ ] Users table berisi 54 records
- [ ] Interactions table berisi 1700+ records
- [ ] Aplikasi bisa akses database tanpa error
- [ ] File `config/database.php` sudah dikonfigurasi
- [ ] File `.gitignore` sudah exclude file sensitive

## 🚀 Next Steps

Setelah database setup:

1. Test aplikasi: `php -S localhost:8000`
2. Buka browser: `http://localhost:8000`
3. Test fitur rekomendasi
4. Test filter (New, Sale, Brand)
5. Test rating system
6. Ready to push ke GitHub!

---

**Need Help?** Check `proc/FLOW_DOCUMENTATION.md` untuk detail alur sistem.
