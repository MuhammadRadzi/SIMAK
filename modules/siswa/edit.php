<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$id    = (int)get('id');
$error = '';
$pageTitle = 'Edit Siswa';

$stmt = $db->prepare("SELECT * FROM siswa WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    setFlash('error', 'Data siswa tidak ditemukan.');
    redirect(BASE_URL . '/modules/siswa/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nis    = post('nis');
        $nama   = post('nama');
        $jk     = post('jenis_kelamin');
        $tgl    = post('tanggal_lahir') ?: null;
        $regu   = post('regu') ?: null;
        $angk   = post('angkatan') ? (int)post('angkatan') : null;
        $hp     = post('no_hp') ?: null;
        $alamat = post('alamat') ?: null;

        if (empty($nis) || empty($nama) || empty($jk)) {
            $error = 'NIS, Nama, dan Jenis Kelamin wajib diisi.';
        } else {
            // Cek NIS duplikat (kecuali diri sendiri)
            $chk = $db->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
            $chk->bind_param('si', $nis, $id);
            $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = 'NIS sudah digunakan siswa lain.';
            } else {
                $stmt = $db->prepare(
                    "UPDATE siswa SET nis=?, nama=?, jenis_kelamin=?, tanggal_lahir=?,
                     regu=?, angkatan=?, no_hp=?, alamat=? WHERE id=?"
                );
                $stmt->bind_param('sssssiisi', $nis, $nama, $jk, $tgl, $regu, $angk, $hp, $alamat, $id);
                $stmt->execute(); $stmt->close();
                setFlash('success', 'Data siswa berhasil diperbarui.');
                redirect(BASE_URL . '/modules/siswa/index.php');
            }
            $chk->close();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Edit Siswa</h2>
            <p class="page-sub"><?= e($siswa['nama']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/modules/siswa/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">NIS <span class="required">*</span></label>
                        <input type="text" name="nis" class="form-input" value="<?= e($siswa['nis']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="L" <?= $siswa['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $siswa['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" class="form-input" value="<?= e($siswa['nama']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-input" value="<?= e($siswa['tanggal_lahir'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Angkatan</label>
                        <input type="number" name="angkatan" class="form-input" value="<?= e($siswa['angkatan'] ?? '') ?>" min="2000" max="2099">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Regu / Kelompok</label>
                        <input type="text" name="regu" class="form-input" value="<?= e($siswa['regu'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP / WA</label>
                        <input type="text" name="no_hp" class="form-input" value="<?= e($siswa['no_hp'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-textarea" rows="3"><?= e($siswa['alamat'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?= BASE_URL ?>/modules/siswa/detail.php?id=<?= $id ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
