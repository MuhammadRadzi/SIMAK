<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Import Siswa CSV';
$error     = '';
$results   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Gagal upload file.';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // Skip header row

        $imported = 0; $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) { $skipped++; continue; }
            [$nis, $nama, $jk, $tgl, $regu, $angk, $hp] = array_pad($row, 7, '');

            $nis  = trim($nis);
            $nama = trim($nama);
            $jk   = strtoupper(trim($jk));
            if (empty($nis) || empty($nama) || !in_array($jk, ['L','P'])) { $skipped++; continue; }

            $chk = $db->prepare("SELECT id FROM siswa WHERE nis = ?");
            $chk->bind_param('s', $nis); $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) { $skipped++; $chk->close(); continue; }
            $chk->close();

            $tgl  = !empty(trim($tgl))  ? trim($tgl)  : null;
            $regu = !empty(trim($regu)) ? trim($regu) : null;
            $angk = !empty(trim($angk)) ? (int)trim($angk) : null;
            $hp   = !empty(trim($hp))   ? trim($hp)   : null;

            $stmt = $db->prepare("INSERT INTO siswa (nis, nama, jenis_kelamin, tanggal_lahir, regu, angkatan, no_hp) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssi', $nis, $nama, $jk, $tgl, $regu, $angk, $hp);
            $stmt->execute(); $stmt->close();
            $imported++;
        }
        fclose($handle);
        setFlash('success', "$imported siswa berhasil diimport, $skipped baris dilewati.");
        redirect(BASE_URL . '/modules/siswa/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Import Siswa CSV</h2>
            <p class="page-sub">Upload file CSV untuk menambahkan banyak siswa sekaligus</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/siswa/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:start;">
        <div class="card">
            <div class="card-header"><span class="card-title">Upload File CSV</span></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Pilih File CSV <span class="required">*</span></label>
                        <input type="file" name="csv_file" class="form-input" accept=".csv" required>
                        <small class="form-hint">Maksimal 5MB. Format: CSV dengan header.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Sekarang</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">📋 Format CSV</span></div>
            <div class="card-body">
                <p style="font-size:.875rem; color:var(--gray-600); margin-bottom:.75rem;">File CSV harus memiliki kolom berikut (baris pertama = header):</p>
                <div style="background:var(--gray-50); border-radius:8px; padding:.875rem; font-size:.8rem; font-family:monospace; color:var(--gray-700); overflow-x:auto;">
                    nis,nama,jenis_kelamin,tanggal_lahir,regu,angkatan,no_hp<br>
                    2024001,Ahmad Fauzi,L,2007-05-12,Alpha,2024,081234567890<br>
                    2024002,Siti Rahayu,P,2007-08-20,Bravo,2024,089876543210
                </div>
                <ul style="font-size:.8125rem; color:var(--gray-500); margin-top:.75rem; padding-left:1.25rem;">
                    <li><strong>nis</strong> — wajib, harus unik</li>
                    <li><strong>nama</strong> — wajib</li>
                    <li><strong>jenis_kelamin</strong> — wajib, isi <code>L</code> atau <code>P</code></li>
                    <li><strong>tanggal_lahir</strong> — opsional, format YYYY-MM-DD</li>
                    <li><strong>regu, angkatan, no_hp</strong> — opsional</li>
                </ul>
                <a href="<?= BASE_URL ?>/assets/template-siswa.csv" class="btn btn-sm btn-secondary" style="margin-top:.75rem;">
                    ⬇ Download Template CSV
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
