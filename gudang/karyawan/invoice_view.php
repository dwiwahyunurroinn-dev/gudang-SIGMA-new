<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$inv = trim($_GET['inv'] ?? '');

$items = [];
if ($inv !== '') {
    $stmt = $pdo->prepare("
        SELECT k.*, b.nama_barang, b.merk, b.satuan, b.kode_barcode, u.nama AS petugas
        FROM stok_keluar k
        JOIN barang b ON b.id_barang = k.id_barang
        LEFT JOIN users u ON u.id = k.user_id
        WHERE k.nomor_invoice = ?
        ORDER BY k.id ASC");
    $stmt->execute([$inv]);
    $items = $stmt->fetchAll();
}

$page_title = 'Invoice';
$active     = 'keluar';

// ===== Jika invoice tidak ditemukan =====
if (!$items) {
    require __DIR__ . '/../includes/header.php'; ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center max-w-md mx-auto">
        <i data-lucide="file-x" class="w-10 h-10 mx-auto text-slate-300 mb-3"></i>
        <h2 class="font-semibold text-slate-900">Invoice Tidak Ditemukan</h2>
        <p class="text-sm text-slate-500 mt-1">Nomor invoice tidak valid atau sudah dibatalkan.</p>
        <a href="<?= BASE_URL ?>/karyawan/stok_keluar.php" class="inline-flex items-center gap-1.5 mt-5 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Stok Keluar
        </a>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$head  = $items[0];
$total = 0;
foreach ($items as $it) $total += (int)$it['jumlah'] * (int)$it['harga_satuan'];

require __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
  #sidebar, #overlay, header, .no-print { display: none !important; }
  body { background: #ffffff !important; }
  main { padding: 0 !important; }
  .invoice-box { border: none !important; border-radius: 0 !important; box-shadow: none !important; max-width: 100% !important; }
  @page { margin: 1.5cm; }
}
</style>

<!-- Aksi (tidak ikut tercetak) -->
<div class="no-print flex items-center justify-between mb-5 max-w-3xl mx-auto">
  <a href="<?= BASE_URL ?>/karyawan/stok_keluar.php" class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
  </a>
  <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">
    <i data-lucide="printer" class="w-4 h-4"></i> Cetak / Download PDF
  </button>
</div>

<!-- ===== INVOICE ===== -->
<div class="invoice-box bg-white border border-slate-200 rounded-2xl p-8 sm:p-10 max-w-3xl mx-auto">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row items-start justify-between gap-4 pb-6 border-b border-slate-200">
    <div class="flex items-center gap-3">
      <span class="grid place-items-center w-12 h-12 rounded-xl bg-blue-600 text-white"><i data-lucide="package" class="w-6 h-6"></i></span>
      <div>
        <p class="text-lg font-bold text-slate-900">PT Wahyu Makmur Abadi</p>
        <p class="text-sm text-slate-500">Jl. Kudus-Jepara. 03, Kudus</p>
        <p class="text-sm text-slate-500">Telp: 0831-5965-8759</p>
      </div>
    </div>
    <div class="text-left sm:text-right">
      <p class="text-2xl font-extrabold text-slate-900 tracking-tight">INVOICE</p>
      <p class="text-sm font-mono text-blue-600 mt-1"><?= e($head['nomor_invoice']) ?></p>
    </div>
  </div>

  <!-- Meta -->
  <div class="grid grid-cols-2 gap-6 py-6">
    <div>
      <p class="text-xs text-slate-400 uppercase tracking-wide">Ditujukan Kepada</p>
      <p class="font-semibold text-slate-800 mt-1"><?= e($head['pelanggan'] ?: 'Pelanggan Umum') ?></p>
    </div>
    <div class="text-right">
      <p class="text-sm text-slate-500">Tanggal: <span class="text-slate-800 font-medium"><?= date('d M Y', strtotime($head['tanggal'])) ?></span></p>
      <p class="text-sm text-slate-500">Petugas: <span class="text-slate-800 font-medium"><?= e($head['petugas'] ?: '-') ?></span></p>
    </div>
  </div>

  <!-- Tabel item -->
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-slate-50 text-slate-500 text-left">
        <th class="px-3 py-2.5 font-medium w-10">#</th>
        <th class="px-3 py-2.5 font-medium">Barang</th>
        <th class="px-3 py-2.5 font-medium text-center">Qty</th>
        <th class="px-3 py-2.5 font-medium text-right">Harga</th>
        <th class="px-3 py-2.5 font-medium text-right">Subtotal</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php $no = 1; foreach ($items as $it): $sub = (int)$it['jumlah'] * (int)$it['harga_satuan']; ?>
        <tr>
          <td class="px-3 py-3 text-slate-400"><?= $no++ ?></td>
          <td class="px-3 py-3">
            <p class="font-medium text-slate-800"><?= e($it['nama_barang']) ?></p>
            <p class="text-xs text-slate-400"><?= e($it['merk'] ?: $it['kode_barcode']) ?></p>
          </td>
          <td class="px-3 py-3 text-center text-slate-700"><?= (int)$it['jumlah'] ?> <?= e($it['satuan']) ?></td>
          <td class="px-3 py-3 text-right text-slate-600">Rp <?= number_format((int)$it['harga_satuan'], 0, ',', '.') ?></td>
          <td class="px-3 py-3 text-right font-medium text-slate-800">Rp <?= number_format($sub, 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Total -->
  <div class="flex justify-end pt-5">
    <div class="w-full sm:w-72">
      <div class="flex justify-between py-3 border-t-2 border-slate-800">
        <span class="font-bold text-slate-900">TOTAL</span>
        <span class="font-bold text-slate-900 text-lg">Rp <?= number_format($total, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>

  <?php if ($head['keterangan']): ?>
    <p class="text-sm text-slate-500 mt-4">Catatan: <?= e($head['keterangan']) ?></p>
  <?php endif; ?>

  <!-- Footer -->
  <div class="mt-10 pt-6 border-t border-slate-100 flex justify-between items-end">
    <p class="text-xs text-slate-400">Terima kasih atas transaksi Anda.<br>Barang yang sudah dibeli tidak dapat dikembalikan.</p>
    <div class="text-center">
      <p class="text-sm text-slate-500 mb-12">Hormat kami,</p>
      <p class="text-sm font-medium text-slate-800 border-t border-slate-300 pt-1 px-6"><?= e($head['petugas'] ?: 'Petugas') ?></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>