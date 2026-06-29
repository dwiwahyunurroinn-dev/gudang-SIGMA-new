<?php
// buat_karyawan.php — file SEMENTARA, hapus setelah dipakai
require __DIR__ . '/config/database.php';

$username = 'karyawan1';
$password = 'karyawan123';
$nama     = 'Budiono Siregar';

$cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$cek->execute([$username]);
if ($cek->fetch()) exit("Akun '$username' sudah ada. Hapus file ini.");

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username, password_hash, nama, role) VALUES (?,?,?,'karyawan')")
    ->execute([$username, $hash, $nama]);

echo "✅ Karyawan dibuat! Username: <b>$username</b> | Password: <b>$password</b><br>";
echo "⚠️ HAPUS file buat_karyawan.php ini sekarang demi keamanan.";
