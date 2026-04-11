<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db = getDB();
$id = (int)get('id');

$stmt = $db->prepare("SELECT * FROM siswa WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    setFlash('error', 'Data siswa tidak ditemukan.');
    redirect(BASE_URL . '/modules/siswa/index.php');
}

$pageTitle = 'Detail Siswa — ' . $siswa['nama'];

// Riwayat kehadiran siswa ini
$riwayatPresensi = $db->prepare(
    "SELECT p.jenis_kegiatan, p.status, p.dicatat_pada,
            CASE p.jenis_kegiatan
                WHEN 'rabuan'    THEN r.judul
                WHEN 'mentoring' THEN m.judul_materi
                WHEN 'binjas'    THEN b.nama_sesi
            END AS nama_kegiatan
     FROM presensi p
     LEFT JOIN rabuan    r ON r.id = p.kegiatan_id AND p.jenis_kegiatan = 'rabuan'
     LEFT JOIN mentoring m ON m.id = p.kegiatan_id AND p.jenis_kegiatan = 'mentoring'
     LEFT JOIN binjas    b ON b.id = p.kegiatan_id AND p.jenis_kegiatan = 'binjas'
     WHERE p.siswa_id = ?
     ORDER BY p.dicatat_pada DESC
     LIMIT 10"
);
$riwayatPresensi->bind_param('i', $id);
$riwayatPresensi->execute();
$presensiList = $riwayatPresensi->get_result()->fetch_all(MYSQLI_ASSOC);
$riwayatPresensi->close();

// Statistik kehadiran
$statStmt = $db->prepare(
    "SELECT status, COUNT(*) AS total FROM presensi WHERE siswa_id = ? GROUP BY status"
);
$statStmt->bind_param('i', $id);
$statStmt->execute();
$statRows = $statStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$statStmt->close();
$statMap = [];
foreach ($statRows as $r) $statMap[$r['status']] = $r['total'];
$totalPresensi = array_sum($statMap);

// Nilai Binjas terbaru
$binjasNilai = $db->prepare(
    "SELECT bn.nilai, bn.input_at, jl.nama AS jenis, jl.satuan, jl.nilai_standar, jl.keterangan_arah, b.nama_sesi
     FROM binjas_nilai bn
     JOIN binjas_jenis_latihan jl ON jl.id = bn.jenis_latihan_id
     JOIN binjas b ON b.id = bn.binjas_id
     WHERE bn.siswa_id = ?
     ORDER BY bn.input_at DESC LIMIT 10"
);
$binjasNilai->bind_param('i', $id);
$binjasNilai->execute();
$nilaiList = $binjasNilai->get_result()->fetch_all(MYSQLI_ASSOC);
$binjasNilai->close();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Detail Siswa</h2>
            <p class="page-sub">Profil lengkap <?= e($siswa['nama']) ?></p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/siswa/edit.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="<?= BASE_URL ?>/modules/siswa/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:1rem; align-items:start;">

        <!-- Profil Card -->
        <div class="card">
            <div class="card-body" style="text-align:center; padding:1.75rem 1.25rem;">
                <div style="width:80px; height:80px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; margin:0 auto 1rem;">
                    <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                </div>
                <h3 style="font-size:1.1rem; font-weight:800; color:var(--gray-900); margin-bottom:.25rem;"><?= e($siswa['nama']) ?></h3>
                <code style="font-size:.8rem; color:var(--gray-500);"><?= e($siswa['nis']) ?></code>
                <div style="margin-top:.75rem; display:flex; flex-direction:column; gap:.5rem; text-align:left;">
                    <?php
                    $infos = [
                        ['JK',         $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'],
                        ['Tgl Lahir',  $siswa['tanggal_lahir'] ? formatTanggal($siswa['tanggal_lahir']) : '—'],
                        ['Regu',       $siswa['regu'] ?? '—'],
                        ['Angkatan',   $siswa['angkatan'] ?? '—'],
                        ['No. HP',     $siswa['no_hp'] ?? '—'],
                    ];
                    foreach ($infos as [$label, $val]):
                    ?>
                    <div style="display:flex; justify-content:space-between; font-size:.8125rem; padding:.3rem 0; border-bottom:1px solid var(--gray-100);">
                        <span style="color:var(--gray-500);"><?= $label ?></span>
                        <span style="color:var(--gray-800); font-weight:500;"><?= e((string)$val) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($siswa['alamat']): ?>
                    <div style="font-size:.8rem; color:var(--gray-500); margin-top:.25rem;"><?= e($siswa['alamat']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kanan -->
        <div style="display:flex; flex-direction:column; gap:1rem;">

            <!-- Statistik Kehadiran -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Statistik Kehadiran</span>
                    <span style="font-size:.8rem; color:var(--gray-400);">Total <?= $totalPresensi ?> data</span>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; text-align:center;">
                        <?php
                        $statItems = [
                            ['hadir', 'Hadir', 'var(--success)'],
                            ['izin',  'Izin',  'var(--warning)'],
                            ['sakit', 'Sakit', 'var(--info)'],
                            ['alpha', 'Alpha', 'var(--danger)'],
                        ];
                        foreach ($statItems as [$key, $label, $color]):
                            $val = $statMap[$key] ?? 0;
                            $pct = $totalPresensi > 0 ? round($val/$totalPresensi*100) : 0;
                        ?>
                        <div style="padding:.75rem; background:var(--gray-50); border-radius:10px;">
                            <div style="font-size:1.5rem; font-weight:800; color:<?= $color ?>;"><?= $val ?></div>
                            <div style="font-size:.75rem; color:var(--gray-500);"><?= $label ?></div>
                            <div style="font-size:.7rem; color:var(--gray-400);"><?= $pct ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Riwayat Kehadiran -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📋 Riwayat Kehadiran Terbaru</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Kegiatan</th><th>Jenis</th><th>Status</th><th>Tanggal</th></tr></thead>
                        <tbody>
                        <?php if (empty($presensiList)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat</td></tr>
                        <?php else: ?>
                            <?php foreach ($presensiList as $p): ?>
                            <tr>
                                <td><?= e($p['nama_kegiatan'] ?? '—') ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst($p['jenis_kegiatan']) ?></span></td>
                                <td><?= badgeKehadiran($p['status']) ?></td>
                                <td><?= formatTanggal($p['dicatat_pada'], 'd M Y') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Nilai Binjas -->
            <?php if (!empty($nilaiList)): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">💪 Nilai Bina Jasmani Terbaru</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Jenis</th><th>Nilai</th><th>Standar</th><th>Status</th><th>Sesi</th></tr></thead>
                        <tbody>
                        <?php foreach ($nilaiList as $n):
                            $lulus = ($n['keterangan_arah'] === 'semakin_tinggi')
                                ? $n['nilai'] >= $n['nilai_standar']
                                : $n['nilai'] <= $n['nilai_standar'];
                        ?>
                        <tr>
                            <td><?= e($n['jenis']) ?></td>
                            <td><strong><?= $n['nilai'] ?></strong> <?= e($n['satuan'] ?? '') ?></td>
                            <td><?= $n['nilai_standar'] ?> <?= e($n['satuan'] ?? '') ?></td>
                            <td><?= $lulus
                                ? '<span class="badge badge-success">Lulus</span>'
                                : '<span class="badge badge-danger">Belum Lulus</span>'
                            ?></td>
                            <td style="font-size:.8rem; color:var(--gray-500);"><?= e($n['nama_sesi']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
