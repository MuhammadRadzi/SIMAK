<?php
// ============================================================
//  SIMAK — Konfigurasi Global Aplikasi
// ============================================================

define('APP_NAME',    'SIMAK');
define('APP_FULL',    'Sistem Informasi Monitoring Aktivitas Kegiatan');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    'http://localhost/simak');
define('BASE_PATH',   dirname(__DIR__));

// Timezone
date_default_timezone_set('Asia/Makassar');

// Upload
define('UPLOAD_DIR',      BASE_PATH . '/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_TYPES',   ['application/pdf']);

// Session
define('SESSION_LIFETIME', 3600 * 8); // 8 jam

// Role
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN',        'admin');

// Status Kegiatan
define('STATUS_DRAFT',     'draft');
define('STATUS_AKTIF',     'aktif');
define('STATUS_SELESAI',   'selesai');
define('STATUS_BATAL',     'batal');

// Status Kondisi Alat
define('KONDISI_LAYAK',          'layak');
define('KONDISI_TIDAK_LAYAK',    'tidak_layak');
define('KONDISI_BUTUH_PERBAIKAN','butuh_perbaikan');

// Autoload config & DB
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/google-drive.php';
