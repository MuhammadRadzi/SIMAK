<?php
require_once __DIR__ . '/../../includes/auth_middleware.php';
requireLogin();
$pageTitle = ucfirst('rabuan');
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="page-title">Modul <?= ucfirst('rabuan') ?></h2>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p>Modul ini sedang dalam pengembangan. 🚧</p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
