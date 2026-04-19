<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db = getDB(); $id = (int)get('id'); $error = ''; $pageTitle = 'Edit Sesi Binjas';

$stmt = $db->prepare("SELECT * FROM binjas WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id); $stmt->execute();
$binjas = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$binjas) { setFlash('error', 'Sesi tidak ditemukan.'); redirect(BASE_URL . '/modules/binjas/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nama    = post('nama_sesi');
        $tgl     = post('tanggal');
        $mulai   = post('waktu_mulai')   ?: null;
        $selesai = post('waktu_selesai') ?: null;
        $lokasi  = post('lokasi')        ?: null;
        $status  = post('status');

        if (empty($nama) || empty($tgl)) { $error = 'Nama Sesi dan Tanggal wajib diisi.'; }
        else {
            $stmt = $db->prepare("UPDATE binjas SET nama_sesi=?, tanggal=?, waktu_mulai=?, waktu_selesai=?, lokasi=?, status=? WHERE id=?");
            $stmt->bind_param('ssssssi', $nama, $tgl, $mulai, $selesai, $lokasi, $status, $id);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Sesi Binjas berhasil diperbarui.');
            redirect(BASE_URL . '/modules/binjas/detail.php?id=' . $id);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div><h2 class="page-title">Edit Sesi Binjas</h2><p class="page-sub"><?= e($binjas['nama_sesi']) ?></p></div>
        <a href="<?= BASE_URL ?>/modules/binjas/detail.php?id=<?= $id ?>" class="btn btn-secondary">← Kembali</a>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <div class="card" style="max-width:620px;"><div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Nama Sesi <span class="required">*</span></label>
                <input type="text" name="nama_sesi" class="form-input" value="<?= e($binjas['nama_sesi']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" class="form-input" value="<?= e($binjas['tanggal']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $binjas['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai" class="form-input" value="<?= e($binjas['waktu_mulai'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai" class="form-input" value="<?= e($binjas['waktu_selesai'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text" name="lokasi" class="form-input" value="<?= e($binjas['lokasi'] ?? '') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="<?= BASE_URL ?>/modules/binjas/detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
