# SIMAK — Sistem Informasi Monitoring Aktivitas Kegiatan

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/Google%20Drive-API-4285F4?style=for-the-badge&logo=googledrive&logoColor=white"/>
  <img src="https://img.shields.io/badge/Status-Active-16a34a?style=for-the-badge"/>
</p>

Aplikasi web untuk melacak, mengelola, dan memvisualisasikan seluruh aktivitas siswa — mencakup Rapat Rutin (Rabuan), Mentoring, Kegiatan Lapangan (Operasional), dan Bina Jasmani (Binjas).

---

## ✨ Fitur Utama

| Modul | Fitur |
|---|---|
| **Dashboard** | Statistik real-time, Bar Chart tren kehadiran, Radar Chart Binjas, top alpha |
| **Rabuan** | Jadwal rapat, upload notulensi PDF → Google Drive |
| **Mentoring** | Manajemen sesi, upload bahan ajar PDF → Google Drive, catatan logistik |
| **Operasional** | Alur 3 fase (Pra → Operasional → Pasca), manajemen peserta & peralatan, checklist kondisi alat |
| **Bina Jasmani** | Input nilai massal, perbandingan otomatis vs nilai standar, Radar Chart |
| **Presensi** | Input presensi per kegiatan, rekap per siswa per bulan |
| **Jadwal Terpadu** | Kalender bulanan + list view, semua kegiatan dalam satu tampilan |
| **Data Siswa** | CRUD lengkap, import CSV, soft delete, tab aktif/nonaktif |
| **Manajemen User** | RBAC (Super Admin & Admin), manajemen akun |
| **Pengaturan** | Konfigurasi Google Drive Shared Drive per modul |

---

## 🛠️ Tech Stack

- **Backend:** PHP Native (no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML + CSS + JavaScript Vanilla
- **Charts:** Chart.js 4.4 (CDN)
- **File Storage:** Google Drive API v3 (Service Account + Shared Drive)
- **Server:** XAMPP / Laragon

---

## ⚙️ Persyaratan Sistem

- PHP >= 8.1
- MySQL >= 5.7 / MariaDB >= 10.3
- Composer
- XAMPP atau server lokal lainnya
- Akun Google Workspace (untuk Shared Drive)
- Google Cloud Project dengan Drive API aktif

---

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/MuhammadRadzi/simak.git
cd simak
```

### 2. Letakkan di XAMPP

Salin folder `simak/` ke:
- **Windows:** `C:\xampp\htdocs\`
- **Linux:** `/opt/lampp/htdocs/`

### 3. Install Dependencies

```bash
composer require google/apiclient
```

### 4. Setup Database

1. Buka phpMyAdmin → `http://localhost/phpmyadmin`
2. Import file `database.sql`
3. Database `simak_db` terbuat otomatis beserta seed data

### 5. Konfigurasi Database

```bash
cp config/database.example.php config/database.php
```

Edit `config/database.php`, sesuaikan `DB_USER` dan `DB_PASS`.

### 6. Setup Google Drive API

> ⚠️ Wajib menggunakan **Google Workspace** (akun sekolah/organisasi) karena fitur Shared Drive tidak tersedia di akun Gmail biasa.

**Langkah:**

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru → aktifkan **Google Drive API**
3. Buat **Service Account** → buat key format JSON
4. Taruh file JSON di `credentials/service-account.json`
5. Di **Google Drive** (akun Workspace):
   - Buat **Shared Drive** baru (misal: `SIMAK`)
   - Buat subfolder: `Rabuan`, `Mentoring`, `Operasional`
   - Klik kanan Shared Drive → **Manage members** → tambahkan email Service Account sebagai **Contributor**
6. Salin **Folder ID** masing-masing subfolder dari URL Drive
7. Login SIMAK sebagai Super Admin → **Pengaturan** → paste Folder ID

```bash
cp credentials/service-account.example.json credentials/service-account.json
# Isi dengan key asli dari Google Cloud Console
```

### 7. Akses Aplikasi

```
http://localhost/simak
```

**Login default Super Admin:**
```
Username : superadmin
Password : Admin@SIMAK2024
```

> ⚠️ **Segera ganti password setelah login pertama!**

---

## 📁 Struktur Folder

```
simak/
├── assets/
│   ├── css/
│   │   ├── auth.css          # Styling halaman login
│   │   └── main.css          # CSS utama (layout, komponen)
│   ├── js/
│   │   └── main.js           # JavaScript global
│   └── template-siswa.csv    # Template import siswa
├── config/
│   ├── config.php            # Konfigurasi global aplikasi
│   ├── database.php          # Koneksi DB (di-gitignore)
│   ├── database.example.php  # Template konfigurasi DB
│   └── google-drive.php      # Konstanta Google Drive
├── credentials/
│   ├── service-account.json          # Key Google API (di-gitignore)
│   └── service-account.example.json # Template key
├── includes/
│   ├── auth_middleware.php   # Middleware autentikasi & RBAC
│   ├── functions.php         # Helper functions global
│   ├── gdrive.php            # Google Drive helper
│   ├── header.php            # HTML head + buka layout
│   ├── sidebar.php           # Sidebar + topbar
│   └── footer.php            # Tutup layout + load JS
├── modules/
│   ├── auth/                 # Login, logout, ganti password
│   ├── dashboard/            # Dashboard analitik
│   ├── siswa/                # Master data siswa (CRUD + import CSV)
│   ├── rabuan/               # Modul Rapat Rutin
│   ├── mentoring/            # Modul Mentoring
│   ├── jadwal/               # Jadwal Terpadu (kalender)
│   ├── operasional/          # Modul Kegiatan Lapangan (3 fase)
│   ├── binjas/               # Modul Bina Jasmani + standarisasi
│   ├── presensi/             # Modul Kehadiran
│   ├── users/                # Manajemen pengguna (Super Admin)
│   └── settings/             # Pengaturan sistem (Super Admin)
├── uploads/                  # File sementara sebelum ke Drive
├── vendor/                   # Composer dependencies (di-gitignore)
├── .gitignore
├── composer.json
├── database.sql              # Schema + seed data
├── index.php                 # Entry point
└── README.md
```

---

## 🔐 Hak Akses (RBAC)

| Fitur | Super Admin | Admin |
|---|:---:|:---:|
| Manajemen User | ✅ | ❌ |
| Pengaturan Sistem & Google Drive | ✅ | ❌ |
| Standarisasi Nilai Binjas | ✅ | ✅ |
| Input & Edit Semua Kegiatan | ✅ | ✅ |
| Upload Dokumen ke Google Drive | ✅ | ✅ |
| Input Presensi & Nilai | ✅ | ✅ |
| Lihat Dashboard & Laporan | ✅ | ✅ |
| CRUD Data Siswa | ✅ | ✅ |

---

## 🔒 Keamanan

- Password di-hash dengan **bcrypt** (cost 12)
- **CSRF token** di semua form POST
- **Session regeneration** saat login
- Input di-escape dengan `htmlspecialchars()` sebelum ditampilkan
- Query database menggunakan **Prepared Statement** (mencegah SQL Injection)
- File upload divalidasi tipe MIME (hanya PDF) dan ukuran (maks 10 MB)
- Halaman sensitif dilindungi middleware RBAC

---

## 📊 Alur Google Drive

```
User upload PDF
      ↓
Validasi (PDF only, ≤ 10MB)
      ↓
Simpan file sementara di /uploads/
      ↓
Upload ke Google Shared Drive (Service Account)
      ↓
Simpan File ID + Link di database
      ↓
Hapus file sementara dari server
      ↓
Link tersedia di sistem untuk diakses/diunduh
```

---

## 🗄️ Skema Database

| Tabel | Keterangan |
|---|---|
| `users` | Akun Super Admin & Admin |
| `siswa` | Master data siswa |
| `settings` | Konfigurasi sistem (folder Drive, dll) |
| `rabuan` | Jadwal rapat rutin |
| `rabuan_dokumen` | Notulensi PDF (link Google Drive) |
| `mentoring` | Sesi mentoring |
| `mentoring_dokumen` | Bahan ajar PDF (link Google Drive) |
| `operasional` | Kegiatan lapangan |
| `operasional_pra` | Data pra-operasional |
| `operasional_siswa` | Peserta kegiatan operasional |
| `operasional_peralatan` | Daftar peralatan + kondisi pasca |
| `operasional_laporan` | Laporan PDF (link Google Drive) |
| `binjas` | Sesi bina jasmani |
| `binjas_jenis_latihan` | Jenis latihan + nilai standar |
| `binjas_nilai` | Nilai per siswa per jenis latihan |
| `presensi` | Kehadiran siswa (Rabuan, Mentoring, Binjas) |

---

## 🐛 Troubleshooting

**Login gagal "Username atau password salah"**
→ Jalankan query di phpMyAdmin:
```sql
UPDATE users SET password = '$2y$12$pZLcbEJu/chvFy.BydfRGOSEMQGU6.lq9qATDSn6I5FO6Ibj449Ny' WHERE username = 'superadmin';
```

**Upload Google Drive error 403 storageQuotaExceeded**
→ Pastikan menggunakan **Shared Drive** (bukan My Drive). Service Account tidak punya storage quota sendiri.

**GDRIVE_CREDENTIALS undefined**
→ Pastikan `config/google-drive.php` sudah di-include di `config/config.php`.

**Halaman blank / error 500**
→ Aktifkan error reporting di `php.ini` atau cek file `error_log` di folder XAMPP.