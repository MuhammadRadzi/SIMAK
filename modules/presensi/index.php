<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Presensi';
$flash     = getFlash();

// Statistik ringkasan
$bulanIni = date('Y-m');
$stats = [];
foreach (['rabuan','mentoring','binjas'] as $jenis) {
    $r = $db->query("SELECT status, COUNT(*) AS total FROM presensi WHERE jenis_kegiatan='$jenis' AND DATE_FORMAT(dicatat_pada,'%Y-%m')='$bulanIni' GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $map = [];
    foreach ($r as $row) $map[$row['status']] = $row['total'];
    $total = array_sum($map);
    $stats[$jenis] = [
        'hadir' => $map['hadir'] ?? 0,
        'izin'  => $map['izin']  ?? 0,
        'sakit' => $map['sakit'] ?? 0,
        'alpha' => $map['alpha'] ?? 0,
        'total' => $total,
        'pct'   => $total > 0 ? round((($map['hadir']??0)/$total)*100) : 0,
    ];
}

// Kegiatan terbaru yang bisa dipresensi
$rabuanList = $db->query("SELECT id, judul AS nama, tanggal FROM rabuan WHERE status IN ('aktif','selesai') ORDER BY tanggal DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$mentoringList = $db->query("SELECT id, judul_materi AS nama, tanggal FROM mentoring WHERE status IN ('aktif','selesai') ORDER BY tanggal DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$binjásList = $db->query("SELECT id, nama_sesi AS nama, tanggal FROM binjas WHERE status IN ('aktif','selesai') ORDER BY tanggal DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Modul Presensi</h2>
            <p class="page-sub">Rekapitulasi kehadiran siswa — <?= formatTanggal(date('Y-m-01'), 'F Y') ?></p>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Ringkasan Bulan Ini -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.5rem;">
        <?php
        $jenisLabel = ['rabuan'=>'Rabuan','mentoring'=>'Mentoring','binjas'=>'Bina Jasmani'];
        $jenisIcon  = ['rabuan'=>'👥','mentoring'=>'📖','binjas'=>'💪'];
        foreach ($stats as $jenis => $s):
        ?>
        <div class="card">
            <div class="card-body">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
                    <span style="font-size:.9rem; font-weight:700; color:var(--gray-700);"><?= $jenisIcon[$jenis] ?> <?= $jenisLabel[$jenis] ?></span>
                    <span style="font-size:1.25rem; font-weight:800; color:var(--primary);"><?= $s['pct'] ?>%</span>
                </div>
                <div style="height:8px; background:var(--gray-100); border-radius:999px; margin-bottom:.75rem; overflow:hidden;">
                    <div style="height:100%; width:<?= $s['pct'] ?>%; background:var(--success); border-radius:999px;"></div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(4,1fr); text-align:center; font-size:.75rem;">
                    <div><div style="font-weight:700; color:var(--success);"><?= $s['hadir'] ?></div><div style="color:var(--gray-400);">Hadir</div></div>
                    <div><div style="font-weight:700; color:var(--warning);"><?= $s['izin'] ?></div><div style="color:var(--gray-400);">Izin</div></div>
                    <div><div style="font-weight:700; color:var(--info);"><?= $s['sakit'] ?></div><div style="color:var(--gray-400);">Sakit</div></div>
                    <div><div style="font-weight:700; color:var(--danger);"><?= $s['alpha'] ?></div><div style="color:var(--gray-400);">Alpha</div></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Daftar Kegiatan per Modul -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">

        <?php
        $kegiatanData = [
            'rabuan'    => ['label'=>'Rabuan',       'list'=>$rabuanList,    'icon'=>'👥'],
            'mentoring' => ['label'=>'Mentoring',     'list'=>$mentoringList, 'icon'=>'📖'],
            'binjas'    => ['label'=>'Bina Jasmani',  'list'=>$binjásList,   'icon'=>'💪'],
        ];
        foreach ($kegiatanData as $jenis => $data):
        ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $data['icon'] ?> <?= $data['label'] ?></span>
            </div>
            <div style="padding:0;">
                <?php if (empty($data['list'])): ?>
                    <p style="padding:1rem; text-align:center; color:var(--gray-400); font-size:.875rem;">Belum ada kegiatan.</p>
                <?php else: ?>
                    <?php foreach ($data['list'] as $k): ?>
                    <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=<?= $jenis ?>&id=<?= $k['id'] ?>"
                       style="display:flex; align-items:center; justify-content:space-between; padding:.75rem 1.25rem; border-bottom:1px solid var(--gray-100); text-decoration:none; transition:background .15s;"
                       onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <div>
                            <div style="font-size:.875rem; font-weight:600; color:var(--gray-800);"><?= e($k['nama']) ?></div>
                            <div style="font-size:.75rem; color:var(--gray-400);"><?= formatTanggal($k['tanggal'], 'd M Y') ?></div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Link laporan kehadiran per siswa -->
    <div style="margin-top:1rem; text-align:center;">
        <a href="<?= BASE_URL ?>/modules/presensi/rekap.php" class="btn btn-secondary">
            📊 Lihat Rekap Kehadiran Per Siswa
        </a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
