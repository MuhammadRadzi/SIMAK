<?php
// ============================================================
//  SIMAK — Template Konfigurasi Database
//  Salin file ini, rename jadi database.php, lalu isi sesuai
//  environment kamu. File database.php sudah di-.gitignore
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simak_db');
define('DB_CHARSET', 'utf8mb4');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log('[SIMAK] Koneksi DB gagal: ' . $conn->connect_error);
            http_response_code(500);
            die(json_encode(['error' => 'Koneksi database gagal.']));
        }
        $conn->set_charset(DB_CHARSET);
    }
    return $conn;
}
