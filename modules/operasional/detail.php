<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
require_once __DIR__ . '/../../includes/gdrive.php';

$db    = getDB();
$id    = (int)get('id');
$error = '';
$flash = getFlash();

$stmt = $db->prepare("SELECT o.*, u.nama AS created_by_nama FROM operasional o LEFT JOIN users u ON u.id = o.created_by WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$ops = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ops) { setFlash('error', 'Data tidak ditemukan.'); redirect(BASE_URL . '/modules/operasional/index.php'); }

$pageTitle = 'Detail Operasional';

// ── Handle POST actions ──────────────────────────────────────

// Simpan pra-operasional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save_pra') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $kesiapan = post('kesiapan_peserta') ?: null;
        $perbekal = post('perbekalan_regu')  ?: null;
        $catatan  = post('catatan_tambahan') ?: null;
        $me       = currentUser();

        $stmt = $db->prepare("SELECT id FROM operasional_pra WHERE operasional_id = ?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->store_result();
        $exists = $stmt->num_rows > 0; $stmt->close();

        if ($exists) {
            $stmt = $db->prepare("UPDATE operasional_pra SET kesiapan_peserta=?, perbekalan_regu=?, catatan_tambahan=?, updated_by=? WHERE operasional_id=?");
            $stmt->bind_param('sssii', $kesiapan, $perbekal, $catatan, $me['id'], $id);
        } else {
            $stmt = $db->prepare("INSERT INTO operasional_pra (operasional_id, kesiapan_peserta, perbekalan_regu, catatan_tambahan, updated_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param('isssi', $id, $kesiapan, $perbekal, $catatan, $me['id']);
        }
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Data Pra-Operasional disimpan.');
        redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Tambah peserta siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'add_siswa') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $siswaId = postInt('siswa_id');
        $peran   = post('peran') ?: null;
        $stmt = $db->prepare("INSERT IGNORE INTO operasional_siswa (operasional_id, siswa_id, peran) VALUES (?,?,?)");
        $stmt->bind_param('iis', $id, $siswaId, $peran);
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Siswa berhasil ditambahkan.');
        redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Hapus peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'del_siswa') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $osId = postInt('os_id');
        $stmt = $db->prepare("DELETE FROM operasional_siswa WHERE id = ? AND operasional_id = ?");
        $stmt->bind_param('ii', $osId, $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Peserta dihapus.'); redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Tambah peralatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'add_alat') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $namaAlat = post('nama_alat'); $jenis = post('jenis');
        $jumlah   = postInt('jumlah') ?: 1; $satuan = post('satuan') ?: null;
        if (!empty($namaAlat)) {
            $stmt = $db->prepare("INSERT INTO operasional_peralatan (operasional_id, nama_alat, jenis, jumlah, satuan) VALUES (?,?,?,?,?)");
            $stmt->bind_param('issis', $id, $namaAlat, $jenis, $jumlah, $satuan);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Peralatan ditambahkan.');
        }
        redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Hapus peralatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'del_alat') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $alatId = postInt('alat_id');
        $stmt = $db->prepare("DELETE FROM operasional_peralatan WHERE id = ? AND operasional_id = ?");
        $stmt->bind_param('ii', $alatId, $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Peralatan dihapus.'); redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Maju fase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'maju_fase') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $faseBaru = ($ops['fase'] === 'pra') ? 'operasional' : 'pasca';
        $stmt = $db->prepare("UPDATE operasional SET fase=? WHERE id=?");
        $stmt->bind_param('si', $faseBaru, $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Fase berhasil dimajukan ke ' . ucfirst($faseBaru) . '.');
        redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// Upload laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload_laporan') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $result = handlePdfUpload('laporan', 'operasional', 'Laporan_' . $id);
        if (isset($result['error'])) { $error = $result['error']; }
        elseif (!isset($result['skipped'])) {
            $me = currentUser();
            $stmt = $db->prepare("INSERT INTO operasional_laporan (operasional_id, nama_file, gdrive_file_id, gdrive_link, ukuran_file, uploaded_by) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('isssii', $id, $result['nama_file'], $result['gdrive_file_id'], $result['gdrive_link'], $result['ukuran_file'], $me['id']);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Laporan berhasil diupload ke Google Drive.');
            redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
        }
    }
}

// Simpan kondisi alat (pasca)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save_kondisi') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $kondisiArr = $_POST['kondisi'] ?? [];
        $catatanArr = $_POST['catatan_kondisi'] ?? [];
        foreach ($kondisiArr as $alatId => $kondisi) {
            $catKondisi = $catatanArr[$alatId] ?? null;
            $stmt = $db->prepare("UPDATE operasional_peralatan SET kondisi=?, catatan_kondisi=? WHERE id=? AND operasional_id=?");
            $stmt->bind_param('ssii', $kondisi, $catKondisi, $alatId, $id);
            $stmt->execute(); $stmt->close();
        }
        setFlash('success', 'Kondisi peralatan disimpan.');
        redirect(BASE_URL . '/modules/operasional/detail.php?id=' . $id);
    }
}

// ── Load data ────────────────────────────────────────────────
// Reload ops setelah possible fase change
$stmt = $db->prepare("SELECT o.*, u.nama AS created_by_nama FROM operasional o LEFT JOIN users u ON u.id = o.created_by WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $id); $stmt->execute();
$ops = $stmt->get_result()->fetch_assoc(); $stmt->close();

// Pra data
$praStmt = $db->prepare("SELECT * FROM operasional_pra WHERE operasional_id = ? LIMIT 1");
$praStmt->bind_param('i', $id); $praStmt->execute();
$pra = $praStmt->get_result()->fetch_assoc(); $praStmt->close();

// Peserta
$pesertaStmt = $db->prepare("SELECT os.*, s.nama, s.nis, s.regu FROM operasional_siswa os JOIN siswa s ON s.id = os.siswa_id WHERE os.operasional_id = ? ORDER BY s.nama");
$pesertaStmt->bind_param('i', $id); $pesertaStmt->execute();
$peserta = $pesertaStmt->get_result()->fetch_all(MYSQLI_ASSOC); $pesertaStmt->close();
$pesertaIds = array_column($peserta, 'siswa_id');

// Peralatan
$alatStmt = $db->prepare("SELECT * FROM operasional_peralatan WHERE operasional_id = ? ORDER BY jenis, nama_alat");
$alatStmt->bind_param('i', $id); $alatStmt->execute();
$peralatan = $alatStmt->get_result()->fetch_all(MYSQLI_ASSOC); $alatStmt->close();
$alatPribadi = array_filter($peralatan, fn($a) => $a['jenis'] === 'pribadi');
$alatRegu    = array_filter($peralatan, fn($a) => $a['jenis'] === 'regu');

// Laporan
$laporanStmt = $db->prepare("SELECT ol.*, u.nama AS uploader FROM operasional_laporan ol LEFT JOIN users u ON u.id = ol.uploaded_by WHERE ol.operasional_id = ? ORDER BY ol.uploaded_at DESC");
$laporanStmt->bind_param('i', $id); $laporanStmt->execute();
$laporan = $laporanStmt->get_result()->fetch_all(MYSQLI_ASSOC); $laporanStmt->close();

// Siswa belum terdaftar (untuk form tambah peserta)
$allSiswa = $db->query("SELECT id, nama, nis, regu FROM siswa WHERE is_active=1 ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

// Fase helpers
$faseLabel = ['pra'=>'Pra-Operasional','operasional'=>'Operasional','pasca'=>'Pasca-Operasional'];
$faseBadge = ['pra'=>'badge-warning','operasional'=>'badge-primary','pasca'=>'badge-info'];
$kondisiLabel = ['layak'=>'Layak','tidak_layak'=>'Tidak Layak','butuh_perbaikan'=>'Butuh Perbaikan'];
$kondisiColor = ['layak'=>'var(--success)','tidak_layak'=>'var(--danger)','butuh_perbaikan'=>'var(--warning)'];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title"><?= e($ops['nama_kegiatan']) ?></h2>
            <p class="page-sub">Detail Kegiatan Operasional</p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/operasional/edit.php?id=<?= $id ?>" class="btn btn-warning">Edit</a>
            <a href="<?= BASE_URL ?>/modules/operasional/index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <!-- Fase Progress Bar -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:1rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:0;">
                <?php
                $fases = ['pra'=>'1. Pra-Operasional','operasional'=>'2. Operasional','pasca'=>'3. Pasca-Operasional'];
                $faseOrder = array_keys($fases);
                $currentIdx = array_search($ops['fase'], $faseOrder);
                foreach ($fases as $fKey => $fNama):
                    $fIdx    = array_search($fKey, $faseOrder);
                    $isDone  = $fIdx < $currentIdx;
                    $isActive= $fKey === $ops['fase'];
                ?>
                <div style="flex:1; text-align:center; padding:.5rem; border-radius:8px;
                    background:<?= $isActive ? 'var(--primary)' : ($isDone ? 'var(--success-light)' : 'var(--gray-100)') ?>;
                    color:<?= $isActive ? '#fff' : ($isDone ? 'var(--success)' : 'var(--gray-400)') ?>;
                    font-size:.8125rem; font-weight:<?= $isActive ? '700' : '500' ?>;">
                    <?= $isDone ? '✓ ' : '' ?><?= $fNama ?>
                </div>
                <?php if ($fKey !== 'pasca'): ?>
                <div style="width:20px; height:2px; background:var(--gray-200); flex-shrink:0;"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Tombol Maju Fase -->
            <?php if ($ops['fase'] !== 'pasca' && $ops['status'] !== 'batal'): ?>
            <div style="margin-top:.875rem; text-align:right;">
                <form method="POST" onsubmit="return confirm('Majukan ke fase berikutnya? Pastikan data sudah lengkap.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="maju_fase">
                    <button type="submit" class="btn btn-success btn-sm">
                        Maju ke <?= $ops['fase'] === 'pra' ? 'Operasional' : 'Pasca-Operasional' ?> →
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Umum -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
        <div class="card">
            <div class="card-header"><span class="card-title">Informasi Kegiatan</span><?= badgeStatus($ops['status']) ?></div>
            <div class="card-body">
                <?php foreach ([
                    ['Nama Kegiatan', $ops['nama_kegiatan']],
                    ['Lokasi',        $ops['lokasi'] ?? '—'],
                    ['Tanggal Mulai', formatTanggal($ops['tanggal_mulai'])],
                    ['Tanggal Selesai', $ops['tanggal_selesai'] ? formatTanggal($ops['tanggal_selesai']) : '—'],
                    ['Dibuat oleh',   $ops['created_by_nama'] ?? '—'],
                ] as [$lbl, $val]): ?>
                <div style="display:flex; gap:1rem; padding:.4rem 0; border-bottom:1px solid var(--gray-100); font-size:.875rem;">
                    <span style="color:var(--gray-500); min-width:110px;"><?= $lbl ?></span>
                    <span style="color:var(--gray-800); font-weight:500;"><?= e((string)$val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Daftar Peserta -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">👥 Peserta (<?= count($peserta) ?>)</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if ($ops['fase'] === 'pra'): ?>
                <form method="POST" style="padding:.875rem; border-bottom:1px solid var(--gray-100); display:flex; gap:.5rem; flex-wrap:wrap;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_siswa">
                    <select name="siswa_id" class="form-select" style="flex:1; min-width:150px;" required>
                        <option value="">Pilih Siswa...</option>
                        <?php foreach ($allSiswa as $s): ?>
                            <?php if (!in_array($s['id'], $pesertaIds)): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['nama']) ?> (<?= e($s['nis']) ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="peran" class="form-input" placeholder="Peran (opsional)" style="width:130px;">
                    <button type="submit" class="btn btn-primary btn-sm">+ Tambah</button>
                </form>
                <?php endif; ?>
                <div style="max-height:220px; overflow-y:auto;">
                <?php if (empty($peserta)): ?>
                    <p style="padding:1rem; text-align:center; color:var(--gray-400); font-size:.875rem;">Belum ada peserta.</p>
                <?php else: ?>
                    <table class="table" style="font-size:.8125rem;">
                        <thead><tr><th>Nama</th><th>Regu</th><th>Peran</th><?php if ($ops['fase']==='pra'): ?><th></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($peserta as $p): ?>
                        <tr>
                            <td><strong><?= e($p['nama']) ?></strong><br><code style="font-size:.7rem;"><?= e($p['nis']) ?></code></td>
                            <td><?= e($p['regu'] ?? '—') ?></td>
                            <td><?= e($p['peran'] ?? '—') ?></td>
                            <?php if ($ops['fase']==='pra'): ?>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus peserta ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="del_siswa">
                                    <input type="hidden" name="os_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" style="padding:.2rem .4rem;">✕</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================
         FASE: PRA-OPERASIONAL
         ====================================================== -->
    <?php if ($ops['fase'] === 'pra'): ?>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">

        <!-- Form Pra-Operasional -->
        <div class="card">
            <div class="card-header"><span class="card-title">📋 Data Pra-Operasional</span></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_pra">
                    <div class="form-group">
                        <label class="form-label">Kesiapan Peserta</label>
                        <textarea name="kesiapan_peserta" class="form-textarea" rows="3"
                            placeholder="Catatan kesiapan fisik, mental, administrasi peserta..."><?= e($pra['kesiapan_peserta'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Perbekalan Regu</label>
                        <textarea name="perbekalan_regu" class="form-textarea" rows="3"
                            placeholder="Daftar perbekalan regu (makanan, minuman, obat-obatan, dll)..."><?= e($pra['perbekalan_regu'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="catatan_tambahan" class="form-textarea" rows="2"
                            placeholder="Catatan lainnya..."><?= e($pra['catatan_tambahan'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Data Pra-Ops</button>
                </form>
            </div>
        </div>

        <!-- Manajemen Peralatan -->
        <div class="card">
            <div class="card-header"><span class="card-title">🎒 Daftar Peralatan</span></div>
            <div class="card-body" style="padding:0;">
                <!-- Form tambah alat -->
                <form method="POST" style="padding:.875rem; border-bottom:1px solid var(--gray-100);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_alat">
                    <div style="display:grid; grid-template-columns:1fr auto; gap:.5rem; margin-bottom:.5rem;">
                        <input type="text" name="nama_alat" class="form-input" placeholder="Nama alat..." required>
                        <select name="jenis" class="form-select" style="width:100px;">
                            <option value="pribadi">Pribadi</option>
                            <option value="regu">Regu</option>
                        </select>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:.5rem;">
                        <input type="number" name="jumlah" class="form-input" placeholder="Jumlah" min="1" value="1">
                        <input type="text" name="satuan" class="form-input" placeholder="Satuan (buah, kg...)">
                        <button type="submit" class="btn btn-primary btn-sm">+ Tambah</button>
                    </div>
                </form>

                <?php foreach (['pribadi'=>'👤 Pribadi','regu'=>'👥 Regu'] as $jenis=>$jenisLabel):
                    $alatList = ($jenis === 'pribadi') ? $alatPribadi : $alatRegu;
                ?>
                <div style="padding:.5rem .875rem; background:var(--gray-50); font-size:.75rem; font-weight:700; color:var(--gray-500); text-transform:uppercase; letter-spacing:.05em;">
                    <?= $jenisLabel ?> (<?= count($alatList) ?>)
                </div>
                <?php if (empty($alatList)): ?>
                    <p style="padding:.5rem .875rem; font-size:.8rem; color:var(--gray-400);">Belum ada peralatan <?= $jenis ?>.</p>
                <?php else: ?>
                    <?php foreach ($alatList as $alat): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:.5rem .875rem; border-bottom:1px solid var(--gray-100); font-size:.8125rem;">
                        <span><?= e($alat['nama_alat']) ?> <span style="color:var(--gray-400);">(<?= $alat['jumlah'] ?> <?= e($alat['satuan'] ?? '') ?>)</span></span>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus alat ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="del_alat">
                            <input type="hidden" name="alat_id" value="<?= $alat['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" style="padding:.15rem .4rem; font-size:.7rem;">✕</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ======================================================
         FASE: OPERASIONAL
         ====================================================== -->
    <?php elseif ($ops['fase'] === 'operasional'): ?>
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
            <span class="card-title">📁 Laporan Hasil Kegiatan</span>
            <span style="font-size:.75rem; color:var(--gray-400);"><?= count($laporan) ?> file</span>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" style="margin-bottom:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid var(--gray-100);">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="upload_laporan">
                <div class="form-group">
                    <label class="form-label">Upload Laporan Hasil Kegiatan (PDF)</label>
                    <input type="file" name="laporan" class="form-input" accept=".pdf" required>
                    <small class="form-hint">Maks. 10 MB · Format PDF · Tersimpan di Google Drive</small>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload Laporan ke Drive
                </button>
            </form>

            <?php if (empty($laporan)): ?>
                <div class="empty-state" style="padding:1.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p>Belum ada laporan diupload.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:.6rem;">
                <?php foreach ($laporan as $lap): ?>
                    <div style="display:flex; align-items:center; gap:.75rem; padding:.75rem; background:var(--gray-50); border-radius:10px; border:1px solid var(--gray-200);">
                        <div style="width:36px; height:36px; background:#fee2e2; color:#dc2626; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:800; flex-shrink:0;">PDF</div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:.8125rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($lap['nama_file']) ?></div>
                            <div style="font-size:.7rem; color:var(--gray-400);"><?= formatBytes($lap['ukuran_file']) ?> · <?= formatTanggal($lap['uploaded_at'], 'd M Y') ?> · <?= e($lap['uploader'] ?? '—') ?></div>
                        </div>
                        <?php if ($lap['gdrive_link']): ?>
                            <a href="<?= e($lap['gdrive_link']) ?>" target="_blank" class="btn btn-sm btn-primary">Buka</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======================================================
         FASE: PASCA-OPERASIONAL
         ====================================================== -->
    <?php elseif ($ops['fase'] === 'pasca'): ?>
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
            <span class="card-title">🔧 Checklist Pemeliharaan Alat</span>
            <span style="font-size:.75rem; color:var(--gray-400);">Total <?= count($peralatan) ?> peralatan</span>
        </div>
        <div class="card-body">
            <?php if (empty($peralatan)): ?>
                <div class="empty-state"><p>Tidak ada data peralatan untuk dicek.</p></div>
            <?php else: ?>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_kondisi">

                <?php foreach (['pribadi'=>'👤 Peralatan Pribadi','regu'=>'👥 Peralatan Regu'] as $jenis=>$jenisLabel):
                    $alatList = array_filter($peralatan, fn($a) => $a['jenis'] === $jenis);
                    if (empty($alatList)) continue;
                ?>
                <div style="margin-bottom:1.25rem;">
                    <div style="font-size:.8rem; font-weight:700; color:var(--gray-600); text-transform:uppercase; letter-spacing:.05em; margin-bottom:.75rem; padding-bottom:.4rem; border-bottom:2px solid var(--gray-200);">
                        <?= $jenisLabel ?>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.5rem;">
                    <?php foreach ($alatList as $alat): ?>
                        <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:.75rem; align-items:center; padding:.75rem; background:var(--gray-50); border-radius:8px;">
                            <div>
                                <div style="font-weight:600; font-size:.875rem;"><?= e($alat['nama_alat']) ?></div>
                                <div style="font-size:.75rem; color:var(--gray-400);"><?= $alat['jumlah'] ?> <?= e($alat['satuan'] ?? '') ?></div>
                            </div>
                            <select name="kondisi[<?= $alat['id'] ?>]" class="form-select" style="width:160px;" required>
                                <option value="">Pilih kondisi...</option>
                                <?php foreach (['layak'=>'✅ Layak','tidak_layak'=>'❌ Tidak Layak','butuh_perbaikan'=>'⚠️ Butuh Perbaikan'] as $kVal=>$kLbl): ?>
                                    <option value="<?= $kVal ?>" <?= ($alat['kondisi']??'')===$kVal?'selected':'' ?>><?= $kLbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="catatan_kondisi[<?= $alat['id'] ?>]"
                                   class="form-input" placeholder="Catatan kondisi..."
                                   value="<?= e($alat['catatan_kondisi'] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Simpan Kondisi Peralatan</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ringkasan kondisi -->
    <?php if (!empty($peralatan)):
        $layak = count(array_filter($peralatan, fn($a) => $a['kondisi']==='layak'));
        $rusak = count(array_filter($peralatan, fn($a) => $a['kondisi']==='tidak_layak'));
        $perlu = count(array_filter($peralatan, fn($a) => $a['kondisi']==='butuh_perbaikan'));
    ?>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem;">
        <?php foreach ([['✅ Layak',$layak,'var(--success)','var(--success-light)'],['❌ Tidak Layak',$rusak,'var(--danger)','var(--danger-light)'],['⚠️ Butuh Perbaikan',$perlu,'var(--warning)','var(--warning-light)']] as [$lbl,$cnt,$color,$bg]): ?>
        <div style="background:<?= $bg ?>; border-radius:10px; padding:1rem; text-align:center;">
            <div style="font-size:1.75rem; font-weight:800; color:<?= $color ?>;"><?= $cnt ?></div>
            <div style="font-size:.8rem; color:<?= $color ?>; font-weight:600;"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
