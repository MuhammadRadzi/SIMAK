<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
startSession();

// Kalau sudah login, langsung ke dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/modules/dashboard/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $username = post('username');
        $password = post('password');

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT id, nama, username, password, role, is_active
                 FROM users WHERE username = ? LIMIT 1"
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
                // Regenerasi session ID untuk keamanan
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_nama']     = $user['nama'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['login_time']    = time();

                setFlash('success', 'Selamat datang, ' . $user['nama'] . '!');
                redirect(BASE_URL . '/modules/dashboard/index.php');
            } else {
                $error = 'Username atau password salah, atau akun tidak aktif.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo / Brand -->
        <div class="auth-brand">
            <div class="auth-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">
                    <rect width="64" height="64" rx="16" fill="#2563eb"/>
                    <path d="M16 44V24l16-8 16 8v20l-16 8-16-8z" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/>
                    <path d="M32 16v36M16 24l16 8 16-8" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1 class="auth-title"><?= APP_NAME ?></h1>
            <p class="auth-subtitle"><?= APP_FULL ?></p>
        </div>

        <!-- Flash / Error -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" class="auth-form" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-wrapper">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Masukkan username"
                        value="<?= e(post('username')) ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Tampilkan password">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Masuk
            </button>
        </form>

        <p class="auth-footer">
            &copy; <?= date('Y') ?> <?= APP_NAME ?> — <?= APP_FULL ?>
        </p>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>
</body>
</html>
