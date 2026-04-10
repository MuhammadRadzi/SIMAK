<?php require_once BASE_PATH . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akses Ditolak — SIMAK</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-code">403</div>
        <h1>Akses Ditolak</h1>
        <p>Kamu tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="btn btn-primary">Kembali ke Dasbor</a>
    </div>
</body>
</html>
