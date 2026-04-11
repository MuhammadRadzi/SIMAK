<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$id    = (int)get('id');
$error = '';
$pageTitle = 'Edit Rabuan';

$stmt = $db->prepare("SELECT * FROM rabuan WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$rabuan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rabuan) {
    setFlash('error', 'Data Rabuan tidak ditemukan.');
    redirect(BASE_URL . '/modules/rabuan/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $judul   = post('judul');
        $tgl     = post('tanggal');
        $mulai   = post('waktu_mulai')   ?: null;
        $selesai = post('waktu_selesai') ?: null;
        $lokasi  = post('lokasi')        ?: null;
        $agenda  = post('agenda')        ?: null;
        $status  = post('status');

        if (empty($judul) || empty($tgl)) {
            $error = 'Judul dan Tanggal wajib diisi.';
        } else {
            $stmt = $db->prepare(
                "UPDATE rabuan SET judul=?, tanggal=?, waktu_mulai=?, waktu_selesai=?, lokasi=?, agenda=?, status=? WHERE id=?"
            );
            $stmt->bind_param('sssssssi', $judul, $tgl, $mulai, $selesai, $lokasi, $agenda, $status, $id);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Data Rabuan berhasil diperbarui.');
            redirect(BASE_URL . '/modules/rabuan/detail.php?id=' . $id);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Edit Rabuan</h2>
            <p class="page-sub"><?= e($rabuan['judul']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $id ?>" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Judul Rapat <span class="required">*</span></label>
                    <input type="text" name="judul" class="form-input" value="<?= e($rabuan['judul']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-input" value="<?= e($rabuan['tanggal']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'] as $val=>$lbl): ?>
                                <option value="<?= $val ?>" <?= $rabuan['status']===$val?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" class="form-input" value="<?= e($rabuan['waktu_mulai'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Selesai</label>
                        <input type="time" name="waktu_selesai" class="form-input" value="<?= e($rabuan['waktu_selesai'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-input" value="<?= e($rabuan['lokasi'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda</label>
                    <textarea name="agenda" class="form-textarea" rows="4"><?= e($rabuan['agenda'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
