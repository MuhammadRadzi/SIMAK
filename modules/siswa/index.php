<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Data Siswa';
$flash     = getFlash();

// Tab aktif/nonaktif
$tampil  = get('tampil', 'aktif');
$isAktif = $tampil !== 'nonaktif' ? 1 : 0;

// Search & filter
$search   = get('search');
$regu     = get('regu');
$angkatan = get('angkatan');
$perPage  = 15;
$page     = max(1, (int)get('page', '1'));

// Aksi restore (aktifkan kembali)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'restore') {
    if (!csrf_verify()) { setFlash('error', 'Permintaan tidak valid.'); redirect(BASE_URL . '/modules/siswa/index.php?tampil=nonaktif'); }
    $restoreId = postInt('id');
    $stmt = $db->prepare("UPDATE siswa SET is_active = 1 WHERE id = ?");
    $stmt->bind_param('i', $restoreId);
    $stmt->execute(); $stmt->close();
    setFlash('success', 'Siswa berhasil diaktifkan kembali.');
    redirect(BASE_URL . '/modules/siswa/index.php');
}

// Build query
$where  = ["s.is_active = $isAktif"];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[]  = "(s.nama LIKE ? OR s.nis LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if (!empty($regu)) {
    $where[]  = "s.regu = ?";
    $params[] = $regu;
    $types   .= 's';
}
if (!empty($angkatan)) {
    $where[]  = "s.angkatan = ?";
    $params[] = (int)$angkatan;
    $types   .= 'i';
}

$whereStr = 'WHERE ' . implode(' AND ', $where);

// Count total
$stmtCount = $db->prepare("SELECT COUNT(*) FROM siswa s $whereStr");
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_row()[0];
$stmtCount->close();

$pag    = paginate($total, $perPage, $page);
$offset = $pag['offset'];

// Fetch data
$sql  = "SELECT s.* FROM siswa s $whereStr ORDER BY s.nama ASC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$siswaList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count nonaktif (untuk badge tab)
$totalNonaktif = $db->query("SELECT COUNT(*) FROM siswa WHERE is_active = 0")->fetch_row()[0];

// Filter options
$reguList     = $db->query("SELECT DISTINCT regu FROM siswa WHERE regu IS NOT NULL AND regu != '' ORDER BY regu")->fetch_all(MYSQLI_ASSOC);
$angkatanList = $db->query("SELECT DISTINCT angkatan FROM siswa WHERE angkatan IS NOT NULL ORDER BY angkatan DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Data Siswa</h2>
            <p class="page-sub">Total <?= $total ?> siswa <?= $isAktif ? 'aktif' : 'nonaktif' ?></p>
        </div>
        <div class="d-flex gap-1">
            <?php if ($isAktif): ?>
            <a href="<?= BASE_URL ?>/modules/siswa/import.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import CSV
            </a>
            <a href="<?= BASE_URL ?>/modules/siswa/create.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Siswa
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Tab Aktif / Nonaktif -->
    <div style="display:flex; gap:.5rem; margin-bottom:1rem;">
        <a href="<?= BASE_URL ?>/modules/siswa/index.php?tampil=aktif"
           class="btn <?= $isAktif ? 'btn-primary' : 'btn-secondary' ?>">
            ✓ Aktif
        </a>
        <a href="<?= BASE_URL ?>/modules/siswa/index.php?tampil=nonaktif"
           class="btn <?= !$isAktif ? 'btn-primary' : 'btn-secondary' ?>">
            Nonaktif
            <?php if ($totalNonaktif > 0): ?>
                <span style="background:var(--danger); color:#fff; border-radius:999px; padding:.05rem .45rem; font-size:.7rem; font-weight:700; margin-left:.25rem;">
                    <?= $totalNonaktif ?>
                </span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <form method="GET" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end;">
                <input type="hidden" name="tampil" value="<?= e($tampil) ?>">
                <div style="flex:1; min-width:180px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Cari</label>
                    <input type="text" name="search" class="form-input" placeholder="Nama atau NIS..." value="<?= e($search) ?>">
                </div>
                <div style="min-width:140px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Regu</label>
                    <select name="regu" class="form-select">
                        <option value="">Semua Regu</option>
                        <?php foreach ($reguList as $r): ?>
                            <option value="<?= e($r['regu']) ?>" <?= $regu === $r['regu'] ? 'selected' : '' ?>><?= e($r['regu']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width:130px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Angkatan</label>
                    <select name="angkatan" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($angkatanList as $a): ?>
                            <option value="<?= $a['angkatan'] ?>" <?= $angkatan == $a['angkatan'] ? 'selected' : '' ?>><?= $a['angkatan'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= BASE_URL ?>/modules/siswa/index.php?tampil=<?= e($tampil) ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>JK</th>
                        <th>Regu</th>
                        <th>Angkatan</th>
                        <th>No. HP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($siswaList)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <p>Tidak ada data siswa <?= $isAktif ? 'aktif' : 'nonaktif' ?> ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($siswaList as $i => $s): ?>
                    <tr>
                        <td><?= $pag['offset'] + $i + 1 ?></td>
                        <td><code><?= e($s['nis']) ?></code></td>
                        <td>
                            <?php if ($isAktif): ?>
                            <a href="<?= BASE_URL ?>/modules/siswa/detail.php?id=<?= $s['id'] ?>"
                               style="font-weight:600; color:var(--primary); text-decoration:none;">
                                <?= e($s['nama']) ?>
                            </a>
                            <?php else: ?>
                            <span style="font-weight:600; color:var(--gray-500);"><?= e($s['nama']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $s['jenis_kelamin'] === 'L' ? '♂ L' : '♀ P' ?></td>
                        <td><?= e($s['regu'] ?? '—') ?></td>
                        <td><?= $s['angkatan'] ?? '—' ?></td>
                        <td><?= e($s['no_hp'] ?? '—') ?></td>
                        <td>
                            <div class="action-btns">
                                <?php if ($isAktif): ?>
                                    <a href="<?= BASE_URL ?>/modules/siswa/detail.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                                    <a href="<?= BASE_URL ?>/modules/siswa/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>/modules/siswa/delete.php" style="display:inline"
                                          onsubmit="return confirmDelete('Nonaktifkan siswa <?= e(addslashes($s['nama'])) ?>?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Nonaktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Tombol Aktifkan Kembali -->
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Aktifkan kembali siswa <?= e(addslashes($s['nama'])) ?>?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">✓ Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pag['total_pages'] > 1): ?>
        <div style="padding:.875rem 1.25rem; border-top:1px solid var(--gray-100); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
            <span style="font-size:.8125rem; color:var(--gray-500);">
                Menampilkan <?= $pag['offset'] + 1 ?>–<?= min($pag['offset'] + $perPage, $total) ?> dari <?= $total ?> data
            </span>
            <div style="display:flex; gap:.3rem;">
                <?php $baseUrl = BASE_URL . '/modules/siswa/index.php?' . http_build_query(['tampil'=>$tampil,'search'=>$search,'regu'=>$regu,'angkatan'=>$angkatan]); ?>
                <?php if ($pag['has_prev']): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $pag['current'] - 1 ?>" class="btn btn-sm btn-secondary">← Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1,$page-2); $p <= min($pag['total_pages'],$page+2); $p++): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $p ?>"
                       class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($pag['has_next']): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $pag['current'] + 1 ?>" class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
