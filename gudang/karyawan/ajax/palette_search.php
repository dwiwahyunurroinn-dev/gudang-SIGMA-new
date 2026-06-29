<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_login();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode(['items' => []]); exit; }

$stmt = $pdo->prepare(
  "SELECT id_barang, nama_barang, kode_barcode, satuan, stok_sekarang
   FROM barang
   WHERE nama_barang LIKE ? OR kode_barcode LIKE ? OR merk LIKE ?
   ORDER BY nama_barang LIMIT 8"
);
$stmt->execute(["%$q%", "%$q%", "%$q%"]);

$items = array_map(fn($b) => [
  'id_barang'     => (int)$b['id_barang'],
  'nama_barang'   => $b['nama_barang'],
  'kode_barcode'  => $b['kode_barcode'],
  'satuan'        => $b['satuan'],
  'stok_sekarang' => (int)$b['stok_sekarang'],
], $stmt->fetchAll());

echo json_encode(['items' => $items]);
