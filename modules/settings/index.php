<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
requireRole(ROLE_SUPER_ADMIN);

$db        = getDB();
$pageTitle = 'Pengaturan Sistem';
$flash     = getFlash();
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $keys = ['gdrive_folder_rabuan','gdrive_folder_mentoring','gdrive_folder_operasional','nama_institusi','tahun_ajaran'];
        $me   = currentUser();
        foreach ($keys as $key) {
            $val  = post($key);
            $stmt = $db->prepare("UPDATE settings SET `value`=?, updated_by=? WHERE `key`=?");
            $stmt->bind_param('sis', $val, $me['id'], $key);
            $stmt->execute(); $stmt->close();
        }
        setFlash('success', 'Pengaturan berhasil disimpan.');
        redirect(BASE_URL . '/modules/settings/index.php');
    }
}

// Ambil semua settings
$rows = $db->query("SELECT `key`, `value`, `label` FROM settings ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$settings = [];
foreach ($rows as $r) $settings[$r['key']] = ['value' => $r['value'], 'label' => $r['label']];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Pengaturan Sistem</h2>
            <p class="page-sub">Konfigurasi Google Drive dan informasi institusi</p>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:start;">

            <!-- Google Drive -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">☁️ Google Drive API</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" style="margin-bottom:1rem; font-size:.8125rem;">
                        Salin <strong>Folder ID</strong> dari URL Google Drive:<br>
                        <code style="font-size:.75rem;">drive.google.com/drive/folders/<strong style="color:var(--primary);">{FOLDER_ID}</strong></code>
                    </div>

                    <?php foreach (['gdrive_folder_rabuan'=>'📁 Folder Drive — Rabuan','gdrive_folder_mentoring'=>'📁 Folder Drive — Mentoring','gdrive_folder_operasional'=>'📁 Folder Drive — Operasional'] as $key=>$label): ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                        <input type="text" name="<?= $key ?>" class="form-input"
                               value="<?= e($settings[$key]['value'] ?? '') ?>"
                               placeholder="Paste Folder ID di sini...">
                    </div>
                    <?php endforeach; ?>

                    <div class="alert alert-warning" style="font-size:.8rem; margin-top:.5rem;">
                        Pastikan folder sudah di-share ke email Service Account dengan akses <strong>Editor</strong>.
                    </div>
                </div>
            </div>

            <!-- Info Institusi -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🏫 Informasi Institusi</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Nama Institusi / Sekolah</label>
                        <input type="text" name="nama_institusi" class="form-input"
                               value="<?= e($settings['nama_institusi']['value'] ?? '') ?>"
                               placeholder="Nama sekolah atau organisasi">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tahun Ajaran Aktif</label>
                        <input type="text" name="tahun_ajaran" class="form-input"
                               value="<?= e($settings['tahun_ajaran']['value'] ?? '') ?>"
                               placeholder="Contoh: 2024/2025">
                    </div>

                    <!-- Status Kredensial -->
                    <div style="margin-top:1rem; padding:.875rem; background:var(--gray-50); border-radius:10px;">
                        <div style="font-size:.8rem; font-weight:700; color:var(--gray-600); margin-bottom:.5rem;">STATUS KREDENSIAL</div>
                        <?php
                        $credPath = BASE_PATH . '/credentials/service-account.json';
                        $credOk   = file_exists($credPath) && filesize($credPath) > 100;
                        ?>
                        <div style="display:flex; align-items:center; gap:.5rem; font-size:.8125rem;">
                            <?php if ($credOk): ?>
                                <span style="color:var(--success);">✓</span>
                                <span style="color:var(--success); font-weight:600;">service-account.json ditemukan</span>
                            <?php else: ?>
                                <span style="color:var(--danger);">✕</span>
                                <span style="color:var(--danger); font-weight:600;">service-account.json tidak ditemukan</span>
                                <div style="font-size:.75rem; color:var(--gray-400); margin-top:.25rem;">Letakkan file di <code>credentials/service-account.json</code></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
