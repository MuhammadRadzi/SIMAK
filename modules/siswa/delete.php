<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    setFlash('error', 'Permintaan tidak valid.');
    redirect(BASE_URL . '/modules/siswa/index.php');
}

$db = getDB();
$id = postInt('id');

// Soft delete (nonaktifkan saja)
$stmt = $db->prepare("UPDATE siswa SET is_active = 0 WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

setFlash('success', 'Siswa berhasil dihapus dari daftar aktif.');
redirect(BASE_URL . '/modules/siswa/index.php');
