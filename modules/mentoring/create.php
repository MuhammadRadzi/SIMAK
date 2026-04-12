<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$error = '';
$pageTitle = 'Buat Sesi Mentoring';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $judul   = post('judul_materi');
        $mentor  = post('nama_mentor');
        $tgl     = post('tanggal');
        $mulai   = post('waktu_mulai')   ?: null;
        $selesai = post('waktu_selesai') ?: null;
        $lokasi  = post('lokasi')        ?: null;
        $logistik= post('catatan_logistik') ?: null;
        $status  = post('status') ?: 'draft';
        $me      = currentUser();

        if (empty($judul) || empty($mentor) || empty($tgl)) {
            $error = 'Judul Materi, Nama Mentor, dan Tanggal wajib diisi.';
        } else {
            $stmt = $db->prepare(
                "INSERT INTO mentoring (judul_materi, nama_mentor, tanggal, waktu_mulai, waktu_selesai, lokasi, catatan_logistik, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssssssi', $judul, $mentor, $tgl, $mulai, $selesai, $lokasi, $logistik, $status, $me['id']);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            setFlash('success', 'Sesi mentoring berhasil dibuat.');
            redirect(BASE_URL . '/modules/mentoring/detail.php?id=' . $newId);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Buat Sesi Mentoring</h2>
            <p class="page-sub">Jadwalkan sesi mentoring baru</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/mentoring/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">Judul Materi <span class="required">*</span></label>
                    <input type="text" name="judul_materi" class="form-input" value="<?= e(post('judul_materi')) ?>" required placeholder="Contoh: Manajemen Waktu & Produktivitas">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Mentor / Tutor <span class="required">*</span></label>
                        <input type="text" name="nama_mentor" class="form-input" value="<?= e(post('nama_mentor')) ?>" required placeholder="Nama pengisi materi">
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
                        <label class="form-label">Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-input" value="<?= e(post('tanggal')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi</label>
                        <input type="text" name="lokasi" class="form-input" value="<?= e(post('lokasi')) ?>" placeholder="Tempat pelaksanaan">
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
                    <label class="form-label">Catatan Kebutuhan Logistik</label>
                    <textarea name="catatan_logistik" class="form-textarea" rows="3"
                        placeholder="Contoh: Proyektor, spidol whiteboard, sound system, snack 30 orang..."><?= e(post('catatan_logistik')) ?></textarea>
                    <small class="form-hint">Catat kebutuhan alat/perlengkapan selama sesi berlangsung.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Mentoring</button>
                    <a href="<?= BASE_URL ?>/modules/mentoring/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
