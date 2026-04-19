<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db = getDB();
$id = (int)get('id');

$stmt = $db->prepare("SELECT b.*, u.nama AS created_by_nama FROM binjas b LEFT JOIN users u ON u.id = b.created_by WHERE b.id = ? LIMIT 1");
$stmt->bind_param('i', $id); $stmt->execute();
$binjas = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$binjas) { setFlash('error', 'Sesi tidak ditemukan.'); redirect(BASE_URL . '/modules/binjas/index.php'); }

$pageTitle = 'Hasil Binjas — ' . $binjas['nama_sesi'];

// Jenis latihan
$jenisLatihan = $db->query("SELECT * FROM binjas_jenis_latihan WHERE is_active=1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$jenisMap = array_column($jenisLatihan, null, 'id');

// Nilai semua siswa di sesi ini
$nStmt = $db->prepare(
    "SELECT bn.siswa_id, bn.jenis_latihan_id, bn.nilai, s.nama, s.nis, s.regu
     FROM binjas_nilai bn
     JOIN siswa s ON s.id = bn.siswa_id
     WHERE bn.binjas_id = ?
     ORDER BY s.regu, s.nama"
);
$nStmt->bind_param('i', $id); $nStmt->execute();
$rows = $nStmt->get_result()->fetch_all(MYSQLI_ASSOC); $nStmt->close();

// Susun per siswa
$siswaNilai = [];
foreach ($rows as $row) {
    $sid = $row['siswa_id'];
    if (!isset($siswaNilai[$sid])) {
        $siswaNilai[$sid] = ['nama'=>$row['nama'],'nis'=>$row['nis'],'regu'=>$row['regu'],'nilai'=>[],'lulus'=>0,'gagal'=>0];
    }
    $jid   = $row['jenis_latihan_id'];
    $jenis = $jenisMap[$jid] ?? null;
    $lulus = false;
    if ($jenis) {
        $lulus = $jenis['keterangan_arah'] === 'semakin_tinggi'
            ? $row['nilai'] >= $jenis['nilai_standar']
            : $row['nilai'] <= $jenis['nilai_standar'];
    }
    $siswaNilai[$sid]['nilai'][$jid] = ['nilai'=>$row['nilai'],'lulus'=>$lulus];
    if ($lulus) $siswaNilai[$sid]['lulus']++;
    else        $siswaNilai[$sid]['gagal']++;
}

// Presensi
$prStmt = $db->prepare("SELECT status, COUNT(*) AS total FROM presensi WHERE jenis_kegiatan='binjas' AND kegiatan_id=? GROUP BY status");
$prStmt->bind_param('i', $id); $prStmt->execute();
$presensiMap = [];
foreach ($prStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) $presensiMap[$p['status']] = $p['total'];
$prStmt->close();

// Rata-rata per jenis
$avgPerJenis = [];
foreach ($jenisLatihan as $jl) {
    $vals = array_filter(array_map(fn($s) => $s['nilai'][$jl['id']]['nilai'] ?? null, $siswaNilai), fn($v) => $v !== null);
    $avgPerJenis[$jl['id']] = count($vals) > 0 ? round(array_sum($vals)/count($vals), 2) : null;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title"><?= e($binjas['nama_sesi']) ?></h2>
            <p class="page-sub">Hasil Bina Jasmani · <?= formatTanggal($binjas['tanggal']) ?></p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=binjas&id=<?= $id ?>" class="btn btn-secondary">📋 Presensi</a>
            <a href="<?= BASE_URL ?>/modules/binjas/input-nilai.php?id=<?= $id ?>" class="btn btn-success">✏️ Edit Nilai</a>
            <a href="<?= BASE_URL ?>/modules/binjas/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Ringkasan atas -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.75rem; margin-bottom:1rem;">
        <div class="stat-card">
            <div class="stat-icon blue"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= count($siswaNilai) ?></div><div class="stat-label">Peserta Dinilai</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="stat-info">
                <div class="stat-value"><?= count(array_filter($siswaNilai, fn($s) => $s['gagal'] === 0 && $s['lulus'] > 0)) ?></div>
                <div class="stat-label">Lulus Semua Standar</div>
            </div>
        </div>
        <?php foreach (['hadir'=>['Hadir','var(--success)'],'alpha'=>['Alpha','var(--danger)']] as $key=>[$lbl,$color]): ?>
        <div class="stat-card">
            <div class="stat-icon <?= $key==='hadir'?'green':'red' ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <div class="stat-info"><div class="stat-value" style="color:<?= $color ?>;"><?= $presensiMap[$key] ?? 0 ?></div><div class="stat-label"><?= $lbl ?></div></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Rata-rata per jenis -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><span class="card-title">📊 Rata-rata vs Standar</span></div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.75rem;">
            <?php foreach ($jenisLatihan as $jl):
                $avg = $avgPerJenis[$jl['id']];
                $std = $jl['nilai_standar'];
                $pct = $avg !== null ? ($jl['keterangan_arah']==='semakin_tinggi' ? min(($avg/$std)*100,100) : min(($std/$avg)*100,100)) : 0;
                $color = $avg === null ? 'var(--gray-300)' : ($jl['keterangan_arah']==='semakin_tinggi' ? ($avg>=$std?'var(--success)':'var(--danger)') : ($avg<=$std?'var(--success)':'var(--danger)'));
            ?>
            <div style="background:var(--gray-50); border-radius:10px; padding:.875rem; text-align:center;">
                <div style="font-size:.8rem; font-weight:700; color:var(--gray-600); margin-bottom:.5rem;"><?= e($jl['nama']) ?></div>
                <div style="font-size:1.5rem; font-weight:800; color:<?= $color ?>;"><?= $avg ?? '—' ?></div>
                <div style="font-size:.7rem; color:var(--gray-400);"><?= e($jl['satuan'] ?? '') ?></div>
                <div style="margin:.5rem 0; height:6px; background:var(--gray-200); border-radius:999px;">
                    <div style="height:100%; width:<?= round($pct) ?>%; background:<?= $color ?>; border-radius:999px;"></div>
                </div>
                <div style="font-size:.7rem; color:var(--gray-400);">Standar: <?= $std ?> <?= e($jl['satuan'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tabel hasil per siswa -->
    <?php if (empty($siswaNilai)): ?>
        <div class="card"><div class="card-body">
            <div class="empty-state">
                <p>Belum ada nilai diinput. <a href="<?= BASE_URL ?>/modules/binjas/input-nilai.php?id=<?= $id ?>">Input Nilai Sekarang →</a></p>
            </div>
        </div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><span class="card-title">📋 Hasil Per Siswa</span></div>
        <div class="table-responsive">
            <table class="table" style="font-size:.8125rem;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Siswa</th>
                        <th>Regu</th>
                        <?php foreach ($jenisLatihan as $jl): ?>
                        <th style="text-align:center;"><?= e($jl['nama']) ?><br><span style="font-weight:400; color:var(--gray-400);">(Std: <?= $jl['nilai_standar'] ?>)</span></th>
                        <?php endforeach; ?>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($siswaNilai as $sid => $sData): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/modules/siswa/detail.php?id=<?= $sid ?>" style="font-weight:600; color:var(--primary); text-decoration:none;"><?= e($sData['nama']) ?></a><br>
                        <code style="font-size:.7rem;"><?= e($sData['nis']) ?></code>
                    </td>
                    <td><?= e($sData['regu'] ?? '—') ?></td>
                    <?php foreach ($jenisLatihan as $jl):
                        $nilaiData = $sData['nilai'][$jl['id']] ?? null;
                        $bg = !$nilaiData ? '' : ($nilaiData['lulus'] ? 'background:var(--success-light);' : 'background:var(--danger-light);');
                    ?>
                    <td style="text-align:center; <?= $bg ?>">
                        <?php if ($nilaiData): ?>
                            <strong><?= $nilaiData['nilai'] ?></strong> <?= e($jl['satuan'] ?? '') ?>
                            <br><span style="font-size:.7rem; color:<?= $nilaiData['lulus']?'var(--success)':'var(--danger)' ?>;"><?= $nilaiData['lulus']?'✓ Lulus':'✗ Belum' ?></span>
                        <?php else: ?>
                            <span style="color:var(--gray-300);">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td style="text-align:center;">
                        <?php if ($sData['lulus'] + $sData['gagal'] === 0): ?>
                            <span class="badge badge-secondary">Belum dinilai</span>
                        <?php elseif ($sData['gagal'] === 0): ?>
                            <span class="badge badge-success">✓ Lulus Semua</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?= $sData['gagal'] ?> Belum Lulus</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
