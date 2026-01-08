# DOKUMENTASI SISTEM REKOMENDASI (BAHASA INDONESIA)

## 1. Ikhtisar Sistem
Sistem ini adalah mesin rekomendasi e-commerce berbasis PHP yang berjalan di lingkungan Google App Engine. Sistem ini menggunakan **Pendekatan Hybrid (Hibrida)** yang menggabungkan berbagai teknik untuk memberikan rekomendasi produk yang paling relevan kepada pengguna.

Semua logika utama pemrosesan data dan rekomendasi terdapat di file: `sneakeazy_core/index.php`.

---

## 2. Fitur "Top Picks" (Rekomendasi Utama)

Fitur ini dapat diakses melalui filter `Top Picks` (`?filter=top-picks`) dan merupakan inti dari personalisasi sistem.

### Logika Awal (Authentication Guard)
*   Sistem memeriksa apakah pengguna sudah login.
*   Jika belum, pengguna akan diarahkan (redirect) ke halaman login. Hal ini memastikan bahwa rekomendasi yang diberikan benar-benar dipersonalisasi.

### Alur Pemrosesan
Untuk mengoptimalkan kinerja dan mencegah kelambatan (*slow loading*), sistem memisahkan logika menjadi dua kondisi utama:

#### A. Pengguna yang Sudah Memberikan Rating (Punya Histori)
Jika pengguna sudah pernah memberi rating pada produk, sistem menggunakan **Collaborative Filtering** yang dioptimalkan:

1.  **Pengambilan Data Terpusat (Optimization)**
    *   Sistem mengambil 5 produk dengan rating tertinggi (bintang 4 atau 5) dari histori pengguna.
    *   Sistem melakukan "Pre-fetch" data item-to-item similarity dari database *sekaligus* untuk menghindari *N+1 Query Problem*.
    *   Sistem juga memuat semua interaksi rating/view pengguna ke dalam memori (*hash map*) untuk akses cepat.

2.  **Penghitungan Skor (In-Memory Scoring)**
    Sistem menghitung skor untuk setiap produk (dari 500 produk sampel) secara instan di memori:
    *   **Skor Dasar**: Diambil dari rating global produk.
    *   **Collaborative Boost**: Jika produk ini sering disukai oleh orang lain yang juga menyukai produk favorit Anda, skornya ditambah (+100 poin maks).
    *   **Content Match**: Menambah nilai jika kategori/merek cocok dengan preferensi pengguna.
    *   **Priority Boost (Histori Sendiri)**:
        *   **Tier 1 (Rated)**: Produk yang diberi rating oleh pengguna mendapat lonjakan skor +1000 hingga +1500 poin. Wajib muncul paling atas.
        *   **Tier 2 (Viewed)**: Produk yang pernah dilihat pengguna mendapat +500 poin.

#### B. Pengguna Baru / Belum Memberikan Rating (Cold Start)
Jika pengguna baru dan belum memberi rating, sistem menggunakan pendekatan **Implicit Preference (Content-Based) + Social Proof**:

1.  **User Profiling**: Sistem membaca histori *view* (klik/hover) pengguna untuk menentukan merek dan kategori favorit (misal: Suka "Adidas" atau "Running").
2.  **Social Proof**: Produk dengan rating global tinggi diberi bobot lebih.
3.  **Randomness (Roulette)**: Faktor acak ditambahkan agar pengguna baru mendapatkan variasi produk untuk dieksplorasi.

---

## 3. Logika Pengurutan Dinamis (Dynamic Roulette)

Sistem tidak hanya mengurutkan berdasarkan skor tertinggi secara kaku (statis), tetapi menggunakan metode **Roulette/Shuffle** agar tampilan selalu segar.

**Cara Kerja:**
1.  Setelah semua produk diberi skor, sistem melakukan pengurutan.
2.  Jika selisih skor antar dua produk besar (>50 poin), produk dengan skor lebih tinggi menang.
3.  Jika selisih skor kecil (produk mirip relevansinya), posisinya diacak.
    *   *Efek*: Setiap kali pengguna me-refresh halaman, urutan produk yang relevan akan berubah-ubah, memberikan pengalaman penemuan (*discovery*) yang dinamis.

---

## 4. Pelacakan Interaksi (Interaction Tracking)

Sistem mencatat perilaku pengguna secara *real-time* untuk memperbarui rekomendasi:

*   **View/Click**: Saat pengguna membuka halaman detail produk, skor minat bertambah (+1.0).
*   **Hover (AJAX)**: Saat pengguna mengarahkan mouse ke kartu produk selama >2 detik, sistem mengirim sinyal background untuk menambah skor minat (+0.5).
*   **Rating**: Skor minat tertinggi (eksplisit) yang sangat mempengaruhi rekomendasi masa depan.

---

## 5. Struktur Teknis dan Optimasi

### Penanganan Database
*   **Koneksi**: Menggunakan PDO dengan dukungan Cloud SQL Socket (untuk Google Cloud) dan TCP/IP biasa (untuk lokal).
*   **Query**: Menggunakan *Prepared Statements* untuk keamanan dan kecepatan.

### Optimasi Kinerja
*   **File Exclusion**: File yang tidak perlu (seperti script seeding dan data mentah) dihapus dari upload Cloud untuk mempercepat *container startup*.
*   **GZIP Compression**: Diaktifkan di awal script untuk memperkecil ukuran transfer data ke browser.
*   **Pagination**: Sistem menggunakan `LIMIT` dan `OFFSET` untuk pengambilan data bertahap, namun "Top Picks" memproses batch 500 produk di memori karena kecepatannya.

---


