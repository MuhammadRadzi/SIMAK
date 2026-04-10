<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
requireRole(ROLE_SUPER_ADMIN);

$db = getDB();
$pageTitle = 'Manajemen Pengguna';

// Hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    if (!csrf_verify()) { setFlash('error', 'Permintaan tidak valid.'); redirect(BASE_URL . '/modules/users/index.php'); }
    $delId = postInt('user_id');
    $me    = currentUser();
    if ($delId === (int)$me['id']) {
        setFlash('error', 'Tidak dapat menghapus akun sendiri.');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
        $stmt->bind_param('i', $delId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Pengguna berhasil dihapus.');
    }
    redirect(BASE_URL . '/modules/users/index.php');
}

// Toggle aktif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'toggle') {
    if (!csrf_verify()) { setFlash('error', 'Permintaan tidak valid.'); redirect(BASE_URL . '/modules/users/index.php'); }
    $togId = postInt('user_id');
    $stmt  = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'super_admin'");
    $stmt->bind_param('i', $togId);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Status pengguna diperbarui.');
    redirect(BASE_URL . '/modules/users/index.php');
}

$users = $db->query(
    "SELECT u.id, u.nama, u.username, u.email, u.role, u.is_active, u.created_at,
            c.nama AS created_by_nama
     FROM users u
     LEFT JOIN users c ON c.id = u.created_by
     ORDER BY u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$flash = getFlash();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Manajemen Pengguna</h2>
            <p class="page-sub">Kelola akun Admin sistem SIMAK</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/users/create.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Admin
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($u['nama']) ?></strong></td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'super_admin'): ?>
                                <span class="badge badge-primary">Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $u['is_active']
                                ? '<span class="badge badge-success">Aktif</span>'
                                : '<span class="badge badge-danger">Nonaktif</span>'
                            ?>
                        </td>
                        <td><?= formatTanggal($u['created_at'], 'd M Y') ?></td>
                        <td>
                            <?php if ($u['role'] !== 'super_admin'): ?>
                            <div class="action-btns">
                                <a href="<?= BASE_URL ?>/modules/users/edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status pengguna ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-info">
                                        <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
