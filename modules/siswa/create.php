<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$error = '';
$pageTitle = 'Tambah Siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nis    = post('nis');
        $nama   = post('nama');
        $jk     = post('jenis_kelamin');
        $tgl    = post('tanggal_lahir');
        $regu   = post('regu');
        $angk   = post('angkatan');
        $hp     = post('no_hp');
        $alamat = post('alamat');

        if (empty($nis) || empty($nama) || empty($jk)) {
            $error = 'NIS, Nama, dan Jenis Kelamin wajib diisi.';
        } else {
            // Cek NIS duplikat
            $chk = $db->prepare("SELECT id FROM siswa WHERE nis = ?");
            $chk->bind_param('s', $nis);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = 'NIS sudah digunakan.';
            } else {
                $tgl    = !empty($tgl) ? $tgl : null;
                $angk   = !empty($angk) ? (int)$angk : null;
                $regu   = !empty($regu) ? $regu : null;
                $hp     = !empty($hp) ? $hp : null;
                $alamat = !empty($alamat) ? $alamat : null;

                $stmt = $db->prepare(
                    "INSERT INTO siswa (nis, nama, jenis_kelamin, tanggal_lahir, regu, angkatan, no_hp, alamat)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sssssiis', $nis, $nama, $jk, $tgl, $regu, $angk, $hp, $alamat);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Siswa ' . $nama . ' berhasil ditambahkan.');
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
            <h2 class="page-title">Tambah Siswa</h2>
            <p class="page-sub">Daftarkan siswa baru ke sistem</p>
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
                        <input type="text" name="nis" class="form-input" value="<?= e(post('nis')) ?>" required placeholder="Nomor Induk Siswa">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="L" <?= post('jenis_kelamin') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= post('jenis_kelamin') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" class="form-input" value="<?= e(post('nama')) ?>" required placeholder="Nama lengkap siswa">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-input" value="<?= e(post('tanggal_lahir')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Angkatan</label>
                        <input type="number" name="angkatan" class="form-input" value="<?= e(post('angkatan')) ?>" placeholder="Contoh: 2023" min="2000" max="2099">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Regu / Kelompok</label>
                        <input type="text" name="regu" class="form-input" value="<?= e(post('regu')) ?>" placeholder="Nama regu">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP / WA</label>
                        <input type="text" name="no_hp" class="form-input" value="<?= e(post('no_hp')) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-textarea" rows="3" placeholder="Alamat lengkap siswa"><?= e(post('alamat')) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Siswa</button>
                    <a href="<?= BASE_URL ?>/modules/siswa/index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
