<?php
// ============================================================
//  SIMAK — Auth Middleware
//  Include file ini di setiap halaman yang butuh proteksi
//  Usage:
//    require_once BASE_PATH . '/includes/auth_middleware.php';
//    requireLogin();           // Semua yang sudah login
//    requireRole('super_admin'); // Hanya Super Admin
//    requireRole('super_admin','admin'); // SA atau Admin
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
startSession();
