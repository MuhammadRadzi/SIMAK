# SIMAK — Sistem Informasi Monitoring Aktivitas Kegiatan

Aplikasi web untuk melacak, mengelola, dan memvisualisasikan seluruh aktivitas siswa, mencakup Rapat Rutin (Rabuan), Mentoring, Kegiatan Lapangan (Operasional), dan Bina Jasmani (Binjas).

## Tech Stack

- **Backend:** PHP Native
- **Database:** MySQL / MariaDB
- **Frontend:** HTML + CSS + JavaScript (Vanilla)
- **Penyimpanan Dokumen:** Google Drive API (Service Account)
- **Server Lokal:** XAMPP

## Persyaratan

- PHP >= 7.4
- MySQL >= 5.7 / MariaDB >= 10.3
- XAMPP (atau server lokal lainnya)
- Composer (untuk install Google API PHP Client)
- Akun Google Cloud Project dengan Drive API aktif

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/MuhammadRadzi/SIMAK.git
cd simak
```

### 2. Letakkan di folder XAMPP

Salin folder `simak/` ke dalam `C:/xampp/htdocs/` (Windows) atau `/opt/lampp/htdocs/` (Linux).

### 3. Setup Database

1. Buka phpMyAdmin → `http://localhost/phpmyadmin`
2. Import file `database.sql`
3. Database `simak_db` akan terbuat otomatis

### 4. Konfigurasi Database

```bash
cp config/database.example.php config/database.php
```

Edit `config/database.php` sesuaikan `DB_USER` dan `DB_PASS`.

### 5. Setup Google Drive API

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru → aktifkan **Google Drive API**
3. Buat **Service Account** → buat key (format JSON)
4. Salin file JSON tersebut ke `credentials/service-account.json`
5. Login sebagai Super Admin → buka **Pengaturan** → masukkan ID folder Google Drive

```bash
cp credentials/service-account.example.json credentials/service-account.json
# Lalu isi dengan key asli dari Google Cloud Console
```

### 6. Install Google API PHP Client

```bash
composer require google/apiclient:^2.0
```

### 7. Akses Aplikasi

Buka browser → `http://localhost/simak`

**Login default Super Admin:**
- Username: `superadmin`
- Password: `Admin@SIMAK2024`

> ⚠️ Segera ganti password setelah login pertama!

## Struktur Folder

```
simak/
├── assets/              # CSS, JS, Gambar
├── config/              # Konfigurasi DB & app
├── credentials/         # Service account Google (di-gitignore)
├── includes/            # Header, sidebar, footer, helper
├── modules/             # Modul-modul fitur
│   ├── auth/            # Login & logout
│   ├── dashboard/       # Dasbor utama
│   ├── siswa/           # Master data siswa
│   ├── rabuan/          # Modul Rapat Rutin
│   ├── mentoring/       # Modul Mentoring
│   ├── jadwal/          # Jadwal Terpadu
│   ├── operasional/     # Modul Kegiatan Lapangan
│   ├── binjas/          # Modul Bina Jasmani
│   └── presensi/        # Modul Kehadiran
├── uploads/             # File sementara sebelum ke Drive
├── vendor/              # Composer dependencies (di-gitignore)
├── .gitignore
├── database.sql         # Schema & seed data
└── index.php            # Entry point
```

## Hak Akses

| Fitur | Super Admin | Admin |
|---|:---:|:---:|
| Manajemen User | ✅ | ❌ |
| Pengaturan Sistem & Drive | ✅ | ❌ |
| Input & Edit Kegiatan | ✅ | ✅ |
| Upload Dokumen | ✅ | ✅ |
| Lihat Dashboard & Laporan | ✅ | ✅ |

## Lisensi

Proyek ini dikembangkan untuk keperluan internal sekolah.
