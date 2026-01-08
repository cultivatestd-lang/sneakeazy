# Dokumentasi Sistem Rekomendasi SNEAKEAZY

Dokumen ini menjelaskan alur kerja, arsitektur data, dan algoritma perhitungan yang digunakan dalam sistem rekomendasi sepatu **SNEAKEAZY**.

## 1. Arsitektur Data (JSON Database)

Sistem ini tidak menggunakan database SQL konvensional, melainkan menggunakan **Flat-File JSON Database** yang ringan dan cepat untuk keperluan prototipe ini. Semua data tersimpan di folder `data/`:

*   **`products.json`**: Menyimpan master data sepatu.
    *   *Field*: `id`, `product_name`, `brand`, `category`, `image_url`, `rating` (rata-rata), `rating_count`.
*   **`users.json`**: Menyimpan data pengguna terdaftar.
    *   *Field*: `id`, `name`, `email`, `password` (hashed).
*   **`interactions.json`**: Menyimpan log aktivitas rating pengguna.
    *   *Field*: `user_id`, `product_id`, `rating` (1-5), `timestamp`.

---

## 2. Alur Proses Rekomendasi (Algorithm Flow)

Sistem menggunakan pendekatan **Hybrid Filtering** yang menggabungkan:
1.  **Content-Based Filtering**: Mencari kemiripan antar produk (misal: sama-sama sepatu lari).
2.  **Collaborative/Personalized Filtering**: Menyesuaikan dengan selera unik pengguna berdasarkan histori rating mereka.
3.  **Popularity-Based**: Memprioritaskan produk dengan rating global tinggi.

### Tahap 1: User Profiling (`getUserPreferences`)
Saat pengguna login, sistem menganalisis file `interactions.json` untuk membangun profil selera pengguna:
1.  Sistem mengambil semua produk yang diberi rating **Bintang 4 atau 5** oleh pengguna tersebut.
2.  Sistem menghitung frekuensi **Kategori** dan **Brand** dari produk-produk tersebut.
3.  Hasilnya adalah daftar urutan prioritas.
    *   *Contoh Profil User*: "Suka Kategori Basketball (#1), Brand Nike (#1)".

### Tahap 2: Scoring Engine (`calculateUserScore` & `getRecommendations`)
Setiap produk di database diberi **SKOR TOTAL** berdasarkan 4 komponen utama:

#### A. Skor Konteks (Context Relevance)
Digunakan saat merekomendasikan produk mirip di halaman detail ("You Might Also Like").
*   **Kategori Sama**: +30 Poin
*   **Brand Sama**: +15 Poin

#### B. Skor Personalisasi (Personalization)
Digunakan untuk menyesuaikan dengan selera user yang sedang login.
*   **Kecocokan Kategori**:
    *   Jika cocok dengan Kategori Favorit #1 User: **+40 Poin**
    *   Jika cocok dengan Kategori Favorit #2 User: **+30 Poin**
    *   Dst (berkurang 10 poin per peringkat, min 10).
*   **Kecocokan Brand**:
    *   Jika cocok dengan Brand Favorit #1 User: **+25 Poin**
    *   Jika cocok dengan Brand Favorit lainnya: **+10 Poin**

#### C. Skor Popularitas (Global Rating)
Produk populer di kalangan semua user mendapat boost skor.
*   Rumus: `Rating Rata-rata * 5`
*   *Contoh*: Rating 5.0 menyumbang **+25 Poin**. Rating 4.0 menyumbang **+20 Poin**.

#### D. Faktor Serendipity (Randomness)
Menambahkan elemen kejutan agar rekomendasi tidak terlalu kaku/statis.
*   **Random Noise**: +0 s/d 5 Poin (acak).

### Tahap 3: Pengurutan & Penyajian (Ranking)
1.  Sistem menghitung total skor untuk setiap produk kandidat.
2.  Produk diurutkan dari **Skor Tertinggi ke Terendah**.
3.  **4 Produk Teratas** diambil untuk ditampilkan di widget "You Might Also Like".
4.  Di **Halaman Utama (Home)**, *seluruh* feed produk diurutkan ulang berdasarkan skor ini (Personalized Feed).

---

## 3. Contoh Studi Kasus Perhitungan

**Profil User A (Reza):**
*   Suka: **Basketball** (Peringkat 1), Brand **Nike** (Peringkat 1).

**Target Produk: "Nike Air Jordan 11" (Kategori: Basketball)**

Perhitungan Skor untuk Reza:

1.  **Cek Brand (Nike)**: Cocok dengan Brand Favorit #1.
    *   Skor: **+25**
2.  **Cek Kategori (Basketball)**: Cocok dengan Kategori Favorit #1.
    *   Skor: **+40**
3.  **Cek Rating Global**: Misal produk ini punya rating 4.8.
    *   Skor: 4.8 * 5 = **+24**
4.  **Cek Konteks**: (Jika user sedang melihat sepatu basket lain).
    *   Kategori Sama: **+30**
    *   Brand Sama: **+15**
5.  **Randomness**: Misal **+2**

**TOTAL SKOR: 25 + 40 + 24 + 30 + 15 + 2 = 136 Poin**

*Produk ini memiliki peluang sangat besar untuk muncul di rekomendasi Reza dibandingkan sepatu "Vans Slip-On" (Kategori Lifestyle) yang mungkin hanya mendapat skor < 50.*

---

## 4. Fitur Real-Time

Sistem ini bersifat **Real-Time**.
1.  Begitu user klik "Submit Rating" di halaman detail sistem akan langsung:
    *   Menyimpan data ke `interactions.json`.
    *   Menghitung ulang rata-rata rating global di `products.json`.
2.  Saat user kembali ke Home atau refresh halaman, fungsi `getUserPreferences` akan langsung membaca data terbaru, memperbarui profil user, dan mengubah urutan rekomendasi seketika.

---

## 5. Cara Menjalankan Aplikasi

Pastikan PHP terinstal, lalu jalankan perintah berikut di terminal folder user:

```bash
cd php-shoe-recommender
php -S localhost:8000
```

Buka browser di `http://localhost:8000`.
