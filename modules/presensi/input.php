<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db      = getDB();
$jenis   = get('jenis');
$id      = (int)get('id');
$flash   = getFlash();
$error   = '';

// Validasi jenis
if (!in_array($jenis, ['rabuan','mentoring','binjas'])) {
    setFlash('error', 'Jenis kegiatan tidak valid.');
    redirect(BASE_URL . '/modules/presensi/index.php');
}

// Ambil info kegiatan
$kegiatan = null;
switch ($jenis) {
    case 'rabuan':
        $s = $db->prepare("SELECT id, judul AS nama, tanggal FROM rabuan WHERE id=? LIMIT 1");
        break;
    case 'mentoring':
        $s = $db->prepare("SELECT id, judul_materi AS nama, tanggal FROM mentoring WHERE id=? LIMIT 1");
        break;
    case 'binjas':
        $s = $db->prepare("SELECT id, nama_sesi AS nama, tanggal FROM binjas WHERE id=? LIMIT 1");
        break;
}
$s->bind_param('i', $id); $s->execute();
$kegiatan = $s->get_result()->fetch_assoc(); $s->close();

if (!$kegiatan) {
    setFlash('error', 'Kegiatan tidak ditemukan.');
    redirect(BASE_URL . '/modules/presensi/index.php');
}

$pageTitle = 'Presensi — ' . $kegiatan['nama'];

// Handle simpan presensi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $statusArr    = $_POST['status']      ?? [];
        $keteranganArr= $_POST['keterangan']  ?? [];
        $me           = currentUser();
        $saved        = 0;

        foreach ($statusArr as $siswaId => $status) {
            if (!in_array($status, ['hadir','izin','sakit','alpha'])) continue;
            $ket = trim($keteranganArr[$siswaId] ?? '');
            $ket = !empty($ket) ? $ket : null;

            $stmt = $db->prepare(
                "INSERT INTO presensi (jenis_kegiatan, kegiatan_id, siswa_id, status, keterangan, dicatat_oleh)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE status=VALUES(status), keterangan=VALUES(keterangan), dicatat_oleh=VALUES(dicatat_oleh), dicatat_pada=NOW()"
            );
            $stmt->bind_param('ssissi', $jenis, $id, $siswaId, $status, $ket, $me['id']);
            $stmt->execute(); $stmt->close();
            $saved++;
        }

        setFlash('success', "Presensi $saved siswa berhasil disimpan.");
        redirect(BASE_URL . '/modules/presensi/input.php?jenis=' . $jenis . '&id=' . $id);
    }
}

// Ambil semua siswa aktif
$allSiswa = $db->query("SELECT id, nama, nis, regu FROM siswa WHERE is_active=1 ORDER BY regu, nama")->fetch_all(MYSQLI_ASSOC);

// Presensi yang sudah ada
$existing = [];
$eStmt = $db->prepare("SELECT siswa_id, status, keterangan FROM presensi WHERE jenis_kegiatan=? AND kegiatan_id=?");
$eStmt->bind_param('si', $jenis, $id); $eStmt->execute();
foreach ($eStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $e) {
    $existing[$e['siswa_id']] = ['status'=>$e['status'],'keterangan'=>$e['keterangan']];
}
$eStmt->close();

// Group per regu
$perRegu = [];
foreach ($allSiswa as $s) {
    $regu = $s['regu'] ?: 'Tanpa Regu';
    $perRegu[$regu][] = $s;
}

// Hitung summary
$summary = ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0,'belum'=>0];
foreach ($allSiswa as $s) {
    $st = $existing[$s['id']]['status'] ?? null;
    if ($st) $summary[$st]++;
    else     $summary['belum']++;
}

// Back URL
$backUrl = match($jenis) {
    'rabuan'    => BASE_URL . '/modules/rabuan/detail.php?id=' . $id,
    'mentoring' => BASE_URL . '/modules/mentoring/detail.php?id=' . $id,
    'binjas'    => BASE_URL . '/modules/binjas/detail.php?id=' . $id,
    default     => BASE_URL . '/modules/presensi/index.php',
};

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Input Presensi</h2>
            <p class="page-sub"><?= e($kegiatan['nama']) ?> · <?= formatTanggal($kegiatan['tanggal']) ?></p>
        </div>
        <a href="<?= $backUrl ?>" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <!-- Summary -->
    <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:.75rem; margin-bottom:1rem;">
        <?php foreach ([
            ['Hadir',  $summary['hadir'], 'var(--success)'],
            ['Izin',   $summary['izin'],  'var(--warning)'],
            ['Sakit',  $summary['sakit'], 'var(--info)'],
            ['Alpha',  $summary['alpha'], 'var(--danger)'],
            ['Belum',  $summary['belum'], 'var(--gray-400)'],
        ] as [$lbl,$cnt,$color]): ?>
        <div style="background:#fff; border-radius:10px; padding:.875rem; text-align:center; border:1px solid var(--gray-200);">
            <div style="font-size:1.5rem; font-weight:800; color:<?= $color ?>;"><?= $cnt ?></div>
            <div style="font-size:.75rem; color:var(--gray-400);"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tombol set semua hadir -->
    <div style="margin-bottom:.75rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <button type="button" onclick="setAllStatus('hadir')" class="btn btn-sm btn-success">✓ Set Semua Hadir</button>
        <button type="button" onclick="setAllStatus('alpha')" class="btn btn-sm btn-danger">✗ Set Semua Alpha</button>
        <span style="font-size:.8rem; color:var(--gray-400); align-self:center; margin-left:.5rem;">Total: <?= count($allSiswa) ?> siswa</span>
    </div>

    <form method="POST" id="formPresensi">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <?php foreach ($perRegu as $reguNama => $siswaList): ?>
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header">
                <span class="card-title">👥 <?= e($reguNama) ?></span>
                <span style="font-size:.8rem; color:var(--gray-400);"><?= count($siswaList) ?> siswa</span>
            </div>
            <div class="table-responsive">
                <table class="table" style="font-size:.875rem;">
                    <thead>
                        <tr>
                            <th style="width:200px;">Nama Siswa</th>
                            <th style="width:80px; text-align:center;">Hadir</th>
                            <th style="width:80px; text-align:center;">Izin</th>
                            <th style="width:80px; text-align:center;">Sakit</th>
                            <th style="width:80px; text-align:center;">Alpha</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($siswaList as $s):
                        $curStatus = $existing[$s['id']]['status'] ?? 'alpha';
                        $curKet    = $existing[$s['id']]['keterangan'] ?? '';
                    ?>
                    <tr id="row-<?= $s['id'] ?>" class="presensi-row" data-siswa="<?= $s['id'] ?>"
                        style="background:<?= match($curStatus){
                            'hadir'=>'var(--success-light)',
                            'izin' =>'var(--warning-light)',
                            'sakit'=>'var(--info-light)',
                            default=>isset($existing[$s['id']])?'var(--danger-light)':'#fff'
                        } ?>">
                        <td>
                            <div style="font-weight:600;"><?= e($s['nama']) ?></div>
                            <div style="font-size:.7rem; color:var(--gray-400);"><?= e($s['nis']) ?></div>
                        </td>
                        <?php foreach (['hadir','izin','sakit','alpha'] as $st): ?>
                        <td style="text-align:center;">
                            <input type="radio"
                                   name="status[<?= $s['id'] ?>]"
                                   value="<?= $st ?>"
                                   <?= $curStatus === $st ? 'checked' : '' ?>
                                   onchange="updateRowColor(<?= $s['id'] ?>, '<?= $st ?>')"
                                   style="width:18px; height:18px; cursor:pointer; accent-color:<?= match($st){'hadir'=>'var(--success)','izin'=>'var(--warning)','sakit'=>'var(--info)',default=>'var(--danger)'} ?>;">
                        </td>
                        <?php endforeach; ?>
                        <td>
                            <input type="text" name="keterangan[<?= $s['id'] ?>]"
                                   class="form-input" style="padding:.35rem .6rem; font-size:.8rem;"
                                   value="<?= e($curKet) ?>"
                                   placeholder="Opsional...">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="position:sticky; bottom:1rem; background:#fff; padding:.875rem; border-radius:10px; box-shadow:var(--shadow-lg); display:flex; gap:.75rem; align-items:center;">
            <button type="submit" class="btn btn-primary">💾 Simpan Presensi</button>
            <a href="<?= $backUrl ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
const rowColors = {
    hadir: 'var(--success-light)',
    izin:  'var(--warning-light)',
    sakit: 'var(--info-light)',
    alpha: 'var(--danger-light)',
};

function updateRowColor(siswaId, status) {
    document.getElementById('row-' + siswaId).style.background = rowColors[status];
}

function setAllStatus(status) {
    document.querySelectorAll('.presensi-row').forEach(row => {
        const sid = row.dataset.siswa;
        const radio = document.querySelector(`input[name="status[${sid}]"][value="${status}"]`);
        if (radio) { radio.checked = true; row.style.background = rowColors[status]; }
    });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
