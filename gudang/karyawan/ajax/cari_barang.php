<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_login();

header('Content-Type: application/json');

$kode = trim($_GET['kode'] ?? '');
if ($kode === '') { echo json_encode(['ketemu' => false]); exit; }

$stmt = $pdo->prepare("SELECT id_barang, nama_barang, merk, satuan, harga, stok_sekarang
                       FROM barang WHERE kode_barcode = ?");
$stmt->execute([$kode]);
$b = $stmt->fetch();

echo json_encode($b ? ['ketemu' => true, 'barang' => $b] : ['ketemu' => false]);