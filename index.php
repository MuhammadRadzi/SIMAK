<?php
// ============================================================
//  SIMAK — Entry Point
//  Redirect ke dashboard jika sudah login, ke login jika belum
// ============================================================
require_once __DIR__ . '/config/config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
}
exit;
