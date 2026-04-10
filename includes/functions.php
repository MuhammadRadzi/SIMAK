<?php
// ============================================================
//  SIMAK — Helper Functions Global
// ============================================================

// --- Session & Auth ---

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu.');
        redirect(BASE_URL . '/modules/auth/login.php');
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
        redirect(BASE_URL . '/modules/dashboard/index.php');
    }
}

function isSuperAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === ROLE_SUPER_ADMIN;
}

function isAdmin(): bool {
    return in_array($_SESSION['user_role'] ?? '', [ROLE_SUPER_ADMIN, ROLE_ADMIN], true);
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'nama'     => $_SESSION['user_nama']  ?? '',
        'username' => $_SESSION['user_username'] ?? '',
        'role'     => $_SESSION['user_role']  ?? '',
    ];
}

// --- Flash Messages ---

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// --- Redirect ---

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// --- Security ---

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// --- Input ---

function post(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

function get(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $default);
}

function postInt(string $key, int $default = 0): int {
    return (int) ($_POST[$key] ?? $default);
}

// --- Format ---

function formatTanggal(string $date, string $format = 'd F Y'): string {
    if (empty($date) || $date === '0000-00-00') return '-';
    $bulan = [
        'January'   => 'Januari',   'February'  => 'Februari',
        'March'     => 'Maret',     'April'     => 'April',
        'May'       => 'Mei',       'June'      => 'Juni',
        'July'      => 'Juli',      'August'    => 'Agustus',
        'September' => 'September', 'October'   => 'Oktober',
        'November'  => 'November',  'December'  => 'Desember',
    ];
    $str = date($format, strtotime($date));
    return str_replace(array_keys($bulan), array_values($bulan), $str);
}

function formatWaktu(string $time): string {
    if (empty($time)) return '-';
    return date('H:i', strtotime($time));
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function badgeStatus(string $status): string {
    $map = [
        'draft'     => ['label' => 'Draft',    'class' => 'badge-secondary'],
        'aktif'     => ['label' => 'Aktif',    'class' => 'badge-primary'],
        'selesai'   => ['label' => 'Selesai',  'class' => 'badge-success'],
        'batal'     => ['label' => 'Batal',    'class' => 'badge-danger'],
        'pra'       => ['label' => 'Pra-Ops',  'class' => 'badge-warning'],
        'operasional'=> ['label'=> 'Operasional','class'=> 'badge-primary'],
        'pasca'     => ['label' => 'Pasca',    'class' => 'badge-info'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function badgeKehadiran(string $status): string {
    $map = [
        'hadir' => ['label' => 'Hadir', 'class' => 'badge-success'],
        'izin'  => ['label' => 'Izin',  'class' => 'badge-warning'],
        'sakit' => ['label' => 'Sakit', 'class' => 'badge-info'],
        'alpha' => ['label' => 'Alpha', 'class' => 'badge-danger'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

// --- Pagination ---

function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = (int) ceil($total / $perPage);
    $offset     = ($currentPage - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $currentPage > 1,
        'has_next'    => $currentPage < $totalPages,
    ];
}
