<?php
// Manajemen User — hanya Super Admin
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

startSession();
requireSuperAdmin();

$db    = getDB();
$error = '';
$success = getFlash('success');

// ── Handle POST (tambah / edit / toggle / hapus) ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';

        // ── Tambah User ──────────────────────────────────────
        if ($action === 'tambah') {
            $nama     = sanitizeString($_POST['nama'] ?? '');
            $username = sanitizeString($_POST['username'] ?? '');
            $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $role     = $_POST['role'] ?? 'admin';

            if (empty($nama) || empty($username) || empty($email) || empty($password)) {
                $error = 'Semua field wajib diisi.';
            } elseif (strlen($password) < 8) {
                $error = 'Password minimal 8 karakter.';
            } elseif (!in_array($role, ['super_admin', 'admin'])) {
                $error = 'Role tidak valid.';
            } else {
                // Cek duplikat
                $chk = $db->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
                $chk->bind_param('ss', $username, $email);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $error = 'Username atau email sudah digunakan.';
                } else {
                    $hash    = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $creator = $_SESSION['user_id'];
                    $ins     = $db->prepare(
                        "INSERT INTO users (nama, username, email, password, role, created_by)
                         VALUES (?,?,?,?,?,?)"
                    );
                    $ins->bind_param('sssssi', $nama, $username, $email, $hash, $role, $creator);
                    if ($ins->execute()) {
                        setFlash('success', 'User berhasil ditambahkan.');
                    } else {
                        $error = 'Gagal menambahkan user.';
                    }
                    $ins->close();
                }
                $chk->close();
            }

        // ── Edit User ─────────────────────────────────────────
        } elseif ($action === 'edit') {
            $id       = (int)($_POST['id'] ?? 0);
            $nama     = sanitizeString($_POST['nama'] ?? '');
            $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $role     = $_POST['role'] ?? 'admin';
            $password = $_POST['password'] ?? '';

            if ($id <= 0 || empty($nama) || empty($email)) {
                $error = 'Data tidak lengkap.';
            } else {
                if (!empty($password) && strlen($password) < 8) {
                    $error = 'Password minimal 8 karakter.';
                } else {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $upd  = $db->prepare(
                            "UPDATE users SET nama=?, email=?, role=?, password=? WHERE id=?"
                        );
                        $upd->bind_param('ssssi', $nama, $email, $role, $hash, $id);
                    } else {
                        $upd = $db->prepare(
                            "UPDATE users SET nama=?, email=?, role=? WHERE id=?"
                        );
                        $upd->bind_param('sssi', $nama, $email, $role, $id);
                    }
                    if ($upd->execute()) {
                        setFlash('success', 'User berhasil diperbarui.');
                    } else {
                        $error = 'Gagal memperbarui user.';
                    }
                    $upd->close();
                }
            }

        // ── Toggle Aktif ──────────────────────────────────────
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$_SESSION['user_id']) {
                $error = 'Tidak bisa menonaktifkan akun sendiri.';
            } else {
                $db->query("UPDATE users SET is_active = NOT is_active WHERE id = $id");
                setFlash('success', 'Status user berhasil diubah.');
            }

        // ── Hapus User ────────────────────────────────────────
        } elseif ($action === 'hapus') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$_SESSION['user_id']) {
                $error = 'Tidak bisa menghapus akun sendiri.';
            } else {
                $db->query("DELETE FROM users WHERE id = $id");
                setFlash('success', 'User berhasil dihapus.');
            }
        }

        if (empty($error)) {
            header('Location: ' . BASE_URL . '/modules/auth/users.php');
            exit;
        }
    }
}

// ── Ambil data user ──────────────────────────────────────────
$users = $db->query(
    "SELECT u.*, c.nama AS created_by_nama
     FROM users u
     LEFT JOIN users c ON c.id = u.created_by
     ORDER BY u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manajemen User';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen User</h1>
            <p class="page-subtitle">Kelola akun Super Admin dan Admin sistem</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-tambah')">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Tambah User
        </button>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrapper">
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
                        <td><?= e($u['nama']) ?></td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'super_admin' ? 'badge-aktif' : 'badge-draft' ?>">
                                <?= $u['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?>
                            </span>
                        </td>
                        <td><?= $u['is_active'] ? '<span class="badge badge-selesai">Aktif</span>' : '<span class="badge badge-batal">Nonaktif</span>' ?></td>
                        <td><?= formatTanggal($u['created_at']) ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                                    Edit
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                        <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus user ini?')">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal">
        <div class="modal-header">
            <h2>Tambah User Baru</h2>
            <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="tambah">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-input" minlength="8" required>
                    <span class="form-hint">Minimal 8 karakter</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-tambah')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit User</h2>
            <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" id="edit-nama" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" id="edit-username" class="form-input" disabled>
                        <span class="form-hint">Username tidak bisa diubah</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role" id="edit-role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="edit-email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-input" minlength="8">
                    <span class="form-hint">Kosongkan jika tidak ingin mengubah password</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit-id').value       = user.id;
    document.getElementById('edit-nama').value     = user.nama;
    document.getElementById('edit-username').value = user.username;
    document.getElementById('edit-email').value    = user.email;
    document.getElementById('edit-role').value     = user.role;
    openModal('modal-edit');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
