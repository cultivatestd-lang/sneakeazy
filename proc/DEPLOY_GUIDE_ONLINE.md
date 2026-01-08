# Panduan Deployment Online (Gratis)

Aplikasi SNEAKEAZY ini menggunakan **Database JSON (Flat-File)**. Artinya, data disimpan langsung di dalam file (`data/*.json`).

⚠️ **PENTING:** Karena aplikasi ini **menulis** data ke file (saat Register/Rating), platform serverless modern seperti **Vercel, Netlify, atau Heroku** TIDAK COCOK secara langsung karena sistem file mereka bersifat *ephemeral* (data akan hilang/reset setiap kali server restart).

Solusi terbaik untuk hosting gratis aplikasi tipe ini adalah:
1.  **Hosting PHP Tradisional (Shared Hosting)** - ❌ Permanen & Stabil.
2.  **Tunneling (Localtunnel/Ngrok)** - ⚡ Kilat (Untuk Demo dari Laptop sendiri).

---

## OPSI 1: Hosting Gratis Permanen (Rekomendasi)

Gunakan penyedia hosting PHP tradisional gratis seperti:
*   **000Webhost** (by Hostinger)
*   **InfinityFree**
*   **ProFreeHost**

### Langkah-Langkah:

1.  **Daftar Akun** di salah satu situs di atas (misal: 000webhost.com).
2.  **Buat Website Baru** di dashboard mereka.
3.  **Buka File Manager** (atau gunakan FTP Client seperti FileZilla).
4.  **Masuk ke folder `public_html`**.
5.  **Upload File**:
    *   Upload file `index.php`.
    *   Buat folder `data`.
    *   Upload isi folder `data/` (`products.json`, `users.json`, `interactions.json`) ke dalam folder `data` di hosting.
6.  **Atur Izin File (Permissions)**:
    *   Agar website bisa *menyimpan* rating dan user baru, folder `data` dan isinya harus bisa ditulis (Writable).
    *   Di File Manager, klik kanan folder `data` -> **Permissions (CHMOD)** -> Set ke **777** (Read, Write, Execute untuk semua).
    *   Lakukan hal yang sama untuk file `.json` di dalamnya.
7.  **Selesai!** Website Anda sudah online permanen.

---

## OPSI 2: Demo Online Instan (Dari Laptop Anda)

Jika Anda hanya ingin menunjukkan website ini ke teman **sekarang juga** tanpa ribet upload, gunakan fitur **Tunneling**. Ini akan membuat link internet publik yang tersambung langsung ke laptop Anda.

**Syarat:** Laptop harus menyala dan PHP Server harus berjalan.

### Langkah-Langkah:

1.  Pastikan server PHP lokal berjalan di port 8000:
    ```bash
    php -S localhost:8000
    ```

2.  Buka terminal **BARU** (tab baru).
3.  Jalankan perintah berikut (menggunakan `npx` yang biasanya sudah ada jika install Node.js):
    ```bash
    npx localtunnel --port 8000
    ```
4.  Terminal akan memberikan link url (contoh: `https://slimy-frog-45.loca.lt`).
5.  **Buka Link tersebut**. (Pertama kali dibuka, biasanya Localtunnel meminta password tunnel. Passwordnya adalah IP publik Anda, cek di [localtunnel.me/password](https://localtunnel.me/password) jika diminta).
6.  Bagikan link tersebut ke siapa saja.

---

## Catatan Keamanan

Karena ini adalah proyek portofolio/prototype dengan database JSON sederhana:
1.  Jangan gunakan password asli/penting saat register.
2.  Data JSON di hosting publik (Opsi 1) bisa diakses langsung jika seseorang tahu nama filenya (misal `domain.com/data/users.json`). Untuk produksi nyata, folder `data` harus diamankan dengan `.htaccess` (Deny form all).

### Mengamankan Folder Data (Opsional)
Buat file bernama `.htaccess` di dalam folder `data/` dengan isi:
```apache
Deny from all
```
Ini akan mencegah orang mendownload database JSON Anda, tapi PHP tetap bisa membacanya.
