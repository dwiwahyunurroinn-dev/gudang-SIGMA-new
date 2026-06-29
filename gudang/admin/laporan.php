<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

// ===== Rentang tanggal (default: bulan berjalan) =====
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
// validasi sederhana format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

// ===== Query rekap invoice (dipakai juga untuk export) =====
$rekapStmt = $pdo->prepare("
  SELECT nomor_invoice, pelanggan, tanggal, MAX(created_at) AS created_at,
         SUM(jumlah) AS qty, SUM(jumlah * harga_satuan) AS nilai
  FROM stok_keluar
  WHERE tanggal BETWEEN ? AND ?
  GROUP BY nomor_invoice, pelanggan, tanggal
  ORDER BY created_at DESC");
$rekapStmt->execute([$from, $to]);
$rekap = $rekapStmt->fetchAll();

// ===== Export CSV (sebelum output HTML apa pun) =====
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_penjualan_' . $from . '_sd_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['No. Invoice', 'Pelanggan', 'Tanggal', 'Total Qty', 'Nilai (Rp)']);
    foreach ($rekap as $r) {
        fputcsv($out, [$r['nomor_invoice'], $r['pelanggan'] ?: '-', $r['tanggal'], (int)$r['qty'], (int)$r['nilai']]);
    }
    fclose($out);
    exit;
}

// ===== KPI =====
$qMasuk = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM stok_masuk WHERE tanggal BETWEEN ? AND ?");
$qMasuk->execute([$from, $to]); $totalMasuk = (int)$qMasuk->fetchColumn();

$qKeluar = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM stok_keluar WHERE tanggal BETWEEN ? AND ?");
$qKeluar->execute([$from, $to]); $totalKeluar = (int)$qKeluar->fetchColumn();

$qNilai = $pdo->prepare("SELECT COALESCE(SUM(jumlah*harga_satuan),0) FROM stok_keluar WHERE tanggal BETWEEN ? AND ?");
$qNilai->execute([$from, $to]); $nilaiJual = (int)$qNilai->fetchColumn();

$qInv = $pdo->prepare("SELECT COUNT(DISTINCT nomor_invoice) FROM stok_keluar WHERE tanggal BETWEEN ? AND ?");
$qInv->execute([$from, $to]); $jmlInvoice = (int)$qInv->fetchColumn();

// Nilai persediaan saat ini (snapshot, tidak difilter tanggal)
$nilaiStok = (int)$pdo->query("SELECT COALESCE(SUM(stok_sekarang*harga),0) FROM barang")->fetchColumn();

// ===== Barang terlaris dalam rentang =====
$terlarisStmt = $pdo->prepare("
  SELECT b.nama_barang, b.satuan, SUM(k.jumlah) AS qty, SUM(k.jumlah*k.harga_satuan) AS nilai
  FROM stok_keluar k JOIN barang b ON b.id_barang = k.id_barang
  WHERE k.tanggal BETWEEN ? AND ?
  GROUP BY k.id_barang, b.nama_barang, b.satuan
  ORDER BY qty DESC LIMIT 5");
$terlarisStmt->execute([$from, $to]);
$terlaris = $terlarisStmt->fetchAll();
$maxQty = $terlaris ? max(array_map(fn($t) => (int)$t['qty'], $terlaris)) : 1;

$page_title = 'Laporan';
$active     = 'laporan';
require __DIR__ . '/../includes/header.php';

$periode = date('d M Y', strtotime($from)) . ' — ' . date('d M Y', strtotime($to));
?>

<style>
@media print {
  #sidebar, #overlay, header, .no-print { display: none !important; }
  body { background: #fff !important; }
  main { padding: 0 !important; }
  @page { margin: 1.5cm; }
}
</style>

<!-- ===== FILTER (tidak ikut cetak) ===== -->
<div class="no-print bg-white border border-slate-200 rounded-2xl p-5 mb-6">
  <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-3">
    <div>
      <label class="text-xs font-medium text-slate-500">Dari Tanggal</label>
      <input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
    </div>
    <div>
      <label class="text-xs font-medium text-slate-500">Sampai Tanggal</label>
      <input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
    </div>
    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
      <i data-lucide="filter" class="w-4 h-4"></i> Terapkan
    </button>
    <div class="sm:ml-auto flex gap-2">
      <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=csv" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
        <i data-lucide="download" class="w-4 h-4"></i> Export CSV
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
        <i data-lucide="printer" class="w-4 h-4"></i> Cetak
      </button>
    </div>
  </form>
</div>

<p class="text-sm text-slate-500 mb-4">Periode: <span class="font-semibold text-slate-800"><?= e($periode) ?></span></p>

<!-- ===== KPI ===== -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
  <?php
  $kpi = [
    ['Barang Masuk', number_format($totalMasuk,0,',','.') . ' unit', 'arrow-down-to-line', 'emerald', ''],
    ['Barang Keluar', number_format($totalKeluar,0,',','.') . ' unit', 'arrow-up-from-line', 'rose', ''],
    ['Nilai Penjualan', 'Rp ' . number_format($nilaiJual,0,',','.'), 'trending-up', 'blue', $jmlInvoice . ' invoice'],
    ['Nilai Persediaan', 'Rp ' . number_format($nilaiStok,0,',','.'), 'layers', 'violet', 'stok saat ini'],
  ];
  foreach ($kpi as [$label,$val,$icon,$c,$sub]): ?>
    <div class="bg-white border border-slate-200/70 rounded-2xl p-5">
      <span class="grid place-items-center w-11 h-11 rounded-xl bg-<?= $c ?>-50 text-<?= $c ?>-600"><i data-lucide="<?= $icon ?>" class="w-5 h-5"></i></span>
      <p class="mt-4 text-2xl font-bold text-slate-900"><?= $val ?></p>
      <p class="text-sm text-slate-500 mt-1"><?= $label ?><?php if($sub): ?> <span class="text-slate-400">· <?= $sub ?></span><?php endif; ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-5 mt-6">

  <!-- Barang terlaris -->
  <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5">
    <div class="flex items-center gap-2 mb-4">
      <i data-lucide="award" class="w-5 h-5 text-amber-500"></i>
      <h2 class="font-semibold text-slate-900">Barang Terlaris</h2>
    </div>
    <?php if (!$terlaris): ?>
      <p class="text-sm text-slate-400 py-6 text-center">Belum ada penjualan pada periode ini.</p>
    <?php else: foreach ($terlaris as $t): $pct = round((int)$t['qty'] / max($maxQty,1) * 100); ?>
      <div class="py-2.5">
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-sm font-medium text-slate-800 truncate pr-2"><?= e($t['nama_barang']) ?></span>
          <span class="text-sm font-bold text-slate-700 whitespace-nowrap"><?= (int)$t['qty'] ?> <?= e($t['satuan']) ?></span>
        </div>
        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
          <div class="h-full rounded-full bg-blue-600" style="width: <?= $pct ?>%"></div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Rekap invoice -->
  <div class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="receipt" class="w-5 h-5 text-slate-400"></i>
      <h2 class="font-semibold text-slate-900">Rekap Invoice</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm table-pro">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr>
            <th class="px-5 py-3 font-medium">No. Invoice</th>
            <th class="px-5 py-3 font-medium">Pelanggan</th>
            <th class="px-5 py-3 font-medium">Tanggal</th>
            <th class="px-5 py-3 font-medium text-right">Nilai</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!$rekap): ?>
            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Tidak ada transaksi pada periode ini.</td></tr>
          <?php else: foreach ($rekap as $r): ?>
            <tr class="hover:bg-slate-50/60 transition">
              <td class="px-5 py-3"><a href="<?= BASE_URL ?>/karyawan/invoice_view.php?inv=<?= urlencode($r['nomor_invoice']) ?>" class="font-mono text-xs text-blue-600 hover:underline"><?= e($r['nomor_invoice']) ?></a></td>
              <td class="px-5 py-3 text-slate-600"><?= e($r['pelanggan'] ?: '-') ?></td>
              <td class="px-5 py-3 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
              <td class="px-5 py-3 text-right font-medium text-slate-800">Rp <?= number_format((int)$r['nilai'],0,',','.') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <?php if ($rekap): ?>
        <tfoot>
          <tr class="bg-slate-50 font-bold text-slate-900">
            <td class="px-5 py-3" colspan="3">Total Penjualan Periode Ini</td>
            <td class="px-5 py-3 text-right">Rp <?= number_format($nilaiJual,0,',','.') ?></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>