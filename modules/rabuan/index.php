<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Rabuan';
$flash     = getFlash();

// Filter
$search  = get('search');
$status  = get('status');
$perPage = 12;
$page    = max(1, (int)get('page', '1'));

$where  = ['1=1'];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[]  = "(judul LIKE ? OR lokasi LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like;
    $types   .= 'ss';
}
if (!empty($status)) {
    $where[]  = "status = ?";
    $params[] = $status;
    $types   .= 's';
}

$whereStr  = 'WHERE ' . implode(' AND ', $where);
$stmtCount = $db->prepare("SELECT COUNT(*) FROM rabuan $whereStr");
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_row()[0];
$stmtCount->close();

$pag  = paginate($total, $perPage, $page);
$sql  = "SELECT r.*, u.nama AS created_by_nama,
                (SELECT COUNT(*) FROM rabuan_dokumen rd WHERE rd.rabuan_id = r.id) AS total_dok
         FROM rabuan r
         LEFT JOIN users u ON u.id = r.created_by
         $whereStr ORDER BY r.tanggal DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$allParams = array_merge($params, [$perPage, $pag['offset']]);
$stmt->bind_param($types . 'ii', ...$allParams);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Modul Rabuan</h2>
            <p class="page-sub">Manajemen Rapat Rutin — Total <?= $total ?> kegiatan</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/rabuan/create.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Buat Rabuan
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <form method="GET" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:200px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Cari</label>
                    <input type="text" name="search" class="form-input" placeholder="Judul atau lokasi..." value="<?= e($search) ?>">
                </div>
                <div style="min-width:150px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <?php foreach (['draft','aktif','selesai','batal'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= BASE_URL ?>/modules/rabuan/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <?php if (empty($list)): ?>
        <div class="card"><div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p>Belum ada data Rabuan.</p>
            </div>
        </div></div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1rem;">
        <?php foreach ($list as $r): ?>
            <div class="card" style="transition:box-shadow .2s;" onmouseover="this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.boxShadow=''">
                <div style="padding:1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; margin-bottom:.75rem;">
                        <h3 style="font-size:.9375rem; font-weight:700; color:var(--gray-900); line-height:1.3; flex:1;">
                            <a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $r['id'] ?>"
                               style="text-decoration:none; color:inherit;"><?= e($r['judul']) ?></a>
                        </h3>
                        <?= badgeStatus($r['status']) ?>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:.35rem; font-size:.8125rem; color:var(--gray-500); margin-bottom:1rem;">
                        <div style="display:flex; align-items:center; gap:.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= formatTanggal($r['tanggal']) ?>
                            <?php if ($r['waktu_mulai']): ?>
                                · <?= formatWaktu($r['waktu_mulai']) ?>
                                <?= $r['waktu_selesai'] ? '– ' . formatWaktu($r['waktu_selesai']) : '' ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($r['lokasi']): ?>
                        <div style="display:flex; align-items:center; gap:.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= e($r['lokasi']) ?>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex; align-items:center; gap:.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?= $r['total_dok'] ?> dokumen notulensi
                        </div>
                    </div>

                    <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                        <a href="<?= BASE_URL ?>/modules/rabuan/detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                        <a href="<?= BASE_URL ?>/modules/rabuan/edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="<?= BASE_URL ?>/modules/presensi/input.php?jenis=rabuan&id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary">Presensi</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pag['total_pages'] > 1): ?>
        <div style="margin-top:1rem; display:flex; justify-content:center; gap:.3rem;">
            <?php $baseUrl = BASE_URL . '/modules/rabuan/index.php?' . http_build_query(['search'=>$search,'status'=>$status]); ?>
            <?php if ($pag['has_prev']): ?><a href="<?= $baseUrl ?>&page=<?= $pag['current']-1 ?>" class="btn btn-sm btn-secondary">←</a><?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($pag['total_pages'],$page+2); $p++): ?>
                <a href="<?= $baseUrl ?>&page=<?= $p ?>" class="btn btn-sm <?= $p===$page?'btn-primary':'btn-secondary' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($pag['has_next']): ?><a href="<?= $baseUrl ?>&page=<?= $pag['current']+1 ?>" class="btn btn-sm btn-secondary">→</a><?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
