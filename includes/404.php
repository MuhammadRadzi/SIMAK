<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--gray-100); }
        .error-box { text-align:center; padding:3rem 2rem; }
        .error-code { font-size:6rem; font-weight:900; color:var(--gray-200); line-height:1; }
        .error-box h1 { font-size:1.5rem; color:var(--gray-700); margin:.5rem 0; }
        .error-box p  { color:var(--gray-400); margin-bottom:1.5rem; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">404</div>
        <h1>Halaman Tidak Ditemukan</h1>
        <p>Halaman yang kamu cari tidak ada atau telah dipindahkan.</p>
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="btn btn-primary">← Kembali ke Dashboard</a>
    </div>
</body>
</html>
