<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$error = '';
$pageTitle = 'Buat Kegiatan Operasional';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nama    = post('nama_kegiatan');
        $lokasi  = post('lokasi')        ?: null;
        $tglMul  = post('tanggal_mulai');
        $tglSel  = post('tanggal_selesai') ?: null;
        $status  = post('status') ?: 'draft';
        $me      = currentUser();

        if (empty($nama) || empty($tglMul)) {
            $error = 'Nama Kegiatan dan Tanggal Mulai wajib diisi.';
        } else {
            $stmt = $db->prepare(
                "INSERT INTO operasional (nama_kegiatan, lokasi, tanggal_mulai, tanggal_selesai, fase, status, created_by)
                 VALUES (?, ?, ?, ?, 'pra', ?, ?)"
            );
            $stmt->bind_param('sssssi', $nama, $lokasi, $tglMul, $tglSel, $status, $me['id']);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();

            // Buat record pra-operasional kosong
            $praStmt = $db->prepare("INSERT INTO operasional_pra (operasional_id) VALUES (?)");
            $praStmt->bind_param('i', $newId);
            $praStmt->execute(); $praStmt->close();

            setFlash('success', 'Kegiatan operasional berhasil dibuat. Silakan lengkapi data Pra-Operasional.');
            redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $newId);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Buat Kegiatan Operasional</h2>
            <p class="page-sub">Kegiatan akan dimulai dari fase Pra-Operasional</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/operasional/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card" style="max-width:620px;">
        <div class="card-body">
            <!-- Alur Fase -->
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1.5rem; padding:.875rem; background:var(--primary-light); border-radius:10px; font-size:.8125rem;">
                <span style="background:var(--primary); color:#fff; padding:.2rem .6rem; border-radius:999px; font-weight:700;">1</span>
                <span style="font-weight:600; color:var(--primary);">Pra-Operasional</span>
                <span style="color:var(--gray-400);">→</span>
                <span style="color:var(--gray-400);">2. Operasional</span>
                <span style="color:var(--gray-400);">→</span>
                <span style="color:var(--gray-400);">3. Pasca-Operasional</span>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Nama Kegiatan <span class="required">*</span></label>
                    <input type="text" name="nama_kegiatan" class="form-input" value="<?= e(post('nama_kegiatan')) ?>" required placeholder="Contoh: Operasi Bersih Pantai Losari">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-input" value="<?= e(post('tanggal_mulai')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-input" value="<?= e(post('tanggal_selesai')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-input" value="<?= e(post('lokasi')) ?>" placeholder="Lokasi kegiatan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'] as $val=>$lbl): ?>
                                <option value="<?= $val ?>" <?= post('status','draft')===$val?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Buat & Mulai Pra-Operasional</button>
                    <a href="<?= BASE_URL ?>/modules/operasional/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
