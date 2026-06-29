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

// ===== Audit Trail =====
// Tabel dibuat otomatis saat pertama kali dipakai — tidak perlu SQL manual.
function audit_ensure(): void {
    global $pdo;
    if (!isset($pdo)) return;
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NULL,
        user_nama  VARCHAR(150) NULL,
        aksi       VARCHAR(50)  NOT NULL,
        entitas    VARCHAR(50)  NOT NULL,
        deskripsi  VARCHAR(500) NULL,
        ip         VARCHAR(45)  NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_entitas (entitas)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

// Catat satu aktivitas. Tidak pernah menggagalkan alur utama bila audit error.
function audit(string $aksi, string $entitas, string $deskripsi = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        audit_ensure();
        $u = current_user();
        $pdo->prepare("INSERT INTO audit_log (user_id, user_nama, aksi, entitas, deskripsi, ip)
                       VALUES (?,?,?,?,?,?)")
            ->execute([
                $u['id'] ?: null,
                $u['nama'] ?: null,
                $aksi,
                $entitas,
                $deskripsi !== '' ? mb_substr($deskripsi, 0, 500) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
    } catch (\Throwable $e) {
        // sengaja diabaikan — audit tidak boleh mengganggu operasi utama
    }
}