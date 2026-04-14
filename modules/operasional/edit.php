<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$id    = (int)get('id');
$error = '';
$pageTitle = 'Edit Operasional';

$stmt = $db->prepare("SELECT * FROM operasional WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$ops = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ops) { setFlash('error', 'Data tidak ditemukan.'); redirect(BASE_URL . '/modules/operasional/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nama   = post('nama_kegiatan');
        $lokasi = post('lokasi')          ?: null;
        $tglMul = post('tanggal_mulai');
        $tglSel = post('tanggal_selesai') ?: null;
        $status = post('status');

        if (empty($nama) || empty($tglMul)) {
            $error = 'Nama Kegiatan dan Tanggal Mulai wajib diisi.';
        } else {
            $stmt = $db->prepare("UPDATE operasional SET nama_kegiatan=?, lokasi=?, tanggal_mulai=?, tanggal_selesai=?, status=? WHERE id=?");
            $stmt->bind_param('sssssi', $nama, $lokasi, $tglMul, $tglSel, $status, $id);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Data kegiatan diperbarui.');
            redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div><h2 class="page-title">Edit Operasional</h2><p class="page-sub"><?= e($ops['nama_kegiatan']) ?></p></div>
        <a href="<?= BASE_URL ?>/modules/operasional/detail.php?id=<?= $id ?>" class="btn btn-secondary">← Kembali</a>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <div class="card" style="max-width:620px;"><div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Nama Kegiatan <span class="required">*</span></label>
                <input type="text" name="nama_kegiatan" class="form-input" value="<?= e($ops['nama_kegiatan']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-input" value="<?= e($ops['tanggal_mulai']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-input" value="<?= e($ops['tanggal_selesai'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-input" value="<?= e($ops['lokasi'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'] as $val=>$lbl): ?>
                            <option value="<?= $val ?>" <?= $ops['status']===$val?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="<?= BASE_URL ?>/modules/operasional/detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
