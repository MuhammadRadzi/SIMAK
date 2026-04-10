<?php
$currentUser = currentUser();
$currentPath = $_SERVER['PHP_SELF'];

function isActivePage(string $path): string {
    global $currentPath;
    return (str_contains($currentPath, $path)) ? 'active' : '';
}
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <strong><?= APP_NAME ?></strong>
            <span>Monitoring Aktivitas</span>
        </div>
    </a>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <!-- Utama -->
        <div class="nav-section-label">Utama</div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/dashboard/index.php"
               class="nav-link <?= isActivePage('/dashboard/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/jadwal/index.php"
               class="nav-link <?= isActivePage('/jadwal/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Jadwal Terpadu
            </a>
        </div>

        <!-- Kegiatan -->
        <div class="nav-section-label">Kegiatan</div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/rabuan/index.php"
               class="nav-link <?= isActivePage('/rabuan/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Rabuan
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/mentoring/index.php"
               class="nav-link <?= isActivePage('/mentoring/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Mentoring
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/operasional/index.php"
               class="nav-link <?= isActivePage('/operasional/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                Operasional
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/binjas/index.php"
               class="nav-link <?= isActivePage('/binjas/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                Bina Jasmani
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/presensi/index.php"
               class="nav-link <?= isActivePage('/presensi/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Presensi
            </a>
        </div>

        <!-- Data -->
        <div class="nav-section-label">Data</div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/siswa/index.php"
               class="nav-link <?= isActivePage('/siswa/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Data Siswa
            </a>
        </div>

        <!-- Admin -->
        <?php if (isSuperAdmin()): ?>
        <div class="nav-section-label">Administrasi</div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/users/index.php"
               class="nav-link <?= isActivePage('/users/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Pengguna
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/modules/settings/index.php"
               class="nav-link <?= isActivePage('/settings/') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Pengaturan
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <!-- User Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <strong><?= e($currentUser['nama']) ?></strong>
                <span><?= $currentUser['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/modules/auth/change-password.php" class="sidebar-logout" style="margin-bottom:.5rem; background:rgba(255,255,255,.06); color:var(--gray-400);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Ganti Password
        </a>
        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="sidebar-logout"
           onclick="return confirm('Yakin ingin keluar?')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</aside>

<!-- Topbar -->
<div class="page-wrapper">
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-breadcrumb">
            <span><?= APP_NAME ?> / </span>
            <strong><?= e($pageTitle) ?></strong>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-date" id="topbarDate"></span>
    </div>
</header>
