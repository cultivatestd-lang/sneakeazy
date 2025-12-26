# Panduan Menjalankan Model Rekomendasi (Python SVD)

Script `recommendation_model.py` ini dibuat agar bisa dijalankan di mana saja (di laptop Anda, server hosting, atau environment lain), asalkan Python terinstal.

Script ini menggunakan algoritma **Model-Based Collaborative Filtering** dengan pendekatan **Matrix Factorization (SVD)** untuk mencari produk yang mirip berdasarkan pola rating user.

---

## 1. Prasyarat (Requirements)

Pastikan Anda sudah menginstal **Python 3.x**.

Anda juga perlu menginstal pustaka yang dibutuhkan (`pandas`, `numpy`, `scikit-learn`). File `requirements.txt` sudah disediakan.

### Cara Instalasi Dependencies:

Buka terminal/command prompt di folder ini, lalu jalankan:

```bash
pip install -r requirements.txt
```

Atau instal manual:
```bash
pip install pandas numpy scikit-learn
```

---

## 2. Cara Menjalankan Script

Program ini dirancang untuk otomatis mendeteksi lokasi file data (`data/interactions.json`) relatif terhadap lokasi script itu sendiri. Jadi Anda bisa menjalankannya dari folder mana saja.

**Perintah:**

```bash
python recommendation_model.py
```

atau jika Anda menggunakan Python 3 secara eksplisit:

```bash
python3 recommendation_model.py
```

---

## 3. Apa yang Dilakukan Script Ini?

1.  **Membaca Data**: Script mencari folder `data/` di direktori yang sama dengan script.
2.  **Membangun Matrix**: Membuat tabel silang (Pivot Table) antara User dan Produk dengan nilai Rating.
3.  **Training Model SVD**: Menggunakan `TruncatedSVD` untuk mengekstrak 12 fitur laten (pola tersembunyi) dari data rating.
4.  **Menghitung Korelasi**: Mencari seberapa mirip satu produk dengan produk lain berdasarkan pola rating user.
5.  **Output**: Menampilkan contoh rekomendasi di terminal untuk beberapa produk sampel.

---

## 4. Troubleshooting

*   **Error: FileNotFoundError**: Pastikan folder `data/` berisi `interactions.json` dan `products.json` dan berada di sebelah file `.py` ini.
*   **Error: ModuleNotFoundError**: Berarti Anda belum menginstal dependency. Jalankan langkah instalasi di atas.
