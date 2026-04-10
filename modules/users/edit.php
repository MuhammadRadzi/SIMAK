<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
requireRole(ROLE_SUPER_ADMIN);

$db    = getDB();
$error = '';
$id    = (int)get('id');
$pageTitle = 'Edit Admin';

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    setFlash('error', 'Pengguna tidak ditemukan.');
    redirect(BASE_URL . '/modules/users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Permintaan tidak valid.';
    } else {
        $nama     = post('nama');
        $email    = post('email');
        $password = post('password');

        if (empty($nama) || empty($email)) {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 8) { $error = 'Password minimal 8 karakter.'; }
                else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $db->prepare("UPDATE users SET nama=?, email=?, password=? WHERE id=?");
                    $stmt->bind_param('sssi', $nama, $email, $hash, $id);
                    $stmt->execute(); $stmt->close();
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET nama=?, email=? WHERE id=?");
                $stmt->bind_param('ssi', $nama, $email, $id);
                $stmt->execute(); $stmt->close();
            }
            if (empty($error)) {
                setFlash('success', 'Data admin berhasil diperbarui.');
                redirect(BASE_URL . '/modules/users/index.php');
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Edit Admin</h2>
            <p class="page-sub">Ubah data akun <?= e($user['nama']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:560px;">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" class="form-input" value="<?= e($user['nama']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" value="<?= e($user['username']) ?>" disabled>
                    <small class="form-hint">Username tidak dapat diubah.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru <small>(kosongkan jika tidak diubah)</small></label>
                    <input type="password" name="password" class="form-input" minlength="8">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
