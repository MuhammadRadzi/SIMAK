<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$user  = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Permintaan tidak valid.';
    } else {
        $old = post('password_lama');
        $new = post('password_baru');
        $confirm = post('password_konfirmasi');

        if (empty($old) || empty($new) || empty($confirm)) {
            $error = 'Semua field wajib diisi.';
        } elseif (strlen($new) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $row  = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($old, $row['password'])) {
                $error = 'Password lama tidak sesuai.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $hash, $user['id']);
                $stmt->execute();
                $stmt->close();
                $success = 'Password berhasil diubah.';
            }
        }
    }
}

$pageTitle = 'Ganti Password';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="page-title">Ganti Password</h2>
    </div>

    <div class="card" style="max-width:480px;">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="password_lama" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru <small>(min. 8 karakter)</small></label>
                    <input type="password" name="password_baru" class="form-input" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_konfirmasi" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Password</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
