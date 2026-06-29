<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_login();

header('Content-Type: application/json');

$kode = trim($_GET['kode'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if ($kode === '') { echo json_encode(['ada' => false]); exit; }

$stmt = $pdo->prepare("SELECT nama_barang FROM barang WHERE kode_barcode = ? AND id_barang <> ?");
$stmt->execute([$kode, $id]);
$row = $stmt->fetch();

echo json_encode(['ada' => (bool)$row, 'nama' => $row['nama_barang'] ?? '']);