<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Dashboard';
$flash     = getFlash();

// ── Statistik Utama ──────────────────────────────────────────
$totalSiswa     = $db->query("SELECT COUNT(*) FROM siswa WHERE is_active=1")->fetch_row()[0];
$totalRabuan    = $db->query("SELECT COUNT(*) FROM rabuan")->fetch_row()[0];
$totalMentoring = $db->query("SELECT COUNT(*) FROM mentoring")->fetch_row()[0];
$totalOps       = $db->query("SELECT COUNT(*) FROM operasional")->fetch_row()[0];
$totalBinjas    = $db->query("SELECT COUNT(*) FROM binjas")->fetch_row()[0];

// ── Kegiatan Mendatang (7 hari) ──────────────────────────────
$upcoming = $db->query("
    SELECT 'Rabuan' AS jenis, judul AS nama, tanggal, status FROM rabuan
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'Mentoring', judul_materi, tanggal, status FROM mentoring
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'Binjas', nama_sesi, tanggal, status FROM binjas
        WHERE tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tanggal ASC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ── Data Chart: Presensi 6 Bulan Terakhir ───────────────────
$presensi6Bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $tgl   = date('Y-m', strtotime("-$i months"));
    $label = formatTanggal($tgl . '-01', 'M Y');
    $row   = $db->query("SELECT
        SUM(status='hadir') AS hadir,
        SUM(status='izin')  AS izin,
        SUM(status='sakit') AS sakit,
        SUM(status='alpha') AS alpha,
        COUNT(*) AS total
        FROM presensi WHERE DATE_FORMAT(dicatat_pada,'%Y-%m')='$tgl'")->fetch_assoc();
    $presensi6Bulan[] = [
        'label'  => $label,
        'hadir'  => (int)($row['hadir'] ?? 0),
        'izin'   => (int)($row['izin']  ?? 0),
        'sakit'  => (int)($row['sakit'] ?? 0),
        'alpha'  => (int)($row['alpha'] ?? 0),
        'total'  => (int)($row['total'] ?? 0),
        'pct'    => ($row['total']??0) > 0 ? round(($row['hadir']/$row['total'])*100) : 0,
    ];
}

// ── Data Chart: Kegiatan per Bulan (6 bulan) ────────────────
$kegiatan6Bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $tgl   = date('Y-m', strtotime("-$i months"));
    $label = formatTanggal($tgl . '-01', 'M Y');
    $kegiatan6Bulan[] = [
        'label'      => $label,
        'rabuan'     => (int)$db->query("SELECT COUNT(*) FROM rabuan WHERE DATE_FORMAT(tanggal,'%Y-%m')='$tgl'")->fetch_row()[0],
        'mentoring'  => (int)$db->query("SELECT COUNT(*) FROM mentoring WHERE DATE_FORMAT(tanggal,'%Y-%m')='$tgl'")->fetch_row()[0],
        'binjas'     => (int)$db->query("SELECT COUNT(*) FROM binjas WHERE DATE_FORMAT(tanggal,'%Y-%m')='$tgl'")->fetch_row()[0],
        'operasional'=> (int)$db->query("SELECT COUNT(*) FROM operasional WHERE DATE_FORMAT(tanggal_mulai,'%Y-%m')='$tgl'")->fetch_row()[0],
    ];
}

// ── Data Chart: Kehadiran per Jenis (bulan ini) ──────────────
$bulanIni = date('Y-m');
$presensiJenis = [];
foreach (['rabuan'=>'Rabuan','mentoring'=>'Mentoring','binjas'=>'Bina Jasmani'] as $key=>$label) {
    $r = $db->query("SELECT SUM(status='hadir') AS hadir, COUNT(*) AS total FROM presensi WHERE jenis_kegiatan='$key' AND DATE_FORMAT(dicatat_pada,'%Y-%m')='$bulanIni'")->fetch_assoc();
    $total = (int)($r['total'] ?? 0);
    $hadir = (int)($r['hadir'] ?? 0);
    $presensiJenis[] = [
        'label' => $label,
        'hadir' => $hadir,
        'total' => $total,
        'pct'   => $total > 0 ? round(($hadir/$total)*100) : 0,
    ];
}

// ── Data Chart: Radar Binjas — Top 5 siswa ──────────────────
$jenisLatihan = $db->query("SELECT * FROM binjas_jenis_latihan WHERE is_active=1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Ambil siswa dengan nilai terbanyak di sesi terbaru
$binjasLatest = $db->query("SELECT id FROM binjas ORDER BY tanggal DESC LIMIT 1")->fetch_row();
$radarData = [];
if ($binjasLatest) {
    $binjasId = $binjasLatest[0];
    $siswaValued = $db->query("SELECT DISTINCT siswa_id FROM binjas_nilai WHERE binjas_id=$binjasId LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    foreach ($siswaValued as $sv) {
        $sid  = $sv['siswa_id'];
        $nama = $db->query("SELECT nama FROM siswa WHERE id=$sid")->fetch_row()[0] ?? 'Siswa';
        $nilaiArr = [];
        foreach ($jenisLatihan as $jl) {
            $n = $db->query("SELECT nilai FROM binjas_nilai WHERE binjas_id=$binjasId AND siswa_id=$sid AND jenis_latihan_id={$jl['id']} LIMIT 1")->fetch_row();
            // Normalisasi 0-100 relatif terhadap standar
            if ($n && $jl['nilai_standar'] > 0) {
                if ($jl['keterangan_arah'] === 'semakin_tinggi') {
                    $nilaiArr[] = min(round(($n[0]/$jl['nilai_standar'])*100), 150);
                } else {
                    // Semakin rendah lebih baik: nilai standar/nilai * 100
                    $nilaiArr[] = $n[0] > 0 ? min(round(($jl['nilai_standar']/$n[0])*100), 150) : 0;
                }
            } else {
                $nilaiArr[] = 0;
            }
        }
        $radarData[] = ['nama' => $nama, 'nilai' => $nilaiArr];
    }
}

// ── Rekap Terbaru ────────────────────────────────────────────
$recentRabuan    = $db->query("SELECT id, judul, tanggal, status FROM rabuan ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recentMentoring = $db->query("SELECT id, judul_materi, nama_mentor, tanggal, status FROM mentoring ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Siswa dengan kehadiran terendah bulan ini
$siswaAlpha = $db->query("
    SELECT s.nama, s.nis, COUNT(*) AS total_alpha
    FROM presensi p JOIN siswa s ON s.id = p.siswa_id
    WHERE p.status = 'alpha' AND DATE_FORMAT(p.dicatat_pada,'%Y-%m') = '$bulanIni'
    GROUP BY p.siswa_id ORDER BY total_alpha DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Dashboard</h2>
            <p class="page-sub">Selamat datang, <strong><?= e(currentUser()['nama']) ?></strong> — <?= formatTanggal(date('Y-m-d'), 'l, d F Y') ?></p>
        </div>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────── -->
    <div class="stat-grid">
        <?php $stats = [
            ['blue',  'Siswa Aktif',   $totalSiswa,     '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',  'modules/siswa/index.php'],
            ['green', 'Total Rabuan',  $totalRabuan,    '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>','modules/rabuan/index.php'],
            ['yellow','Total Mentoring',$totalMentoring,'<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>','modules/mentoring/index.php'],
            ['red',   'Operasional',   $totalOps,       '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',  'modules/operasional/index.php'],
            ['cyan',  'Sesi Binjas',   $totalBinjas,    '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>','modules/binjas/index.php'],
        ];
        foreach ($stats as [$color, $label, $val, $icon, $link]): ?>
        <a href="<?= BASE_URL ?>/<?= $link ?>" style="text-decoration:none;">
            <div class="stat-card" style="cursor:pointer; transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.boxShadow=''">
                <div class="stat-icon <?= $color ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $icon ?></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $val ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Row 1: Chart Kehadiran 6 Bulan + Donut Jenis ────── -->
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem; margin-bottom:1rem;">

        <!-- Bar Chart: Kehadiran 6 bulan -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📈 Tren Kehadiran 6 Bulan Terakhir</span>
            </div>
            <div class="card-body">
                <canvas id="chartPresensi" height="100"></canvas>
            </div>
        </div>

        <!-- Donut: Kehadiran bulan ini per jenis -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">🍩 Kehadiran Bulan Ini</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:.75rem;">
                <?php foreach ($presensiJenis as $pj): ?>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:.8125rem; margin-bottom:.25rem;">
                        <span style="font-weight:600; color:var(--gray-700);"><?= $pj['label'] ?></span>
                        <span style="color:var(--gray-500);"><?= $pj['hadir'] ?>/<?= $pj['total'] ?> (<?= $pj['pct'] ?>%)</span>
                    </div>
                    <div style="height:10px; background:var(--gray-100); border-radius:999px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pj['pct'] ?>%;
                            background:<?= $pj['pct']>=80?'var(--success)':($pj['pct']>=60?'var(--warning)':'var(--danger)') ?>;
                            border-radius:999px; transition:width .6s ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (array_sum(array_column($presensiJenis,'total')) === 0): ?>
                    <p style="text-align:center; color:var(--gray-400); font-size:.875rem;">Belum ada data presensi bulan ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Row 2: Chart Kegiatan per Bulan + Radar Binjas ──── -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">

        <!-- Bar Chart: Kegiatan per bulan -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📊 Kegiatan per Bulan</span>
            </div>
            <div class="card-body">
                <canvas id="chartKegiatan" height="140"></canvas>
            </div>
        </div>

        <!-- Radar Chart: Binjas per siswa -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">🕸️ Radar Binjas (Sesi Terbaru)</span>
                <a href="<?= BASE_URL ?>/modules/binjas/index.php" class="btn btn-sm btn-secondary">Detail</a>
            </div>
            <div class="card-body">
                <?php if (empty($radarData)): ?>
                    <div class="empty-state" style="padding:2rem;">
                        <p>Belum ada data nilai Binjas.</p>
                    </div>
                <?php else: ?>
                    <canvas id="chartRadar" height="140"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Row 3: Kegiatan Mendatang + Alpha Terbanyak ─────── -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">

        <!-- Kegiatan Mendatang -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📅 7 Hari ke Depan</span>
                <a href="<?= BASE_URL ?>/modules/jadwal/index.php" class="btn btn-sm btn-secondary">Kalender</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($upcoming)): ?>
                    <div class="empty-state" style="padding:2rem;">
                        <p>Tidak ada kegiatan dalam 7 hari ke depan.</p>
                    </div>
                <?php else: ?>
                    <ul style="list-style:none; padding:0;">
                    <?php foreach ($upcoming as $up): ?>
                        <li style="display:flex; align-items:center; gap:.75rem; padding:.75rem 1.25rem; border-bottom:1px solid var(--gray-100);">
                            <div style="width:42px; height:42px; border-radius:10px; background:var(--primary-light); color:var(--primary);
                                display:flex; align-items:center; justify-content:center; flex-shrink:0;
                                font-size:.7rem; font-weight:700; text-align:center; line-height:1.2;">
                                <?= date('d', strtotime($up['tanggal'])) ?><br>
                                <?= strtoupper(date('M', strtotime($up['tanggal']))) ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:.875rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($up['nama']) ?></div>
                                <div style="font-size:.75rem; color:var(--gray-400);"><?= e($up['jenis']) ?></div>
                            </div>
                            <?= badgeStatus($up['status']) ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Siswa Alpha Terbanyak -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">⚠️ Alpha Terbanyak Bulan Ini</span>
                <a href="<?= BASE_URL ?>/modules/presensi/rekap.php" class="btn btn-sm btn-secondary">Rekap</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($siswaAlpha)): ?>
                    <div class="empty-state" style="padding:2rem;">
                        <p>Tidak ada data alpha bulan ini. 🎉</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($siswaAlpha as $i => $sa): ?>
                    <div style="display:flex; align-items:center; gap:.875rem; padding:.75rem 1.25rem; border-bottom:1px solid var(--gray-100);">
                        <div style="width:28px; height:28px; border-radius:50%;
                            background:<?= $i===0?'var(--danger)':($i===1?'var(--warning)':'var(--gray-200)') ?>;
                            color:<?= $i<=1?'#fff':'var(--gray-600)' ?>;
                            display:flex; align-items:center; justify-content:center;
                            font-size:.75rem; font-weight:800; flex-shrink:0;">
                            <?= $i+1 ?>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:.875rem;"><?= e($sa['nama']) ?></div>
                            <div style="font-size:.7rem; color:var(--gray-400);"><?= e($sa['nis']) ?></div>
                        </div>
                        <span style="background:var(--danger-light); color:var(--danger); font-weight:700; font-size:.8rem; padding:.2rem .6rem; border-radius:999px;">
                            <?= $sa['total_alpha'] ?>x Alpha
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Row 4: Rabuan & Mentoring Terbaru ────────────────── -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Rabuan Terbaru</span>
                <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-sm btn-secondary">Semua</a>
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
                            <td><a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $r['id'] ?>" style="color:var(--primary); font-weight:500; text-decoration:none;"><?= e($r['judul']) ?></a></td>
                            <td><?= formatTanggal($r['tanggal'], 'd M Y') ?></td>
                            <td><?= badgeStatus($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Mentoring Terbaru</span>
                <a href="<?= BASE_URL ?>/modules/mentoring/index.php" class="btn btn-sm btn-secondary">Semua</a>
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
                            <td><a href="<?= BASE_URL ?>/modules/mentoring/detail.php?id=<?= $m['id'] ?>" style="color:var(--primary); font-weight:500; text-decoration:none;"><?= e($m['judul_materi']) ?></a></td>
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

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// Data dari PHP
const presensi6Bulan = <?= json_encode($presensi6Bulan) ?>;
const kegiatan6Bulan = <?= json_encode($kegiatan6Bulan) ?>;
const radarData      = <?= json_encode($radarData) ?>;
const jenisLatihan   = <?= json_encode(array_column($jenisLatihan, 'nama')) ?>;
const standarNilai   = <?= json_encode(array_map(fn($j) => 100, $jenisLatihan)) ?>; // 100 = baseline standar

const palette = {
    blue:   '#2563eb', blueA:  'rgba(37,99,235,.15)',
    green:  '#16a34a', greenA: 'rgba(22,163,74,.15)',
    yellow: '#d97706', yellowA:'rgba(217,119,6,.15)',
    red:    '#dc2626', redA:   'rgba(220,38,38,.15)',
    cyan:   '#0891b2', cyanA:  'rgba(8,145,178,.15)',
    gray:   '#94a3b8',
};

Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#64748b';

// ── 1. Bar Chart: Tren Kehadiran ──────────────────────────────
new Chart(document.getElementById('chartPresensi'), {
    type: 'bar',
    data: {
        labels: presensi6Bulan.map(d => d.label),
        datasets: [
            { label:'Hadir', data: presensi6Bulan.map(d=>d.hadir), backgroundColor: palette.greenA, borderColor: palette.green, borderWidth:2, borderRadius:4 },
            { label:'Izin',  data: presensi6Bulan.map(d=>d.izin),  backgroundColor: 'rgba(217,119,6,.15)', borderColor: palette.yellow, borderWidth:2, borderRadius:4 },
            { label:'Sakit', data: presensi6Bulan.map(d=>d.sakit), backgroundColor: 'rgba(8,145,178,.15)', borderColor: palette.cyan, borderWidth:2, borderRadius:4 },
            { label:'Alpha', data: presensi6Bulan.map(d=>d.alpha), backgroundColor: palette.redA, borderColor: palette.red, borderWidth:2, borderRadius:4 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:true,
        plugins: { legend:{ position:'top' }, tooltip:{ mode:'index' } },
        scales: {
            x: { grid:{ display:false } },
            y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,.05)' }, ticks:{ stepSize:1 } }
        }
    }
});

// ── 2. Bar Chart: Kegiatan per Bulan ─────────────────────────
new Chart(document.getElementById('chartKegiatan'), {
    type: 'bar',
    data: {
        labels: kegiatan6Bulan.map(d=>d.label),
        datasets: [
            { label:'Rabuan',      data: kegiatan6Bulan.map(d=>d.rabuan),      backgroundColor: palette.blueA,   borderColor: palette.blue,   borderWidth:2, borderRadius:4 },
            { label:'Mentoring',   data: kegiatan6Bulan.map(d=>d.mentoring),   backgroundColor: palette.yellowA, borderColor: palette.yellow, borderWidth:2, borderRadius:4 },
            { label:'Binjas',      data: kegiatan6Bulan.map(d=>d.binjas),      backgroundColor: palette.greenA,  borderColor: palette.green,  borderWidth:2, borderRadius:4 },
            { label:'Operasional', data: kegiatan6Bulan.map(d=>d.operasional), backgroundColor: palette.redA,    borderColor: palette.red,    borderWidth:2, borderRadius:4 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:true,
        plugins: { legend:{ position:'top' }, tooltip:{ mode:'index' } },
        scales: {
            x: { stacked:false, grid:{ display:false } },
            y: { beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'rgba(0,0,0,.05)' } }
        }
    }
});

// ── 3. Radar Chart: Binjas ───────────────────────────────────
<?php if (!empty($radarData)): ?>
const radarColors = [
    ['rgba(37,99,235,.7)',   'rgba(37,99,235,.15)'],
    ['rgba(22,163,74,.7)',   'rgba(22,163,74,.15)'],
    ['rgba(220,38,38,.7)',   'rgba(220,38,38,.15)'],
    ['rgba(217,119,6,.7)',   'rgba(217,119,6,.15)'],
    ['rgba(8,145,178,.7)',   'rgba(8,145,178,.15)'],
];
const radarDatasets = radarData.map((s, i) => ({
    label: s.nama,
    data:  s.nilai,
    borderColor:     radarColors[i%radarColors.length][0],
    backgroundColor: radarColors[i%radarColors.length][1],
    borderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6,
}));
// Tambahkan garis standar (100%)
radarDatasets.push({
    label: '— Standar (100%)',
    data: jenisLatihan.map(()=>100),
    borderColor: 'rgba(100,116,139,.5)',
    backgroundColor: 'transparent',
    borderWidth: 2,
    borderDash: [5,5],
    pointRadius: 0,
});

new Chart(document.getElementById('chartRadar'), {
    type: 'radar',
    data: { labels: jenisLatihan, datasets: radarDatasets },
    options: {
        responsive:true, maintainAspectRatio:true,
        plugins: { legend:{ position:'bottom', labels:{ boxWidth:12, padding:10, font:{size:11} } } },
        scales: {
            r: {
                beginAtZero:true, max:150, min:0,
                ticks:{ stepSize:50, callback: v => v+'%', backdropColor:'transparent' },
                grid: { color:'rgba(0,0,0,.08)' },
                pointLabels: { font:{ size:11, weight:'600' } }
            }
        }
    }
});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
