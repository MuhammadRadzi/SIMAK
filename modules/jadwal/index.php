<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Jadwal Terpadu';

// Bulan & tahun yang ditampilkan
$bulan = (int)get('bulan', date('n'));
$tahun = (int)get('tahun', date('Y'));

// Validasi range
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('n');
if ($tahun < 2020 || $tahun > 2099) $tahun = (int)date('Y');

$prevBulan = $bulan === 1  ? 12 : $bulan - 1;
$prevTahun = $bulan === 1  ? $tahun - 1 : $tahun;
$nextBulan = $bulan === 12 ? 1  : $bulan + 1;
$nextTahun = $bulan === 12 ? $tahun + 1 : $tahun;

$tglAwal  = sprintf('%04d-%02d-01', $tahun, $bulan);
$tglAkhir = date('Y-m-t', strtotime($tglAwal));

// Ambil semua kegiatan bulan ini
$events = [];

// Rabuan
$r = $db->prepare("SELECT id, judul AS nama, tanggal, waktu_mulai, status, 'rabuan' AS jenis FROM rabuan WHERE tanggal BETWEEN ? AND ?");
$r->bind_param('ss', $tglAwal, $tglAkhir); $r->execute();
foreach ($r->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $events[] = $row;
$r->close();

// Mentoring
$m = $db->prepare("SELECT id, judul_materi AS nama, tanggal, waktu_mulai, status, 'mentoring' AS jenis FROM mentoring WHERE tanggal BETWEEN ? AND ?");
$m->bind_param('ss', $tglAwal, $tglAkhir); $m->execute();
foreach ($m->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $events[] = $row;
$m->close();

// Operasional
$o = $db->prepare("SELECT id, nama_kegiatan AS nama, tanggal_mulai AS tanggal, NULL AS waktu_mulai, status, 'operasional' AS jenis FROM operasional WHERE tanggal_mulai BETWEEN ? AND ?");
$o->bind_param('ss', $tglAwal, $tglAkhir); $o->execute();
foreach ($o->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $events[] = $row;
$o->close();

// Binjas
$b = $db->prepare("SELECT id, nama_sesi AS nama, tanggal, waktu_mulai, status, 'binjas' AS jenis FROM binjas WHERE tanggal BETWEEN ? AND ?");
$b->bind_param('ss', $tglAwal, $tglAkhir); $b->execute();
foreach ($b->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $events[] = $row;
$b->close();

// Group events by tanggal
$eventsByDate = [];
foreach ($events as $ev) {
    $eventsByDate[$ev['tanggal']][] = $ev;
}

// Kalender setup
$hariPertama  = (int)date('N', strtotime($tglAwal)); // 1=Sen, 7=Min
$totalHari    = (int)date('t', strtotime($tglAwal));
$namaBulan    = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$namaHari     = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

// Warna per jenis
$jenisColor = [
    'rabuan'     => ['bg'=>'#dbeafe','border'=>'#2563eb','text'=>'#1e40af','label'=>'Rabuan'],
    'mentoring'  => ['bg'=>'#fef9c3','border'=>'#ca8a04','text'=>'#854d0e','label'=>'Mentoring'],
    'operasional'=> ['bg'=>'#fee2e2','border'=>'#dc2626','text'=>'#991b1b','label'=>'Operasional'],
    'binjas'     => ['bg'=>'#dcfce7','border'=>'#16a34a','text'=>'#166534','label'=>'Binjas'],
];

// Ambil semua kegiatan untuk list view
$allEvents = [];
$listR = $db->prepare("SELECT id,'rabuan' AS jenis, judul AS nama, tanggal, waktu_mulai, status FROM rabuan WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, waktu_mulai");
$listR->bind_param('ss', $tglAwal, $tglAkhir); $listR->execute();
foreach ($listR->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $allEvents[] = $row;
$listR->close();

$listM = $db->prepare("SELECT id,'mentoring' AS jenis, judul_materi AS nama, tanggal, waktu_mulai, status FROM mentoring WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, waktu_mulai");
$listM->bind_param('ss', $tglAwal, $tglAkhir); $listM->execute();
foreach ($listM->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $allEvents[] = $row;
$listM->close();

$listO = $db->prepare("SELECT id,'operasional' AS jenis, nama_kegiatan AS nama, tanggal_mulai AS tanggal, NULL AS waktu_mulai, status FROM operasional WHERE tanggal_mulai BETWEEN ? AND ? ORDER BY tanggal_mulai");
$listO->bind_param('ss', $tglAwal, $tglAkhir); $listO->execute();
foreach ($listO->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $allEvents[] = $row;
$listO->close();

$listB = $db->prepare("SELECT id,'binjas' AS jenis, nama_sesi AS nama, tanggal, waktu_mulai, status FROM binjas WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, waktu_mulai");
$listB->bind_param('ss', $tglAwal, $tglAkhir); $listB->execute();
foreach ($listB->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $allEvents[] = $row;
$listB->close();

// Sort all events by tanggal
usort($allEvents, fn($a,$b) => strcmp($a['tanggal'], $b['tanggal']));

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Jadwal Terpadu</h2>
            <p class="page-sub">Kalender seluruh kegiatan siswa</p>
        </div>
        <!-- View toggle -->
        <div class="d-flex gap-1">
            <button onclick="switchView('calendar')" id="btnCalendar" class="btn btn-primary">📅 Kalender</button>
            <button onclick="switchView('list')"     id="btnList"     class="btn btn-secondary">📋 List</button>
        </div>
    </div>

    <!-- Legend -->
    <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem;">
        <?php foreach ($jenisColor as $jenis => $c): ?>
        <div style="display:flex; align-items:center; gap:.4rem; font-size:.8rem;">
            <div style="width:12px; height:12px; border-radius:3px; background:<?= $c['border'] ?>;"></div>
            <span style="color:var(--gray-600);"><?= $c['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ======================================================
         CALENDAR VIEW
         ====================================================== -->
    <div id="viewCalendar">
        <div class="card">
            <!-- Header navigasi bulan -->
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid var(--gray-100);">
                <a href="?bulan=<?= $prevBulan ?>&tahun=<?= $prevTahun ?>" class="btn btn-secondary btn-sm">← Prev</a>
                <h3 style="font-size:1.1rem; font-weight:800; color:var(--gray-900);">
                    <?= $namaBulan[$bulan] ?> <?= $tahun ?>
                </h3>
                <a href="?bulan=<?= $nextBulan ?>&tahun=<?= $nextTahun ?>" class="btn btn-secondary btn-sm">Next →</a>
            </div>

            <!-- Grid Kalender -->
            <div style="padding:1rem;">
                <!-- Header hari -->
                <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-bottom:4px;">
                    <?php foreach ($namaHari as $i => $h): ?>
                    <div style="text-align:center; font-size:.75rem; font-weight:700; color:<?= $i>=5?'var(--danger)':'var(--gray-500)' ?>; padding:.4rem 0; text-transform:uppercase; letter-spacing:.05em;">
                        <?= $h ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sel kalender -->
                <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:4px;">
                    <?php
                    // Padding awal (Senin = 1)
                    $startPad = $hariPertama - 1;
                    for ($pad = 0; $pad < $startPad; $pad++):
                    ?>
                    <div style="min-height:90px; background:var(--gray-50); border-radius:8px; opacity:.3;"></div>
                    <?php endfor; ?>

                    <?php for ($hari = 1; $hari <= $totalHari; $hari++):
                        $tglStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
                        $dayOfWeek= (int)date('N', strtotime($tglStr)); // 1=Sen,7=Min
                        $isToday  = $tglStr === date('Y-m-d');
                        $isWeekend= $dayOfWeek >= 6;
                        $dayEvents= $eventsByDate[$tglStr] ?? [];
                    ?>
                    <div style="min-height:90px; border-radius:8px; padding:.4rem;
                        background:<?= $isToday ? 'var(--primary-light)' : ($isWeekend ? '#fafafa' : '#fff') ?>;
                        border:<?= $isToday ? '2px solid var(--primary)' : '1px solid var(--gray-100)' ?>;">
                        <div style="font-size:.8rem; font-weight:<?= $isToday ? '800' : '600' ?>;
                            color:<?= $isToday ? 'var(--primary)' : ($isWeekend ? 'var(--danger)' : 'var(--gray-700)') ?>;
                            margin-bottom:.25rem; text-align:right;">
                            <?= $hari ?>
                        </div>
                        <?php foreach (array_slice($dayEvents, 0, 3) as $ev):
                            $c = $jenisColor[$ev['jenis']] ?? $jenisColor['rabuan'];
                            $detailUrl = BASE_URL . '/modules/' . $ev['jenis'] . '/detail.php?id=' . $ev['id'];
                        ?>
                        <a href="<?= $detailUrl ?>" title="<?= e($ev['nama']) ?>"
                           style="display:block; font-size:.68rem; font-weight:600;
                                  background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>;
                                  border-left:3px solid <?= $c['border'] ?>;
                                  padding:.15rem .35rem; border-radius:0 4px 4px 0;
                                  margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                                  text-decoration:none; transition:opacity .15s;"
                           onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                            <?= e($ev['nama']) ?>
                        </a>
                        <?php endforeach; ?>
                        <?php if (count($dayEvents) > 3): ?>
                        <div style="font-size:.65rem; color:var(--gray-400); text-align:center;">+<?= count($dayEvents)-3 ?> lagi</div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>

                    <?php
                    // Padding akhir
                    $used = $startPad + $totalHari;
                    $endPad = (7 - ($used % 7)) % 7;
                    for ($pad = 0; $pad < $endPad; $pad++):
                    ?>
                    <div style="min-height:90px; background:var(--gray-50); border-radius:8px; opacity:.3;"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================
         LIST VIEW
         ====================================================== -->
    <div id="viewList" style="display:none;">
        <!-- Navigasi bulan (list) -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <a href="?bulan=<?= $prevBulan ?>&tahun=<?= $prevTahun ?>" class="btn btn-secondary btn-sm">← Prev</a>
            <h3 style="font-size:1rem; font-weight:700; color:var(--gray-800);"><?= $namaBulan[$bulan] ?> <?= $tahun ?></h3>
            <a href="?bulan=<?= $nextBulan ?>&tahun=<?= $nextTahun ?>" class="btn btn-secondary btn-sm">Next →</a>
        </div>

        <?php if (empty($allEvents)): ?>
            <div class="card"><div class="card-body">
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <p>Tidak ada kegiatan di bulan ini.</p>
                </div>
            </div></div>
        <?php else: ?>
            <?php
            $lastDate = '';
            foreach ($allEvents as $ev):
                $c = $jenisColor[$ev['jenis']] ?? $jenisColor['rabuan'];
                $detailUrl = BASE_URL . '/modules/' . $ev['jenis'] . '/detail.php?id=' . $ev['id'];
                if ($ev['tanggal'] !== $lastDate):
                    $lastDate = $ev['tanggal'];
                    $isToday  = $ev['tanggal'] === date('Y-m-d');
            ?>
                <div style="display:flex; align-items:center; gap:.75rem; margin:<?= $lastDate === $ev['tanggal'] && $ev === reset($allEvents) ? '0' : '1rem' ?> 0 .5rem;">
                    <div style="width:48px; height:48px; border-radius:12px;
                        background:<?= $isToday ? 'var(--primary)' : 'var(--gray-100)' ?>;
                        display:flex; flex-direction:column; align-items:center; justify-content:center;
                        flex-shrink:0;">
                        <span style="font-size:.8rem; font-weight:800; color:<?= $isToday ? '#fff' : 'var(--gray-700)' ?>; line-height:1;">
                            <?= date('d', strtotime($ev['tanggal'])) ?>
                        </span>
                        <span style="font-size:.65rem; color:<?= $isToday ? 'rgba(255,255,255,.8)' : 'var(--gray-400)' ?>; text-transform:uppercase;">
                            <?= date('D', strtotime($ev['tanggal'])) ?>
                        </span>
                    </div>
                    <div style="flex:1; height:1px; background:var(--gray-100);"></div>
                    <span style="font-size:.8rem; font-weight:600; color:var(--gray-400);">
                        <?= formatTanggal($ev['tanggal'], 'd F Y') ?>
                    </span>
                </div>
            <?php endif; ?>

            <a href="<?= $detailUrl ?>"
               style="display:flex; align-items:center; gap:.875rem; padding:.875rem 1.25rem;
                      background:#fff; border-radius:10px; border:1px solid var(--gray-100);
                      border-left:4px solid <?= $c['border'] ?>; text-decoration:none;
                      margin-bottom:.4rem; transition:box-shadow .2s;"
               onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
                <div style="width:8px; height:8px; border-radius:50%; background:<?= $c['border'] ?>; flex-shrink:0;"></div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; font-size:.875rem; color:var(--gray-800); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= e($ev['nama']) ?>
                    </div>
                    <div style="font-size:.75rem; color:var(--gray-400); margin-top:.1rem;">
                        <?= $c['label'] ?>
                        <?= $ev['waktu_mulai'] ? ' · ' . formatWaktu($ev['waktu_mulai']) : '' ?>
                    </div>
                </div>
                <?= badgeStatus($ev['status']) ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Navigasi cepat ke bulan lain -->
    <div style="margin-top:1.5rem; display:flex; gap:.4rem; flex-wrap:wrap; justify-content:center;">
        <?php
        $namaBulanShort = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        for ($b = 1; $b <= 12; $b++):
            $isActive = $b === $bulan;
        ?>
        <a href="?bulan=<?= $b ?>&tahun=<?= $tahun ?>"
           class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $namaBulanShort[$b] ?>
        </a>
        <?php endfor; ?>
        <span style="color:var(--gray-300); align-self:center; margin:0 .25rem;">|</span>
        <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun - 1 ?>" class="btn btn-sm btn-secondary"><?= $tahun - 1 ?></a>
        <span class="btn btn-sm btn-primary" style="pointer-events:none;"><?= $tahun ?></span>
        <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun + 1 ?>" class="btn btn-sm btn-secondary"><?= $tahun + 1 ?></a>
    </div>
</div>

<script>
function switchView(view) {
    document.getElementById('viewCalendar').style.display = view === 'calendar' ? 'block' : 'none';
    document.getElementById('viewList').style.display     = view === 'list'     ? 'block' : 'none';
    document.getElementById('btnCalendar').className = 'btn ' + (view === 'calendar' ? 'btn-primary' : 'btn-secondary');
    document.getElementById('btnList').className     = 'btn ' + (view === 'list'     ? 'btn-primary' : 'btn-secondary');
    localStorage.setItem('jadwalView', view);
}

// Restore last view
const savedView = localStorage.getItem('jadwalView') || 'calendar';
switchView(savedView);
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
