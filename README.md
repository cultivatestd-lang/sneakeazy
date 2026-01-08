# 👟 SneakEazy - Sistem Rekomendasi Sepatu

Sistem rekomendasi sepatu berbasis **Collaborative Filtering** dengan **Cold Start Strategy** menggunakan PHP dan MySQL.

## 📋 Deskripsi Project

SneakEazy adalah aplikasi web yang memberikan rekomendasi sepatu personal kepada pengguna berdasarkan:
- **Collaborative Filtering**: Rekomendasi berdasarkan preferensi pengguna lain yang serupa
- **Cold Start Handling**: Untuk pengguna baru, menggunakan social proof (rating) dan randomization
- **User Interactions**: Tracking hover, click, dan rating untuk meningkatkan akurasi rekomendasi

## ✨ Fitur Utama

- 🎯 **Personalized Recommendations** - "Top Picks For You" berdasarkan preferensi pengguna
- 🔥 **Dynamic Filtering** - Filter berdasarkan kategori (New Releases, Sale, Brand)
- ⭐ **Rating System** - User dapat memberikan rating 1-5 bintang
- 📊 **Interaction Tracking** - Mencatat hover dan click untuk analisis behavior
- 🎲 **Roulette Sorting** - Dynamic sorting untuk variasi hasil rekomendasi
- 🌐 **Cloud Ready** - Siap deploy ke Google Cloud Platform

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Deployment**: Google App Engine (Cloud SQL)
- **Local Development**: MAMP / XAMPP

## 📁 Struktur Folder

```
php-shoe-recommender/
├── config/
│   ├── database.php          # Konfigurasi koneksi database
│   └── cacert.pem           # SSL certificate untuk cloud database
├── database/
│   ├── schema.sql           # Schema database (tabel saja)
│   └── sneakeazy_complete.sql  # Complete setup (schema + seed data)
├── data/
│   ├── products.json        # Data produk sepatu (600+ items)
│   ├── users.json           # Data dummy users
│   └── interactions.json    # Data interaksi user-product
├── api/
│   └── config/
│       └── database.php     # API database config
├── proc/
│   ├── generate_interactions_sql.py  # Script generate data interaksi
│   ├── interactions_seed.sql         # SQL seed untuk interactions
│   └── *.md                          # Dokumentasi proses
├── index.php                # Main application file
├── app.yaml                 # Google App Engine config
└── README.md               # Dokumentasi ini
```

## 🚀 Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/YOUR_USERNAME/sneakeazy.git
cd sneakeazy
```

### 2. Setup Database

#### Opsi A: Setup Otomatis (Recommended)

Aplikasi akan otomatis membuat database dan import data saat pertama kali diakses.

#### Opsi B: Setup Manual

```bash
# Masuk ke MySQL
mysql -u root -p

# Import complete database
source database/sneakeazy_complete.sql
```

### 3. Konfigurasi Database

Edit file `config/database.php`:

```php
// Untuk Local Development (MAMP)
$db_host = '127.0.0.1';
$db_port = '8889';           // Port MAMP MySQL
$db_name = 'sneakeazy';
$db_user = 'root';
$db_pass = 'root';
```

### 4. Jalankan Server

```bash
# Masuk ke folder project
cd php-shoe-recommender

# Jalankan PHP built-in server
php -S localhost:8000
```

### 5. Akses Aplikasi

Buka browser dan akses:
```
http://localhost:8000
```

## 📊 Database Schema

### Tabel: `users`
```sql
- id (VARCHAR 255, PRIMARY KEY)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255)
- created_at, updated_at (TIMESTAMP)
```

### Tabel: `products`
```sql
- id (VARCHAR 255, PRIMARY KEY)
- product_name (VARCHAR 500)
- brand (VARCHAR 255)
- original_price (VARCHAR 100)
- sale_price (VARCHAR 100, NULLABLE)
- image_url (TEXT)
- product_detail_url (TEXT)
- rating (DECIMAL 3,1)
- rating_count (INT)
- category (VARCHAR 255)
- created_at, updated_at (TIMESTAMP)
```

### Tabel: `interactions`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (VARCHAR 255, FK -> users.id)
- product_id (VARCHAR 255, FK -> products.id)
- rating (DECIMAL 2,1, NULLABLE)
- view_count (INT)
- view_score (INT)
- timestamp (INT, Unix timestamp)
- created_at, updated_at (TIMESTAMP)
```

## 🧠 Algoritma Rekomendasi

### 1. Collaborative Filtering
Menggunakan **User-Based Collaborative Filtering**:
- Menghitung similarity antar user berdasarkan rating
- Memprediksi rating untuk produk yang belum di-rating
- Merekomendasikan produk dengan predicted rating tertinggi

### 2. Cold Start Strategy
Untuk user baru (belum ada interaksi):
- **Social Proof**: Prioritas produk dengan rating tinggi
- **Random Factor**: Menambah variasi dengan random score
- **Roulette Sorting**: Dynamic sorting untuk hasil yang berbeda setiap refresh

### 3. Interaction Tracking
- **Hover**: Mencatat produk yang di-hover (view_count)
- **Click**: Mencatat produk yang diklik
- **Rating**: User dapat memberikan rating 1-5 bintang

## 🌐 Deployment ke Google Cloud

### Prerequisites
- Google Cloud Account
- Google Cloud SDK installed
- Cloud SQL instance created

### Deploy Steps

```bash
# 1. Login ke Google Cloud
gcloud auth login

# 2. Set project
gcloud config set project YOUR_PROJECT_ID

# 3. Deploy ke App Engine
gcloud app deploy

# 4. Setup Cloud SQL
# - Buat Cloud SQL instance
# - Import database/sneakeazy_complete.sql
# - Update app.yaml dengan connection name
```

Lihat `proc/DEPLOY_GUIDE_ONLINE.md` untuk panduan lengkap.

## 🔧 Konfigurasi

### Environment Variables (Cloud)
```yaml
env_variables:
  DB_USER: "root"
  DB_PASS: "your_password"
  DB_NAME: "sneakeazy"
  INSTANCE_CONNECTION_NAME: "project:region:instance"
```

### Local Development
Edit `config/database.php` untuk menyesuaikan dengan environment lokal Anda.

## 📝 API Endpoints

### Get Recommendations
```
GET /?user_id=USER_ID
```

### Filter Products
```
GET /?filter=new          # New releases
GET /?filter=sale         # Sale items
GET /?brand=BRAND_NAME    # Filter by brand
```

### Track Interaction
```
POST /api/track.php
Body: {
  "user_id": "xxx",
  "product_id": "xxx",
  "action": "hover|click|rate",
  "rating": 1-5 (optional)
}
```

## 🧪 Testing

### Test User Accounts
```
Email: user_6684_0@example.com
Password: hashed_dummy_password
```

### Test Scenarios
1. **New User**: Akses tanpa login → Cold start recommendations
2. **Existing User**: Login → Personalized recommendations
3. **Interaction**: Hover, click, rate produk → Update recommendations

## 📚 Dokumentasi Tambahan

- `proc/FLOW_DOCUMENTATION.md` - Alur kerja sistem
- `proc/DATABASE_SETUP.md` - Setup database detail
- `proc/DEPLOY_GUIDE_ONLINE.md` - Panduan deployment
- `proc/PYTHON_GUIDE.md` - Script Python untuk generate data

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Data produk dari scraping website e-commerce
- Algoritma collaborative filtering berdasarkan penelitian sistem rekomendasi
- MAMP untuk local development environment

---

**Happy Coding! 🚀**
