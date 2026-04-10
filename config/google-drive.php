<?php
// ============================================================
//  SIMAK — Konfigurasi Google Drive API (Service Account)
// ============================================================

define('GDRIVE_CREDENTIALS', BASE_PATH . '/credentials/service-account.json');

// ID folder Google Drive tujuan (diisi oleh Super Admin via UI)
// Format: ambil dari URL Drive → drive.google.com/drive/folders/{FOLDER_ID}
define('GDRIVE_ROOT_FOLDER', '');  // Akan di-override dari DB (settings)

// Sub-folder per modul (dibuat otomatis jika belum ada)
define('GDRIVE_FOLDER_RABUAN',      'SIMAK/Rabuan');
define('GDRIVE_FOLDER_MENTORING',   'SIMAK/Mentoring');
define('GDRIVE_FOLDER_OPERASIONAL', 'SIMAK/Operasional');

// ============================================================
//  Fungsi helper Google Drive akan ada di includes/gdrive.php
//  File ini hanya berisi konstanta konfigurasi
// ============================================================
