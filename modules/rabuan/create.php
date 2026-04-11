<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$error = '';
$pageTitle = 'Buat Rabuan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $judul  = post('judul');
        $tgl    = post('tanggal');
        $mulai  = post('waktu_mulai')  ?: null;
        $selesai= post('waktu_selesai')?: null;
        $lokasi = post('lokasi')       ?: null;
        $agenda = post('agenda')       ?: null;
        $status = post('status') ?: 'draft';
        $me     = currentUser();

        if (empty($judul) || empty($tgl)) {
            $error = 'Judul dan Tanggal wajib diisi.';
        } else {
            $stmt = $db->prepare(
                "INSERT INTO rabuan (judul, tanggal, waktu_mulai, waktu_selesai, lokasi, agenda, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssssssi', $judul, $tgl, $mulai, $selesai, $lokasi, $agenda, $status, $me['id']);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            setFlash('success', 'Rabuan berhasil dibuat.');
            redirect(BASE_URL . '/modules/rabuan/detail.php?id=' . $newId);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Buat Rabuan</h2>
            <p class="page-sub">Jadwalkan rapat rutin baru</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Judul Rapat <span class="required">*</span></label>
                    <input type="text" name="judul" class="form-input" value="<?= e(post('judul')) ?>" required placeholder="Contoh: Rabuan Mingguan — Pekan ke-1">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-input" value="<?= e(post('tanggal')) ?>" required>
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
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" class="form-input" value="<?= e(post('waktu_mulai')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Selesai</label>
                        <input type="time" name="waktu_selesai" class="form-input" value="<?= e(post('waktu_selesai')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-input" value="<?= e(post('lokasi')) ?>" placeholder="Nama tempat pelaksanaan">
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda</label>
                    <textarea name="agenda" class="form-textarea" rows="4" placeholder="Daftar agenda rapat..."><?= e(post('agenda')) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Rabuan</button>
                    <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
