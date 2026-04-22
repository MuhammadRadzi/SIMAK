<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Rekap Kehadiran Siswa';

$bulan    = get('bulan', date('Y-m'));
$jenisFilter = get('jenis', '');
$reguFilter  = get('regu', '');

// Semua siswa aktif
$where = ['s.is_active = 1'];
$params = []; $types = '';
if (!empty($reguFilter)) {
    $where[] = 's.regu = ?';
    $params[] = $reguFilter; $types .= 's';
}
$whereStr = 'WHERE ' . implode(' AND ', $where);
$stmt = $db->prepare("SELECT id, nama, nis, regu FROM siswa s $whereStr ORDER BY s.regu, s.nama");
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$siswaList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Presensi bulan ini per siswa
$jenisTarget = !empty($jenisFilter) ? [$jenisFilter] : ['rabuan','mentoring','binjas'];
$presensiData = [];
foreach ($jenisTarget as $jenis) {
    $pStmt = $db->prepare(
        "SELECT p.siswa_id, p.status, COUNT(*) AS total
         FROM presensi p
         WHERE p.jenis_kegiatan=? AND DATE_FORMAT(p.dicatat_pada,'%Y-%m')=?
         GROUP BY p.siswa_id, p.status"
    );
    $pStmt->bind_param('ss', $jenis, $bulan);
    $pStmt->execute();
    foreach ($pStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $sid = $row['siswa_id'];
        if (!isset($presensiData[$sid])) $presensiData[$sid] = ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
        $presensiData[$sid][$row['status']] += $row['total'];
    }
    $pStmt->close();
}

// Regu list untuk filter
$reguList = $db->query("SELECT DISTINCT regu FROM siswa WHERE regu IS NOT NULL AND regu!='' AND is_active=1 ORDER BY regu")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Rekap Kehadiran Siswa</h2>
            <p class="page-sub">Rekapitulasi per siswa per bulan</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/presensi/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <form method="GET" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end;">
                <div style="min-width:160px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Bulan</label>
                    <input type="month" name="bulan" class="form-input" value="<?= e($bulan) ?>">
                </div>
                <div style="min-width:160px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Jenis Kegiatan</label>
                    <select name="jenis" class="form-select">
                        <option value="">Semua Kegiatan</option>
                        <option value="rabuan" <?= $jenisFilter==='rabuan'?'selected':'' ?>>Rabuan</option>
                        <option value="mentoring" <?= $jenisFilter==='mentoring'?'selected':'' ?>>Mentoring</option>
                        <option value="binjas" <?= $jenisFilter==='binjas'?'selected':'' ?>>Bina Jasmani</option>
                    </select>
                </div>
                <div style="min-width:140px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Regu</label>
                    <select name="regu" class="form-select">
                        <option value="">Semua Regu</option>
                        <?php foreach ($reguList as $r): ?>
                            <option value="<?= e($r['regu']) ?>" <?= $reguFilter===$r['regu']?'selected':'' ?>><?= e($r['regu']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="<?= BASE_URL ?>/modules/presensi/rekap.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">📊 Rekap — <?= formatTanggal($bulan . '-01', 'F Y') ?></span>
            <span style="font-size:.8rem; color:var(--gray-400);"><?= count($siswaList) ?> siswa</span>
        </div>
        <div class="table-responsive">
            <table class="table" style="font-size:.875rem;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Siswa</th>
                        <th>Regu</th>
                        <th style="text-align:center; color:var(--success);">Hadir</th>
                        <th style="text-align:center; color:var(--warning);">Izin</th>
                        <th style="text-align:center; color:var(--info);">Sakit</th>
                        <th style="text-align:center; color:var(--danger);">Alpha</th>
                        <th style="text-align:center;">Total</th>
                        <th style="text-align:center;">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($siswaList)): ?>
                    <tr><td colspan="9" class="text-center text-muted">Tidak ada data siswa.</td></tr>
                <?php else: ?>
                    <?php foreach ($siswaList as $i => $s):
                        $p     = $presensiData[$s['id']] ?? ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
                        $total = array_sum($p);
                        $pct   = $total > 0 ? round(($p['hadir']/$total)*100) : 0;
                        $pctColor = $pct >= 80 ? 'var(--success)' : ($pct >= 60 ? 'var(--warning)' : 'var(--danger)');
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/modules/siswa/detail.php?id=<?= $s['id'] ?>"
                               style="font-weight:600; color:var(--primary); text-decoration:none;"><?= e($s['nama']) ?></a>
                            <br><code style="font-size:.7rem;"><?= e($s['nis']) ?></code>
                        </td>
                        <td><?= e($s['regu'] ?? '—') ?></td>
                        <td style="text-align:center; font-weight:700; color:var(--success);"><?= $p['hadir'] ?></td>
                        <td style="text-align:center; font-weight:700; color:var(--warning);"><?= $p['izin'] ?></td>
                        <td style="text-align:center; font-weight:700; color:var(--info);"><?= $p['sakit'] ?></td>
                        <td style="text-align:center; font-weight:700; color:var(--danger);"><?= $p['alpha'] ?></td>
                        <td style="text-align:center;"><?= $total ?></td>
                        <td style="text-align:center;">
                            <?php if ($total > 0): ?>
                            <div style="display:flex; align-items:center; gap:.4rem;">
                                <div style="flex:1; height:6px; background:var(--gray-100); border-radius:999px; overflow:hidden;">
                                    <div style="height:100%; width:<?= $pct ?>%; background:<?= $pctColor ?>; border-radius:999px;"></div>
                                </div>
                                <span style="font-weight:700; color:<?= $pctColor ?>; font-size:.8rem; min-width:32px;"><?= $pct ?>%</span>
                            </div>
                            <?php else: ?>
                                <span style="color:var(--gray-300); font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
