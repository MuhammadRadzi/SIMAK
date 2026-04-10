// ============================================================
//  SIMAK — Main JS
// ============================================================

// --- Topbar Date ---
(function () {
    const el = document.getElementById('topbarDate');
    if (!el) return;
    const hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const now   = new Date();
    el.textContent = hari[now.getDay()] + ', ' + now.getDate() + ' ' + bulan[now.getMonth()] + ' ' + now.getFullYear();
})();

// --- Sidebar Toggle (mobile) ---
function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}

// --- Flash message auto-hide ---
(function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 4000);
    });
})();

// --- Confirm delete helper ---
function confirmDelete(msg) {
    return confirm(msg || 'Yakin ingin menghapus data ini?');
}
