<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Dashboard';
$flash     = getFlash();

// --- Statistik ---
$totalSiswa     = $db->query("SELECT COUNT(*) FROM siswa WHERE is_active=1")->fetch_row()[0];
$totalRabuan    = $db->query("SELECT COUNT(*) FROM rabuan")->fetch_row()[0];
$totalMentoring = $db->query("SELECT COUNT(*) FROM mentoring")->fetch_row()[0];
$totalOps       = $db->query("SELECT COUNT(*) FROM operasional")->fetch_row()[0];
$totalBinjas    = $db->query("SELECT COUNT(*) FROM binjas")->fetch_row()[0];

// --- Kegiatan mendatang (7 hari ke depan) ---
$upcoming = $db->query("
    SELECT 'Rabuan' AS jenis, judul AS nama, tanggal, status FROM rabuan
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'Mentoring', judul_materi, tanggal, status FROM mentoring
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'Binjas', nama_sesi, tanggal, status FROM binjas
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tanggal ASC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// --- Kegiatan terbaru ---
$recentRabuan = $db->query(
    "SELECT id, judul, tanggal, status FROM rabuan ORDER BY created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$recentMentoring = $db->query(
    "SELECT id, judul_materi, nama_mentor, tanggal, status FROM mentoring ORDER BY created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// --- Kehadiran bulan ini (ringkasan) ---
$bulanIni = date('Y-m');
$statPresensi = $db->query("
    SELECT
        jenis_kegiatan,
        status,
        COUNT(*) AS total
    FROM presensi
    WHERE DATE_FORMAT(dicatat_pada, '%Y-%m') = '$bulanIni'
    GROUP BY jenis_kegiatan, status
")->fetch_all(MYSQLI_ASSOC);

// Susun data presensi ke array terstruktur
$presensiData = [];
foreach ($statPresensi as $row) {
    $presensiData[$row['jenis_kegiatan']][$row['status']] = $row['total'];
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">

    <!-- Flash -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Dashboard</h2>
            <p class="page-sub">Selamat datang, <strong><?= e(currentUser()['nama']) ?></strong> — <?= formatTanggal(date('Y-m-d'), 'l, d F Y') ?></p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalSiswa ?></div>
                <div class="stat-label">Total Siswa Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalRabuan ?></div>
                <div class="stat-label">Total Rabuan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalMentoring ?></div>
                <div class="stat-label">Total Mentoring</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalOps ?></div>
                <div class="stat-label">Total Operasional</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalBinjas ?></div>
                <div class="stat-label">Total Sesi Binjas</div>
            </div>
        </div>
    </div>

    <!-- Row: Kegiatan Mendatang + Presensi -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">

        <!-- Kegiatan Mendatang -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📅 Kegiatan 7 Hari ke Depan</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($upcoming)): ?>
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <p>Tidak ada kegiatan dalam 7 hari ke depan</p>
                    </div>
                <?php else: ?>
                    <ul style="list-style:none; padding:0;">
                    <?php foreach ($upcoming as $up): ?>
                        <li style="display:flex; align-items:center; gap:.75rem; padding:.75rem 1.25rem; border-bottom:1px solid var(--gray-100);">
                            <div style="width:42px; height:42px; border-radius:10px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.7rem; font-weight:700; text-align:center; line-height:1.2;">
                                <?= date('d', strtotime($up['tanggal'])) ?><br>
                                <?= strtoupper(date('M', strtotime($up['tanggal']))) ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:.875rem; color:var(--gray-800); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= e($up['nama']) ?>
                                </div>
                                <div style="font-size:.75rem; color:var(--gray-400);"><?= e($up['jenis']) ?></div>
                            </div>
                            <?= badgeStatus($up['status']) ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ringkasan Presensi Bulan Ini -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📊 Presensi Bulan Ini</span>
                <span style="font-size:.75rem; color:var(--gray-400);"><?= formatTanggal(date('Y-m-01'), 'F Y') ?></span>
            </div>
            <div class="card-body">
                <?php
                $jenisKegiatan = ['rabuan' => 'Rabuan', 'mentoring' => 'Mentoring', 'binjas' => 'Bina Jasmani'];
                foreach ($jenisKegiatan as $key => $label):
                    $hadir = $presensiData[$key]['hadir'] ?? 0;
                    $izin  = $presensiData[$key]['izin']  ?? 0;
                    $sakit = $presensiData[$key]['sakit'] ?? 0;
                    $alpha = $presensiData[$key]['alpha'] ?? 0;
                    $total = $hadir + $izin + $sakit + $alpha;
                    $pct   = $total > 0 ? round(($hadir / $total) * 100) : 0;
                ?>
                <div style="margin-bottom:1.1rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:.3rem;">
                        <span style="font-size:.875rem; font-weight:600; color:var(--gray-700);"><?= $label ?></span>
                        <span style="font-size:.8rem; color:var(--gray-500);"><?= $hadir ?>/<?= $total ?> hadir (<?= $pct ?>%)</span>
                    </div>
                    <div style="height:8px; background:var(--gray-100); border-radius:999px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:var(--success); border-radius:999px; transition:width .5s;"></div>
                    </div>
                    <div style="display:flex; gap:.5rem; margin-top:.35rem; flex-wrap:wrap;">
                        <span style="font-size:.7rem; color:var(--success);">✓ Hadir: <?= $hadir ?></span>
                        <span style="font-size:.7rem; color:var(--warning);">~ Izin: <?= $izin ?></span>
                        <span style="font-size:.7rem; color:var(--info);">+ Sakit: <?= $sakit ?></span>
                        <span style="font-size:.7rem; color:var(--danger);">✗ Alpha: <?= $alpha ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Row: Rabuan & Mentoring Terbaru -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

        <!-- Rabuan Terbaru -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Rabuan Terbaru</span>
                <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Judul</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentRabuan)): ?>
                        <tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentRabuan as $r): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $r['id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= e($r['judul']) ?></a></td>
                            <td><?= formatTanggal($r['tanggal'], 'd M Y') ?></td>
                            <td><?= badgeStatus($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mentoring Terbaru -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Mentoring Terbaru</span>
                <a href="<?= BASE_URL ?>/modules/mentoring/index.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Materi</th><th>Mentor</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentMentoring)): ?>
                        <tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMentoring as $m): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/mentoring/detail.php?id=<?= $m['id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= e($m['judul_materi']) ?></a></td>
                            <td><?= e($m['nama_mentor']) ?></td>
                            <td><?= formatTanggal($m['tanggal'], 'd M Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
