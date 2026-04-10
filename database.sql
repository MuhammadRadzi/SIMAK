-- ============================================================
--  SIMAK — Sistem Informasi Monitoring Aktivitas Kegiatan
--  Schema Database v1.0.0
--  Engine: MySQL 5.7+ / MariaDB 10.3+
--  Charset: utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS simak_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE simak_db;

-- ============================================================
--  TABEL: users
--  Menyimpan akun Super Admin dan Admin
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100)  NOT NULL,
    username    VARCHAR(50)   NOT NULL UNIQUE,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,          -- bcrypt hash
    role        ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_by  INT UNSIGNED  NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: siswa
--  Master data siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS siswa (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nis             VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Nomor Induk Siswa',
    nama            VARCHAR(100)  NOT NULL,
    jenis_kelamin   ENUM('L','P') NOT NULL,
    tanggal_lahir   DATE          NULL,
    regu            VARCHAR(50)   NULL COMMENT 'Nama regu/kelompok',
    angkatan        YEAR          NULL,
    no_hp           VARCHAR(20)   NULL,
    alamat          TEXT          NULL,
    foto            VARCHAR(255)  NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: settings
--  Pengaturan sistem (dikelola Super Admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(100) NOT NULL UNIQUE,
    `value`     TEXT         NULL,
    label       VARCHAR(150) NULL COMMENT 'Label tampilan di UI',
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: rabuan
--  Modul Rapat Rutin (Rabuan)
-- ============================================================
CREATE TABLE IF NOT EXISTS rabuan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul           VARCHAR(200)  NOT NULL,
    tanggal         DATE          NOT NULL,
    waktu_mulai     TIME          NULL,
    waktu_selesai   TIME          NULL,
    lokasi          VARCHAR(200)  NULL,
    agenda          TEXT          NULL,
    status          ENUM('draft','aktif','selesai','batal') NOT NULL DEFAULT 'draft',
    created_by      INT UNSIGNED  NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: rabuan_dokumen
-- Notulensi rapat (PDF → Google Drive)
CREATE TABLE IF NOT EXISTS rabuan_dokumen (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rabuan_id       INT UNSIGNED  NOT NULL,
    nama_file       VARCHAR(255)  NOT NULL,
    gdrive_file_id  VARCHAR(255)  NULL COMMENT 'ID file di Google Drive',
    gdrive_link     VARCHAR(500)  NULL COMMENT 'Link akses file di Drive',
    ukuran_file     INT UNSIGNED  NULL COMMENT 'Ukuran dalam bytes',
    uploaded_by     INT UNSIGNED  NOT NULL,
    uploaded_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rabuan_id)   REFERENCES rabuan(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: mentoring
--  Modul Mentoring
-- ============================================================
CREATE TABLE IF NOT EXISTS mentoring (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul_materi    VARCHAR(200)  NOT NULL,
    nama_mentor     VARCHAR(100)  NOT NULL,
    tanggal         DATE          NOT NULL,
    waktu_mulai     TIME          NULL,
    waktu_selesai   TIME          NULL,
    lokasi          VARCHAR(200)  NULL,
    catatan_logistik TEXT         NULL,
    status          ENUM('draft','aktif','selesai','batal') NOT NULL DEFAULT 'draft',
    created_by      INT UNSIGNED  NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: mentoring_dokumen
-- File bahan ajar mentoring (PDF → Google Drive)
CREATE TABLE IF NOT EXISTS mentoring_dokumen (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mentoring_id    INT UNSIGNED  NOT NULL,
    nama_file       VARCHAR(255)  NOT NULL,
    gdrive_file_id  VARCHAR(255)  NULL,
    gdrive_link     VARCHAR(500)  NULL,
    ukuran_file     INT UNSIGNED  NULL,
    uploaded_by     INT UNSIGNED  NOT NULL,
    uploaded_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentoring_id) REFERENCES mentoring(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: operasional
--  Modul Kegiatan Lapangan (3 fase)
-- ============================================================
CREATE TABLE IF NOT EXISTS operasional (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan   VARCHAR(200)  NOT NULL,
    lokasi          VARCHAR(200)  NULL,
    tanggal_mulai   DATE          NOT NULL,
    tanggal_selesai DATE          NULL,
    fase            ENUM('pra','operasional','pasca') NOT NULL DEFAULT 'pra',
    status          ENUM('draft','aktif','selesai','batal') NOT NULL DEFAULT 'draft',
    created_by      INT UNSIGNED  NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: operasional_pra
-- Data fase Pra-Operasional
CREATE TABLE IF NOT EXISTS operasional_pra (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operasional_id      INT UNSIGNED  NOT NULL UNIQUE,
    kesiapan_peserta    TEXT          NULL COMMENT 'Catatan kesiapan peserta',
    perbekalan_regu     TEXT          NULL COMMENT 'Daftar perbekalan regu',
    catatan_tambahan    TEXT          NULL,
    updated_by          INT UNSIGNED  NULL,
    updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operasional_id) REFERENCES operasional(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: operasional_siswa
-- Data siswa yang terlibat dalam kegiatan operasional
CREATE TABLE IF NOT EXISTS operasional_siswa (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operasional_id  INT UNSIGNED NOT NULL,
    siswa_id        INT UNSIGNED NOT NULL,
    peran           VARCHAR(100) NULL COMMENT 'Peran/posisi siswa dalam kegiatan',
    catatan         TEXT         NULL,
    UNIQUE KEY uk_ops_siswa (operasional_id, siswa_id),
    FOREIGN KEY (operasional_id) REFERENCES operasional(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id)       REFERENCES siswa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: operasional_peralatan
-- Daftar peralatan (pribadi & regu)
CREATE TABLE IF NOT EXISTS operasional_peralatan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operasional_id  INT UNSIGNED NOT NULL,
    nama_alat       VARCHAR(150) NOT NULL,
    jenis           ENUM('pribadi','regu') NOT NULL,
    jumlah          INT UNSIGNED NOT NULL DEFAULT 1,
    satuan          VARCHAR(30)  NULL,
    keterangan      TEXT         NULL,
    -- Status kondisi diisi saat fase Pasca-Operasional
    kondisi         ENUM('layak','tidak_layak','butuh_perbaikan') NULL,
    catatan_kondisi TEXT         NULL,
    FOREIGN KEY (operasional_id) REFERENCES operasional(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: operasional_laporan
-- Laporan hasil kegiatan (PDF → Google Drive)
CREATE TABLE IF NOT EXISTS operasional_laporan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operasional_id  INT UNSIGNED  NOT NULL,
    nama_file       VARCHAR(255)  NOT NULL,
    gdrive_file_id  VARCHAR(255)  NULL,
    gdrive_link     VARCHAR(500)  NULL,
    ukuran_file     INT UNSIGNED  NULL,
    uploaded_by     INT UNSIGNED  NOT NULL,
    uploaded_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operasional_id) REFERENCES operasional(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)    REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: binjas
--  Modul Bina Jasmani
-- ============================================================
CREATE TABLE IF NOT EXISTS binjas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_sesi       VARCHAR(200)  NOT NULL,
    tanggal         DATE          NOT NULL,
    waktu_mulai     TIME          NULL,
    waktu_selesai   TIME          NULL,
    lokasi          VARCHAR(200)  NULL,
    status          ENUM('draft','aktif','selesai','batal') NOT NULL DEFAULT 'draft',
    created_by      INT UNSIGNED  NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: binjas_jenis_latihan
-- Jenis/kategori latihan fisik beserta nilai standarisasi
CREATE TABLE IF NOT EXISTS binjas_jenis_latihan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(150)  NOT NULL,
    satuan          VARCHAR(50)   NULL COMMENT 'Contoh: detik, meter, repetisi',
    nilai_standar   DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Nilai baseline standarisasi',
    keterangan_arah ENUM('semakin_tinggi','semakin_rendah') NOT NULL DEFAULT 'semakin_tinggi'
                    COMMENT 'semakin_tinggi = lebih baik (push up), semakin_rendah = lebih baik (lari)',
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABEL: binjas_nilai
-- Nilai latihan fisik per siswa per sesi
CREATE TABLE IF NOT EXISTS binjas_nilai (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    binjas_id           INT UNSIGNED    NOT NULL,
    siswa_id            INT UNSIGNED    NOT NULL,
    jenis_latihan_id    INT UNSIGNED    NOT NULL,
    nilai               DECIMAL(10,2)   NOT NULL,
    catatan             TEXT            NULL,
    input_by            INT UNSIGNED    NOT NULL,
    input_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_binjas_siswa_jenis (binjas_id, siswa_id, jenis_latihan_id),
    FOREIGN KEY (binjas_id)        REFERENCES binjas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id)         REFERENCES siswa(id)  ON DELETE CASCADE,
    FOREIGN KEY (jenis_latihan_id) REFERENCES binjas_jenis_latihan(id),
    FOREIGN KEY (input_by)         REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABEL: presensi
--  Modul Kehadiran (Rabuan, Mentoring, Binjas)
-- ============================================================
CREATE TABLE IF NOT EXISTS presensi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis_kegiatan  ENUM('rabuan','mentoring','binjas') NOT NULL,
    kegiatan_id     INT UNSIGNED NOT NULL COMMENT 'ID merujuk ke tabel sesuai jenis_kegiatan',
    siswa_id        INT UNSIGNED NOT NULL,
    status          ENUM('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'alpha',
    keterangan      TEXT         NULL,
    dicatat_oleh    INT UNSIGNED NOT NULL,
    dicatat_pada    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_presensi (jenis_kegiatan, kegiatan_id, siswa_id),
    FOREIGN KEY (siswa_id)    REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  DATA AWAL (Seed Data)
-- ============================================================

-- Super Admin default
-- Password: Admin@SIMAK2024 (bcrypt, ganti setelah install!)
INSERT INTO users (nama, username, email, password, role) VALUES
(
    'Super Administrator',
    'superadmin',
    'superadmin@simak.local',
    '$2y$12$pZLcbEJu/chvFy.BydfRGOSEMQGU6.lq9qATDSn6I5FO6Ibj449Ny',
    'super_admin'
);

-- Settings default
INSERT INTO settings (`key`, `value`, `label`) VALUES
('gdrive_root_folder_id',   '',         'ID Folder Google Drive Utama'),
('gdrive_folder_rabuan',    '',         'ID Folder Drive - Rabuan'),
('gdrive_folder_mentoring', '',         'ID Folder Drive - Mentoring'),
('gdrive_folder_operasional','',        'ID Folder Drive - Operasional'),
('app_name',                'SIMAK',    'Nama Aplikasi'),
('nama_institusi',          '',         'Nama Institusi/Sekolah'),
('tahun_ajaran',            '2024/2025','Tahun Ajaran Aktif');

-- Jenis latihan Binjas default
INSERT INTO binjas_jenis_latihan (nama, satuan, nilai_standar, keterangan_arah) VALUES
('Lari 2400 Meter',  'detik',    720,  'semakin_rendah'),
('Push Up',          'repetisi', 30,   'semakin_tinggi'),
('Sit Up',           'repetisi', 30,   'semakin_tinggi'),
('Pull Up',          'repetisi', 10,   'semakin_tinggi'),
('Shuttle Run',      'detik',    12,   'semakin_rendah');

