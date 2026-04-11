<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
require_once __DIR__ . '/../../includes/gdrive.php';

$db    = getDB();
$id    = (int)get('id');
$error = '';
$flash = getFlash();

$stmt = $db->prepare(
    "SELECT r.*, u.nama AS created_by_nama FROM rabuan r
     LEFT JOIN users u ON u.id = r.created_by WHERE r.id = ? LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$rabuan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rabuan) {
    setFlash('error', 'Data Rabuan tidak ditemukan.');
    redirect(BASE_URL . '/modules/rabuan/index.php');
}

$pageTitle = 'Detail Rabuan';

// Handle upload notulensi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $result = handlePdfUpload('notulensi', 'rabuan', 'Notulensi_' . $id);
        if (isset($result['error'])) {
            $error = $result['error'];
        } elseif (!isset($result['skipped'])) {
            $stmt = $db->prepare(
                "INSERT INTO rabuan_dokumen (rabuan_id, nama_file, gdrive_file_id, gdrive_link, ukuran_file, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $me = currentUser();
            $stmt->bind_param('isssii', $id, $result['nama_file'], $result['gdrive_file_id'], $result['gdrive_link'], $result['ukuran_file'], $me['id']);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Notulensi berhasil diupload ke Google Drive.');
            redirect(BASE_URL . '/modules/rabuan/detail.php?id=' . $id);
        }
    }
}

// Handle hapus dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'hapus_dok') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $dokId = postInt('dok_id');
        $dok   = $db->prepare("SELECT gdrive_file_id FROM rabuan_dokumen WHERE id = ? AND rabuan_id = ?");
        $dok->bind_param('ii', $dokId, $id);
        $dok->execute();
        $dokData = $dok->get_result()->fetch_assoc();
        $dok->close();
        if ($dokData) {
            deleteFromGDrive($dokData['gdrive_file_id']);
            $del = $db->prepare("DELETE FROM rabuan_dokumen WHERE id = ?");
            $del->bind_param('i', $dokId);
            $del->execute(); $del->close();
            setFlash('success', 'Dokumen berhasil dihapus.');
            redirect(BASE_URL . '/modules/rabuan/detail.php?id=' . $id);
        }
    }
}

// Ambil dokumen
$dokList = $db->prepare("SELECT rd.*, u.nama AS uploader FROM rabuan_dokumen rd LEFT JOIN users u ON u.id = rd.uploaded_by WHERE rd.rabuan_id = ? ORDER BY rd.uploaded_at DESC");
$dokList->bind_param('i', $id);
$dokList->execute();
$dokumen = $dokList->get_result()->fetch_all(MYSQLI_ASSOC);
$dokList->close();

// Ambil presensi summary
$presensiSummary = $db->query(
    "SELECT status, COUNT(*) AS total FROM presensi
     WHERE jenis_kegiatan='rabuan' AND kegiatan_id=$id GROUP BY status"
)->fetch_all(MYSQLI_ASSOC);
$presensiMap = [];
foreach ($presensiSummary as $p) $presensiMap[$p['status']] = $p['total'];
$totalPresensi = array_sum($presensiMap);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title"><?= e($rabuan['judul']) ?></h2>
            <p class="page-sub">Detail Rabuan · <?= formatTanggal($rabuan['tanggal']) ?></p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=rabuan&id=<?= $id ?>" class="btn btn-secondary">📋 Input Presensi</a>
            <a href="<?= BASE_URL ?>/modules/rabuan/edit.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:start;">

        <!-- Info Rabuan -->
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informasi Rapat</span>
                    <?= badgeStatus($rabuan['status']) ?>
                </div>
                <div class="card-body">
                    <?php
                    $infos = [
                        ['Tanggal',   formatTanggal($rabuan['tanggal'])],
                        ['Waktu',     $rabuan['waktu_mulai'] ? formatWaktu($rabuan['waktu_mulai']) . ($rabuan['waktu_selesai'] ? ' – ' . formatWaktu($rabuan['waktu_selesai']) : '') : '—'],
                        ['Lokasi',    $rabuan['lokasi'] ?? '—'],
                        ['Dibuat oleh', $rabuan['created_by_nama'] ?? '—'],
                    ];
                    foreach ($infos as [$lbl, $val]):
                    ?>
                    <div style="display:flex; gap:1rem; padding:.5rem 0; border-bottom:1px solid var(--gray-100); font-size:.875rem;">
                        <span style="color:var(--gray-500); min-width:100px;"><?= $lbl ?></span>
                        <span style="color:var(--gray-800); font-weight:500;"><?= e((string)$val) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($rabuan['agenda']): ?>
                    <div style="margin-top:.75rem;">
                        <div style="font-size:.8rem; font-weight:600; color:var(--gray-500); margin-bottom:.35rem;">AGENDA</div>
                        <div style="font-size:.875rem; color:var(--gray-700); white-space:pre-line; background:var(--gray-50); padding:.75rem; border-radius:8px;"><?= e($rabuan['agenda']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ringkasan Presensi -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Presensi</span>
                    <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=rabuan&id=<?= $id ?>" class="btn btn-sm btn-primary">Input</a>
                </div>
                <div class="card-body">
                    <?php if ($totalPresensi === 0): ?>
                        <p style="color:var(--gray-400); font-size:.875rem; text-align:center;">Belum ada data presensi.</p>
                    <?php else: ?>
                        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem; text-align:center;">
                        <?php foreach (['hadir'=>['Hadir','var(--success)'],'izin'=>['Izin','var(--warning)'],'sakit'=>['Sakit','var(--info)'],'alpha'=>['Alpha','var(--danger)']] as $key=>[$lbl,$color]): ?>
                            <div style="padding:.6rem; background:var(--gray-50); border-radius:8px;">
                                <div style="font-size:1.25rem; font-weight:800; color:<?= $color ?>;"><?= $presensiMap[$key] ?? 0 ?></div>
                                <div style="font-size:.7rem; color:var(--gray-500);"><?= $lbl ?></div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notulensi -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📄 Notulensi Rapat</span>
                <span style="font-size:.75rem; color:var(--gray-400);"><?= count($dokumen) ?> dokumen</span>
            </div>
            <div class="card-body">
                <!-- Form Upload -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid var(--gray-100);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label class="form-label">Upload Notulensi (PDF)</label>
                        <input type="file" name="notulensi" class="form-input" accept=".pdf" required>
                        <small class="form-hint">Maks. 10 MB · Format PDF · Akan otomatis tersimpan di Google Drive</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Upload ke Drive
                    </button>
                </form>

                <!-- Daftar Dokumen -->
                <?php if (empty($dokumen)): ?>
                    <div class="empty-state" style="padding:1.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <p>Belum ada notulensi diupload.</p>
                    </div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:.6rem;">
                    <?php foreach ($dokumen as $dok): ?>
                        <div style="display:flex; align-items:center; gap:.75rem; padding:.75rem; background:var(--gray-50); border-radius:10px; border:1px solid var(--gray-200);">
                            <div style="width:36px; height:36px; background:#fee2e2; color:#dc2626; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.65rem; font-weight:800;">PDF</div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:.8125rem; font-weight:600; color:var(--gray-800); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($dok['nama_file']) ?></div>
                                <div style="font-size:.7rem; color:var(--gray-400);"><?= formatBytes($dok['ukuran_file']) ?> · <?= formatTanggal($dok['uploaded_at'], 'd M Y H:i') ?> · <?= e($dok['uploader'] ?? '—') ?></div>
                            </div>
                            <div style="display:flex; gap:.3rem; flex-shrink:0;">
                                <?php if ($dok['gdrive_link']): ?>
                                    <a href="<?= e($dok['gdrive_link']) ?>" target="_blank" class="btn btn-sm btn-primary" title="Buka di Drive">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        Buka
                                    </a>
                                <?php endif; ?>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Hapus dokumen ini dari sistem dan Google Drive?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus_dok">
                                    <input type="hidden" name="dok_id" value="<?= $dok['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus">✕</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
