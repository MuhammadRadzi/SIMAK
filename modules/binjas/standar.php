<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);

$db = getDB(); $error = ''; $pageTitle = 'Standarisasi Nilai Binjas';
$flash = getFlash();

// Tambah jenis latihan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'tambah') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $nama   = post('nama'); $satuan = post('satuan') ?: null;
        $standar= post('nilai_standar'); $arah = post('keterangan_arah');
        if (empty($nama) || !is_numeric($standar)) { $error = 'Nama dan Nilai Standar wajib diisi.'; }
        else {
            $std = (float)$standar;
            $stmt = $db->prepare("INSERT INTO binjas_jenis_latihan (nama, satuan, nilai_standar, keterangan_arah) VALUES (?,?,?,?)");
            $stmt->bind_param('ssds', $nama, $satuan, $std, $arah);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Jenis latihan berhasil ditambahkan.');
            redirect(BASE_URL . '/modules/binjas/standar.php');
        }
    }
}

// Edit standar nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'edit') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $jid    = postInt('jenis_id');
        $standar= post('nilai_standar');
        $satuan = post('satuan') ?: null;
        if (!is_numeric($standar)) { $error = 'Nilai standar harus berupa angka.'; }
        else {
            $std  = (float)$standar;
            $arah = post('keterangan_arah');
            $stmt = $db->prepare("UPDATE binjas_jenis_latihan SET nilai_standar=?, satuan=?, keterangan_arah=? WHERE id=?");
            $stmt->bind_param('dssi', $std, $satuan, $arah, $jid);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Nilai standar diperbarui.');
            redirect(BASE_URL . '/modules/binjas/standar.php');
        }
    }
}

// Toggle aktif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'toggle') {
    if (!csrf_verify()) { $error = 'Permintaan tidak valid.'; }
    else {
        $jid = postInt('jenis_id');
        $db->prepare("UPDATE binjas_jenis_latihan SET is_active = NOT is_active WHERE id=?")->bind_param('i',$jid) && null;
        $s = $db->prepare("UPDATE binjas_jenis_latihan SET is_active = NOT is_active WHERE id=?");
        $s->bind_param('i',$jid); $s->execute(); $s->close();
        setFlash('success', 'Status jenis latihan diubah.');
        redirect(BASE_URL . '/modules/binjas/standar.php');
    }
}

$jenisList = $db->query("SELECT * FROM binjas_jenis_latihan ORDER BY is_active DESC, id")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h2 class="page-title">Standarisasi Nilai Binjas</h2>
            <p class="page-sub">Kelola jenis latihan dan nilai standar kelulusan</p>
        </div>
        <a href="<?= BASE_URL ?>/modules/binjas/index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:1rem; align-items:start;">

        <!-- Form Tambah -->
        <div class="card">
            <div class="card-header"><span class="card-title">+ Tambah Jenis Latihan</span></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Nama Latihan <span class="required">*</span></label>
                        <input type="text" name="nama" class="form-input" required placeholder="Contoh: Lari 2400 Meter">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nilai Standar <span class="required">*</span></label>
                            <input type="number" step="0.01" name="nilai_standar" class="form-input" required placeholder="Contoh: 720">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="satuan" class="form-input" placeholder="detik / repetisi / meter">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Arah Penilaian</label>
                        <select name="keterangan_arah" class="form-select">
                            <option value="semakin_tinggi">↑ Semakin tinggi = lebih baik (Push Up, Sit Up)</option>
                            <option value="semakin_rendah">↓ Semakin rendah = lebih baik (Lari, Shuttle Run)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Jenis Latihan</button>
                </form>
            </div>
        </div>

        <!-- Daftar Jenis Latihan -->
        <div class="card">
            <div class="card-header"><span class="card-title">Daftar Jenis Latihan</span></div>
            <div class="table-responsive">
                <table class="table" style="font-size:.875rem;">
                    <thead>
                        <tr><th>Nama</th><th>Standar</th><th>Arah</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($jenisList as $jl): ?>
                    <tr style="<?= $jl['is_active'] ? '' : 'opacity:.5;' ?>">
                        <td><strong><?= e($jl['nama']) ?></strong></td>
                        <td><?= $jl['nilai_standar'] ?> <span style="color:var(--gray-400);"><?= e($jl['satuan'] ?? '') ?></span></td>
                        <td>
                            <?php if ($jl['keterangan_arah'] === 'semakin_tinggi'): ?>
                                <span style="color:var(--success);">↑ Tinggi</span>
                            <?php else: ?>
                                <span style="color:var(--info);">↓ Rendah</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $jl['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>' ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn btn-sm btn-warning" onclick="openEditStandar(<?= htmlspecialchars(json_encode($jl)) ?>)">Edit</button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="jenis_id" value="<?= $jl['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-info"><?= $jl['is_active']?'Nonaktifkan':'Aktifkan' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Standar -->
<div id="modalEditStandar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:200; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:1.5rem; width:100%; max-width:440px; margin:1rem;">
        <h3 style="margin-bottom:1rem; font-size:1rem; font-weight:700;">Edit Standar Nilai</h3>
        <form method="POST" id="formEditStandar">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="jenis_id" id="editJenisId">
            <div class="form-group">
                <label class="form-label">Nama Latihan</label>
                <input type="text" id="editJenisNama" class="form-input" disabled>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nilai Standar <span class="required">*</span></label>
                    <input type="number" step="0.01" name="nilai_standar" id="editNilaiStandar" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="satuan" id="editSatuan" class="form-input" placeholder="detik / repetisi">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Arah Penilaian</label>
                <select name="keterangan_arah" id="editArah" class="form-select">
                    <option value="semakin_tinggi">↑ Semakin tinggi = lebih baik</option>
                    <option value="semakin_rendah">↓ Semakin rendah = lebih baik</option>
                </select>
            </div>
            <div style="display:flex; gap:.5rem; justify-content:flex-end; margin-top:1rem;">
                <button type="button" onclick="document.getElementById('modalEditStandar').style.display='none'" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditStandar(jl) {
    document.getElementById('editJenisId').value      = jl.id;
    document.getElementById('editJenisNama').value    = jl.nama;
    document.getElementById('editNilaiStandar').value = jl.nilai_standar;
    document.getElementById('editSatuan').value       = jl.satuan || '';
    document.getElementById('editArah').value         = jl.keterangan_arah;
    document.getElementById('modalEditStandar').style.display = 'flex';
}
document.getElementById('modalEditStandar').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
