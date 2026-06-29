<?php
// ===== Proteksi Session & Keamanan =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gudang');
}

// Wajib login
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

// Wajib role tertentu (admin / karyawan)
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("⛔ Akses ditolak. Halaman ini khusus untuk: " . htmlspecialchars($role));
    }
}

// ===== Proteksi CSRF =====
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        die("Token keamanan tidak valid. Silakan muat ulang halaman.");
    }
}

// ===== Helper =====
function e($str): string {            // escape output (anti-XSS)
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
function current_user(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'nama' => $_SESSION['nama']    ?? '',
        'role' => $_SESSION['role']    ?? '',
    ];
}
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}