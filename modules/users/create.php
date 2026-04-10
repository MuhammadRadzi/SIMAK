<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
requireRole(ROLE_SUPER_ADMIN);

$db    = getDB();
$error = '';
$pageTitle = 'Tambah Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Permintaan tidak valid.';
    } else {
        $nama     = post('nama');
        $username = post('username');
        $email    = post('email');
        $password = post('password');
        $me       = currentUser();

        if (empty($nama) || empty($username) || empty($email) || empty($password)) {
            $error = 'Semua field wajib diisi.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek duplikat
            $chk = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $chk->bind_param('ss', $username, $email);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                $error = 'Username atau email sudah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare(
                    "INSERT INTO users (nama, username, email, password, role, created_by)
                     VALUES (?, ?, ?, ?, 'admin', ?)"
                );
                $stmt->bind_param('ssssi', $nama, $username, $email, $hash, $me['id']);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Admin ' . $nama . ' berhasil ditambahkan.');
                redirect(BASE_URL . '/modules/users/index.php');
            }
            $chk->close();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Tambah Admin</h2>
            <p class="page-sub">Buat akun Admin baru</p>
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
                    <input type="text" name="nama" class="form-input" value="<?= e(post('nama')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Username <span class="required">*</span></label>
                    <input type="text" name="username" class="form-input" value="<?= e(post('username')) ?>" required pattern="[a-zA-Z0-9_]+">
                    <small class="form-hint">Hanya huruf, angka, dan underscore.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" value="<?= e(post('email')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span> <small>(min. 8 karakter)</small></label>
                    <input type="password" name="password" class="form-input" required minlength="8">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Admin</button>
                    <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
