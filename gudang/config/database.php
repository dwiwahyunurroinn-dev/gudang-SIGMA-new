<?php
// ===== Konfigurasi Koneksi Database (PDO) =====
define('BASE_URL', '/gudang'); // ganti jika nama folder beda

$host   = 'localhost';
$dbname = 'gudang_db';
$user   = 'root';
$pass   = '';            // default XAMPP kosong

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
