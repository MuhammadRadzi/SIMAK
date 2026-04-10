<?php
// Header dipanggil setelah $pageTitle di-set di halaman masing-masing
if (!isset($pageTitle)) $pageTitle = 'SIMAK';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <?php if (isset($extraCss)): ?>
        <?php foreach ((array)$extraCss as $css): ?>
            <link rel="stylesheet" href="<?= e($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div class="app-layout">
