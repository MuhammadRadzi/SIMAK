<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
require_once __DIR__ . '/../../includes/gdrive.php';

$db    = getDB();
$id    = (int)get('id');
$error = '';
$flash = getFlash();

$stmt = $db->prepare(
    "SELECT m.*, u.nama AS created_by_nama FROM mentoring m
     LEFT JOIN users u ON u.id = m.created_by WHERE m.id = ? LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$mentoring = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mentoring) {
    setFlash('error', 'Data Mentoring tidak ditemukan.');
    redirect(BASE_URL . '/modules/mentoring/index.php');
}

$pageTitle = 'Detail Mentoring';

// Handle upload bahan ajar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $result = handlePdfUpload('bahan_ajar', 'mentoring', 'BahanAjar_' . $id);
        if (isset($result['error'])) {
            $error = $result['error'];
        } elseif (!isset($result['skipped'])) {
            $me   = currentUser();
            $stmt = $db->prepare(
                "INSERT INTO mentoring_dokumen (mentoring_id, nama_file, gdrive_file_id, gdrive_link, ukuran_file, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('isssii', $id, $result['nama_file'], $result['gdrive_file_id'], $result['gdrive_link'], $result['ukuran_file'], $me['id']);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Bahan ajar berhasil diupload ke Google Drive.');
            redirect(BASE_URL . '/modules/mentoring/detail.php?id=' . $id);
        }
    }
}

// Handle hapus dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'hapus_dok') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $dokId = postInt('dok_id');
        $dok   = $db->prepare("SELECT gdrive_file_id FROM mentoring_dokumen WHERE id = ? AND mentoring_id = ?");
        $dok->bind_param('ii', $dokId, $id);
        $dok->execute();
        $dokData = $dok->get_result()->fetch_assoc();
        $dok->close();
        if ($dokData) {
            deleteFromGDrive($dokData['gdrive_file_id']);
            $del = $db->prepare("DELETE FROM mentoring_dokumen WHERE id = ?");
            $del->bind_param('i', $dokId);
            $del->execute(); $del->close();
            setFlash('success', 'Dokumen berhasil dihapus.');
            redirect(BASE_URL . '/modules/mentoring/detail.php?id=' . $id);
        }
    }
}

// Ambil dokumen
$dokStmt = $db->prepare("SELECT md.*, u.nama AS uploader FROM mentoring_dokumen md LEFT JOIN users u ON u.id = md.uploaded_by WHERE md.mentoring_id = ? ORDER BY md.uploaded_at DESC");
$dokStmt->bind_param('i', $id);
$dokStmt->execute();
$dokumen = $dokStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dokStmt->close();

// Presensi summary
$presensiSummary = $db->query(
    "SELECT status, COUNT(*) AS total FROM presensi
     WHERE jenis_kegiatan='mentoring' AND kegiatan_id=$id GROUP BY status"
)->fetch_all(MYSQLI_ASSOC);
$presensiMap   = [];
foreach ($presensiSummary as $p) $presensiMap[$p['status']] = $p['total'];
$totalPresensi = array_sum($presensiMap);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title"><?= e($mentoring['judul_materi']) ?></h2>
            <p class="page-sub">Detail Mentoring · <?= formatTanggal($mentoring['tanggal']) ?></p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=mentoring&id=<?= $id ?>" class="btn btn-secondary">📋 Input Presensi</a>
            <a href="<?= BASE_URL ?>/modules/mentoring/edit.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="<?= BASE_URL ?>/modules/mentoring/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:start;">

        <!-- Kolom Kiri -->
        <div style="display:flex; flex-direction:column; gap:1rem;">

            <!-- Info Mentoring -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informasi Sesi</span>
                    <?= badgeStatus($mentoring['status']) ?>
                </div>
                <div class="card-body">
                    <?php
                    $infos = [
                        ['Judul Materi', $mentoring['judul_materi']],
                        ['Mentor / Tutor',$mentoring['nama_mentor']],
                        ['Tanggal',      formatTanggal($mentoring['tanggal'])],
                        ['Waktu',        $mentoring['waktu_mulai']
                            ? formatWaktu($mentoring['waktu_mulai']) . ($mentoring['waktu_selesai'] ? ' – ' . formatWaktu($mentoring['waktu_selesai']) : '')
                            : '—'],
                        ['Lokasi',       $mentoring['lokasi'] ?? '—'],
                        ['Dibuat oleh',  $mentoring['created_by_nama'] ?? '—'],
                    ];
                    foreach ($infos as [$lbl, $val]):
                    ?>
                    <div style="display:flex; gap:1rem; padding:.5rem 0; border-bottom:1px solid var(--gray-100); font-size:.875rem;">
                        <span style="color:var(--gray-500); min-width:110px; flex-shrink:0;"><?= $lbl ?></span>
                        <span style="color:var(--gray-800); font-weight:500;"><?= e((string)$val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Catatan Logistik -->
            <?php if ($mentoring['catatan_logistik']): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📦 Kebutuhan Logistik</span>
                </div>
                <div class="card-body">
                    <div style="font-size:.875rem; color:var(--gray-700); white-space:pre-line; background:var(--warning-light); padding:.875rem; border-radius:8px; border-left:3px solid var(--warning);">
                        <?= e($mentoring['catatan_logistik']) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Presensi -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Presensi</span>
                    <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=mentoring&id=<?= $id ?>" class="btn btn-sm btn-primary">Input</a>
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

        <!-- Kolom Kanan: Bahan Ajar -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📚 Bahan Ajar</span>
                <span style="font-size:.75rem; color:var(--gray-400);"><?= count($dokumen) ?> file</span>
            </div>
            <div class="card-body">
                <!-- Form Upload -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid var(--gray-100);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label class="form-label">Upload Bahan Ajar (PDF)</label>
                        <input type="file" name="bahan_ajar" class="form-input" accept=".pdf" required>
                        <small class="form-hint">Maks. 10 MB · Format PDF · Tersimpan otomatis di Google Drive</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Upload ke Drive
                    </button>
                </form>

                <!-- Daftar Dokumen -->
                <?php if (empty($dokumen)): ?>
                    <div class="empty-state" style="padding:1.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        <p>Belum ada bahan ajar diupload.</p>
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
                                    <a href="<?= e($dok['gdrive_link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        Buka
                                    </a>
                                <?php endif; ?>
                                <form method="POST" style="display:inline" onsubmit="return confirmDelete('Hapus bahan ajar ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus_dok">
                                    <input type="hidden" name="dok_id" value="<?= $dok['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">✕</button>
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
