<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Operasional';
$flash     = getFlash();

$search  = get('search');
$status  = get('status');
$fase    = get('fase');
$perPage = 10;
$page    = max(1, (int)get('page', '1'));

$where  = ['1=1'];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[]  = "(nama_kegiatan LIKE ? OR lokasi LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like;
    $types   .= 'ss';
}
if (!empty($status)) {
    $where[]  = "status = ?";
    $params[] = $status; $types .= 's';
}
if (!empty($fase)) {
    $where[]  = "fase = ?";
    $params[] = $fase; $types .= 's';
}

$whereStr  = 'WHERE ' . implode(' AND ', $where);
$stmtCount = $db->prepare("SELECT COUNT(*) FROM operasional $whereStr");
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_row()[0];
$stmtCount->close();

$pag = paginate($total, $perPage, $page);
$sql = "SELECT o.*, u.nama AS created_by_nama FROM operasional o
        LEFT JOIN users u ON u.id = o.created_by
        $whereStr ORDER BY o.tanggal_mulai DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$allParams = array_merge($params, [$perPage, $pag['offset']]);
$stmt->bind_param($types . 'ii', ...$allParams);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Badge fase
function badgeFase(string $fase): string {
    $map = [
        'pra'         => ['Pra-Operasional', 'badge-warning'],
        'operasional' => ['Operasional',      'badge-primary'],
        'pasca'       => ['Pasca-Operasional','badge-info'],
    ];
    $f = $map[$fase] ?? [ucfirst($fase), 'badge-secondary'];
    return '<span class="badge ' . $f[1] . '">' . $f[0] . '</span>';
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Modul Operasional</h2>
            <p class="page-sub">Manajemen Kegiatan Lapangan — Total <?= $total ?> kegiatan</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/operasional/create.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Buat Kegiatan
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <form method="GET" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:180px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Cari</label>
                    <input type="text" name="search" class="form-input" placeholder="Nama kegiatan atau lokasi..." value="<?= e($search) ?>">
                </div>
                <div style="min-width:140px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Fase</label>
                    <select name="fase" class="form-select">
                        <option value="">Semua Fase</option>
                        <option value="pra" <?= $fase==='pra'?'selected':'' ?>>Pra-Operasional</option>
                        <option value="operasional" <?= $fase==='operasional'?'selected':'' ?>>Operasional</option>
                        <option value="pasca" <?= $fase==='pasca'?'selected':'' ?>>Pasca-Operasional</option>
                    </select>
                </div>
                <div style="min-width:140px;">
                    <label class="form-label" style="margin-bottom:.25rem;">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <?php foreach (['draft','aktif','selesai','batal'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= BASE_URL ?>/modules/operasional/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($list)): ?>
        <div class="card"><div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                <p>Belum ada data kegiatan operasional.</p>
            </div>
        </div></div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Kegiatan</th>
                        <th>Lokasi</th>
                        <th>Tanggal Mulai</th>
                        <th>Fase</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($list as $i => $o): ?>
                <tr>
                    <td><?= $pag['offset'] + $i + 1 ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/modules/operasional/detail.php?id=<?= $o['id'] ?>"
                           style="font-weight:600; color:var(--primary); text-decoration:none;">
                            <?= e($o['nama_kegiatan']) ?>
                        </a>
                    </td>
                    <td><?= e($o['lokasi'] ?? '—') ?></td>
                    <td><?= formatTanggal($o['tanggal_mulai'], 'd M Y') ?></td>
                    <td><?= badgeFase($o['fase']) ?></td>
                    <td><?= badgeStatus($o['status']) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/modules/operasional/detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                            <a href="<?= BASE_URL ?>/modules/operasional/edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pag['total_pages'] > 1): ?>
        <div style="padding:.875rem 1.25rem; border-top:1px solid var(--gray-100); display:flex; justify-content:center; gap:.3rem;">
            <?php $baseUrl = BASE_URL . '/modules/operasional/index.php?' . http_build_query(['search'=>$search,'fase'=>$fase,'status'=>$status]); ?>
            <?php if ($pag['has_prev']): ?><a href="<?= $baseUrl ?>&page=<?= $pag['current']-1 ?>" class="btn btn-sm btn-secondary">←</a><?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($pag['total_pages'],$page+2); $p++): ?>
                <a href="<?= $baseUrl ?>&page=<?= $p ?>" class="btn btn-sm <?= $p===$page?'btn-primary':'btn-secondary' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($pag['has_next']): ?><a href="<?= $baseUrl ?>&page=<?= $pag['current']+1 ?>" class="btn btn-sm btn-secondary">→</a><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
