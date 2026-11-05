# Rumah Produksi - BRMP D.I. Yogyakarta

Aplikasi web **"Rumah Produksi"** dirancang khusus untuk memonitoring ketersediaan, pergerakan stok, formula produksi pakan, dan pemetaan sebaran distribusi pada **Balai Penerapan Standar Instrumen Pertanian / Balai Riset Mutu Pakan (BRMP) Daerah Istimewa Yogyakarta (DIY)**.

---

## Fitur Utama Aplikasi

### 1. Bahan Baku Pakan
- **Monitoring Ketersediaan Bahan Baku**: Inventarisasi bahan baku pakan seperti Jagung Pipil Kering Super, Bungkil Kedelai Non-GMO, Dedak Padi Halus Super, Tepung Ikan Murni 60%, Tepung Maggot BSF Kering, Premix Vitamin & Mineral, dan Molase Tetes Tebu.
- **Spesifikasi Mutu Laboratorium**: Menampilkan kadar air, protein kasar, kandungan serat kasar, serta batas aflatoksin SNI.
- **Deteksi Buffer Stok Minimum**: Peringatan stok kritis saat persediaan mendekati batas aman produksi.

### 2. Pakan Jadi Olahan (Produk)
- **Katalog Produk Pakan Olahan BRMP DIY**: Pelet Starter Ikan Nila Pro-32, Pelet Apung Grower 2, Finisher Pelet Terapung Super, Pelet Benih Lele D-1, Crumbles Ayam Broiler Starter, Konsentrat Pertumbuhan Domba, dan Silase Tebon Jagung Fermentasi EM4.
- **Pencatatan Produksi & Bill of Materials (BOM) Otomatis**: Saat mencatat produksi pakan, sistem secara otomatis menghitung dan memotong stok bahan baku terkait sesuai resep formula.
- **Parameter Kualitas Pakan**: Informasi kadar protein, rasio FCR (*Feed Conversion Ratio*), kestabilan air, dan energi metabolis (ME).
- **Distribusi Pakan & Peta Sebaran**: Pencatatan penyaluran pakan jadi ke kelompok binaan dan penandaan titik koordinat peta wilayah DIY.

### 3. Log Mutasi & Audit Trail
- Pencatatan seluruh arus keluar-masuk stok pakan dan bahan baku secara real-time.

---

## 🚀 Panduan Deployment (Online & Bisa Diakses dari Mana Saja)

Website ini dibangun menggunakan **PHP Native + SQLite**, sehingga sangat ringan, mandiri (*zero external database setup*), dan siap dideploy ke berbagai platform hosting.

### Opsi 1: Akses Instan dari Mana Saja (Via Ngrok / Laragon Share)
Karena komputer Anda sudah memiliki Laragon dan Ngrok:
1. Buka terminal atau PowerShell di folder ini.
2. Jalankan perintah:
   ```powershell
   D:\laragon\bin\ngrok\ngrok.exe http 8000
   ```
   *(Atau dari UI Laragon: Klik kanan di Laragon ➔ **Quick App / Share** ➔ Pilih project ini).*
3. Salin URL publik berawalan `https://....ngrok-free.app`.
4. URL tersebut bisa langsung dibuka dari HP atau laptop mana saja di internet!

---

### Opsi 2: Deploy Permanen Gratis ke Cloud (Render.com)
Project ini sudah dilengkapi file `Dockerfile` siap pakai:
1. Buat repository baru di **GitHub** (misal: `rumah-produksi-brmp`).
2. Upload atau push kode ke GitHub:
   ```bash
   git init
   git add .
   git commit -m "Initial release Rumah Produksi BRMP DIY"
   git branch -M main
   git remote add origin https://github.com/USERNAME/rumah-produksi-brmp.git
   git push -u origin main
   ```
3. Buka **[Render.com](https://render.com)** dan buat akun gratis.
4. Klik **New +** ➔ **Web Service** ➔ Hubungkan dengan repository GitHub Anda.
5. Render akan secara otomatis mendeteksi `Dockerfile`.
6. Klik **Create Web Service**.
7. Dalam 2-3 menit, website Anda aktif di: `https://rumah-produksi-brmp.onrender.com` (gratis dengan SSL/HTTPS otomatis).

---

### Opsi 3: Deploy ke Web Hosting / cPanel (Niagahoster, DomaiNesia, Hostinger, dll.)
1. Kompres semua file di dalam folder ini menjadi file `.zip` (pastikan file `.htaccess`, folder `assets/`, `data/`, `api.php`, `db.php`, `index.php` ikut terkompres).
2. Login ke **cPanel** hosting Anda ➔ Buka **File Manager** ➔ Masuk ke folder `public_html`.
3. Upload file `.zip` lalu klik **Extract**.
4. Pastikan versi PHP di cPanel minimal **PHP 8.1** atau **PHP 8.2** dan ekstensi `pdo_sqlite` aktif (bawaan aktif di hampir seluruh shared hosting).
5. Pastikan folder `data/` memiliki permission **775** atau **755** agar SQLite dapat menulis data mutasi.
6. Website langsung online pada domain/subdomain Anda!

---

### Opsi 4: Deploy Menggunakan Docker (VPS / Cloud Server)
```bash
docker compose up -d --build
```
Aplikasi akan langsung berjalan di port `8080` (dapat diarahkan via Nginx reverse proxy dengan domain Anda).
