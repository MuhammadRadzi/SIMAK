<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db    = getDB();
$id    = (int)get('id');
$flash = getFlash();
$error = '';

$stmt = $db->prepare("SELECT * FROM binjas WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id); $stmt->execute();
$binjas = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$binjas) { setFlash('error', 'Sesi tidak ditemukan.'); redirect(BASE_URL . '/modules/binjas/index.php'); }

$pageTitle = 'Input Nilai — ' . $binjas['nama_sesi'];

// Handle save nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save_nilai') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nilaiArr = $_POST['nilai'] ?? [];
        $me       = currentUser();
        $saved    = 0;

        foreach ($nilaiArr as $siswaId => $jenisArr) {
            foreach ($jenisArr as $jenisId => $nilai) {
                $nilai = trim($nilai);
                if ($nilai === '' || !is_numeric($nilai)) continue;
                $nilaiFloat = (float)$nilai;

                // Upsert
                $stmt = $db->prepare(
                    "INSERT INTO binjas_nilai (binjas_id, siswa_id, jenis_latihan_id, nilai, input_by)
                     VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE nilai=VALUES(nilai), input_by=VALUES(input_by), input_at=NOW()"
                );
                $stmt->bind_param('iiidi', $id, $siswaId, $jenisId, $nilaiFloat, $me['id']);
                $stmt->execute(); $stmt->close();
                $saved++;
            }
        }
        setFlash('success', "$saved nilai berhasil disimpan.");
        redirect(BASE_URL . '/modules/binjas/input-nilai.php?id=' . $id);
    }
}

// Data jenis latihan
$jenisLatihan = $db->query("SELECT * FROM binjas_jenis_latihan WHERE is_active=1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Data siswa aktif
$allSiswa = $db->query("SELECT id, nama, nis, regu FROM siswa WHERE is_active=1 ORDER BY regu, nama")->fetch_all(MYSQLI_ASSOC);

// Nilai yang sudah ada
$existingNilai = [];
$nStmt = $db->prepare("SELECT siswa_id, jenis_latihan_id, nilai FROM binjas_nilai WHERE binjas_id = ?");
$nStmt->bind_param('i', $id); $nStmt->execute();
foreach ($nStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $existingNilai[$row['siswa_id']][$row['jenis_latihan_id']] = $row['nilai'];
}
$nStmt->close();

// Group siswa per regu
$siswaPerRegu = [];
foreach ($allSiswa as $s) {
    $regu = $s['regu'] ?: 'Tanpa Regu';
    $siswaPerRegu[$regu][] = $s;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Input Nilai Binjas</h2>
            <p class="page-sub"><?= e($binjas['nama_sesi']) ?> · <?= formatTanggal($binjas['tanggal']) ?></p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/binjas/detail.php?id=<?= $id ?>" class="btn btn-secondary">Lihat Hasil</a>
            <a href="<?= BASE_URL ?>/modules/binjas/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <!-- Legend standar -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <div style="font-size:.8rem; font-weight:700; color:var(--gray-600); margin-bottom:.5rem;">📏 NILAI STANDARISASI</div>
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                <?php foreach ($jenisLatihan as $jl): ?>
                <div style="background:var(--gray-50); border-radius:8px; padding:.5rem .75rem; font-size:.8rem;">
                    <strong><?= e($jl['nama']) ?></strong>
                    <span style="color:var(--gray-500); margin-left:.25rem;">Standar: <?= $jl['nilai_standar'] ?> <?= e($jl['satuan'] ?? '') ?></span>
                    <span style="color:var(--info); margin-left:.25rem;">(<?= $jl['keterangan_arah'] === 'semakin_tinggi' ? '↑ Lebih tinggi = lebih baik' : '↓ Lebih rendah = lebih baik' ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_nilai">

        <?php foreach ($siswaPerRegu as $reguNama => $siswaList): ?>
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header">
                <span class="card-title">👥 Regu: <?= e($reguNama) ?></span>
                <span style="font-size:.8rem; color:var(--gray-400);"><?= count($siswaList) ?> siswa</span>
            </div>
            <div class="table-responsive">
                <table class="table" style="font-size:.8125rem;">
                    <thead>
                        <tr>
                            <th style="min-width:160px;">Nama Siswa</th>
                            <?php foreach ($jenisLatihan as $jl): ?>
                            <th style="min-width:110px; text-align:center;">
                                <?= e($jl['nama']) ?><br>
                                <span style="font-size:.7rem; color:var(--gray-400); font-weight:400;"><?= e($jl['satuan'] ?? '') ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($siswaList as $s): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= e($s['nama']) ?></div>
                            <div style="font-size:.7rem; color:var(--gray-400);"><?= e($s['nis']) ?></div>
                        </td>
                        <?php foreach ($jenisLatihan as $jl):
                            $existing = $existingNilai[$s['id']][$jl['id']] ?? '';
                            $isLulus  = '';
                            if ($existing !== '') {
                                $lulus = $jl['keterangan_arah'] === 'semakin_tinggi'
                                    ? (float)$existing >= (float)$jl['nilai_standar']
                                    : (float)$existing <= (float)$jl['nilai_standar'];
                                $isLulus = $lulus ? 'var(--success-light)' : 'var(--danger-light)';
                            }
                        ?>
                        <td style="text-align:center; background:<?= $isLulus ?>;">
                            <input type="number" step="0.01" min="0"
                                   name="nilai[<?= $s['id'] ?>][<?= $jl['id'] ?>]"
                                   class="form-input"
                                   style="width:90px; text-align:center; padding:.35rem .5rem; font-size:.875rem;"
                                   value="<?= e((string)$existing) ?>"
                                   placeholder="—">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="position:sticky; bottom:1rem; background:#fff; padding:.875rem; border-radius:10px; box-shadow:var(--shadow-lg); display:flex; gap:.75rem; align-items:center;">
            <button type="submit" class="btn btn-primary">💾 Simpan Semua Nilai</button>
            <span style="font-size:.8rem; color:var(--gray-400);">
                🟢 Hijau = Lulus standar &nbsp; 🔴 Merah = Belum lulus &nbsp; (Otomatis terhitung setelah disimpan)
            </span>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
