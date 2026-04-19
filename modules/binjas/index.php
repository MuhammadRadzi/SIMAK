<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();

$db        = getDB();
$pageTitle = 'Bina Jasmani';
$flash     = getFlash();

$search  = get('search');
$status  = get('status');
$perPage = 10;
$page    = max(1, (int)get('page', '1'));

$where  = ['1=1'];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[]  = "(nama_sesi LIKE ? OR lokasi LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like;
    $types   .= 'ss';
}
if (!empty($status)) {
    $where[]  = "status = ?";
    $params[] = $status; $types .= 's';
}

$whereStr  = 'WHERE ' . implode(' AND ', $where);
$stmtCount = $db->prepare("SELECT COUNT(*) FROM binjas $whereStr");
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_row()[0];
$stmtCount->close();

$pag = paginate($total, $perPage, $page);
$sql = "SELECT b.*, u.nama AS created_by_nama,
               (SELECT COUNT(DISTINCT siswa_id) FROM binjas_nilai bn WHERE bn.binjas_id = b.id) AS total_peserta
        FROM binjas b LEFT JOIN users u ON u.id = b.created_by
        $whereStr ORDER BY b.tanggal DESC LIMIT ? OFFSET ?";
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
            <h2 class="page-title">Modul Bina Jasmani</h2>
            <p class="page-sub">Pencatatan & Penilaian Latihan Fisik Siswa — Total <?= $total ?> sesi</p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/modules/binjas/standar.php" class="btn btn-secondary">⚙️ Standarisasi Nilai</a>
            <a href="<?= BASE_URL ?>/modules/binjas/create.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Buat Sesi Binjas
            </a>
        </div>
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
                    <input type="text" name="search" class="form-input" placeholder="Nama sesi atau lokasi..." value="<?= e($search) ?>">
                </div>
                <div style="min-width:150px;">
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
                    <a href="<?= BASE_URL ?>/modules/binjas/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <?php if (empty($list)): ?>
        <div class="card"><div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
                <p>Belum ada sesi Bina Jasmani.</p>
            </div>
        </div></div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Sesi</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Lokasi</th>
                        <th>Peserta</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($list as $i => $b): ?>
                <tr>
                    <td><?= $pag['offset'] + $i + 1 ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/modules/binjas/detail.php?id=<?= $b['id'] ?>"
                           style="font-weight:600; color:var(--primary); text-decoration:none;">
                            <?= e($b['nama_sesi']) ?>
                        </a>
                    </td>
                    <td><?= formatTanggal($b['tanggal'], 'd M Y') ?></td>
                    <td><?= $b['waktu_mulai'] ? formatWaktu($b['waktu_mulai']) . ($b['waktu_selesai'] ? ' – ' . formatWaktu($b['waktu_selesai']) : '') : '—' ?></td>
                    <td><?= e($b['lokasi'] ?? '—') ?></td>
                    <td><span class="badge badge-primary"><?= $b['total_peserta'] ?> siswa</span></td>
                    <td><?= badgeStatus($b['status']) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/modules/binjas/detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                            <a href="<?= BASE_URL ?>/modules/binjas/input-nilai.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-success">Input Nilai</a>
                            <a href="<?= BASE_URL ?>/modules/binjas/edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pag['total_pages'] > 1): ?>
        <div style="padding:.875rem 1.25rem; border-top:1px solid var(--gray-100); display:flex; justify-content:center; gap:.3rem;">
            <?php $baseUrl = BASE_URL . '/modules/binjas/index.php?' . http_build_query(['search'=>$search,'status'=>$status]); ?>
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
