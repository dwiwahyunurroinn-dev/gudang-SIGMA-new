<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$bStmt = $pdo->prepare("SELECT b.*, k.nama_kategori FROM barang b
  LEFT JOIN kategori k ON k.id_kategori = b.id_kategori WHERE b.id_barang = ?");
$bStmt->execute([$id]);
$barang = $bStmt->fetch();

if (!$barang) {
    flash('error', 'Barang tidak ditemukan.');
    header("Location: " . BASE_URL . "/karyawan/barang.php");
    exit;
}

// Rekap masuk & keluar
$mStmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM stok_masuk  WHERE id_barang = ?");
$mStmt->execute([$id]);
$totMasuk = (int) $mStmt->fetchColumn();

$kStmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM stok_keluar WHERE id_barang = ?");
$kStmt->execute([$id]);
$totKeluar = (int) $kStmt->fetchColumn();

// Gabungan riwayat transaksi
$rStmt = $pdo->prepare("
  SELECT 'masuk' AS tipe, m.jumlah, m.tanggal, m.keterangan, m.created_at,
         u.nama AS petugas, NULL AS nomor_invoice, NULL AS pelanggan
    FROM stok_masuk m LEFT JOIN users u ON u.id = m.user_id
   WHERE m.id_barang = ?
  UNION ALL
  SELECT 'keluar' AS tipe, k.jumlah, k.tanggal, k.keterangan, k.created_at,
         u.nama AS petugas, k.nomor_invoice, k.pelanggan
    FROM stok_keluar k LEFT JOIN users u ON u.id = k.user_id
   WHERE k.id_barang = ?
  ORDER BY created_at DESC, tipe");
$rStmt->execute([$id, $id]);
$riwayat = $rStmt->fetchAll();

$stok    = (int)$barang['stok_sekarang'];
$stokCls = $stok === 0 ? 'bg-rose-100 text-rose-700' : ($stok <= 5 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');

$page_title = 'Riwayat Barang';
$active     = 'barang';
require __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/karyawan/barang.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition mb-4">
  <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Data Barang
</a>

<!-- ===== KARTU IDENTITAS BARANG ===== -->
<div class="bg-white border border-slate-200/70 rounded-2xl p-5 sm:p-6">
  <div class="flex flex-col sm:flex-row sm:items-center gap-5">
    <?php if ($barang['gambar']): ?>
      <img src="<?= BASE_URL ?>/assets/uploads/<?= e($barang['gambar']) ?>" class="w-20 h-20 rounded-xl object-cover border border-slate-200 shrink-0">
    <?php else: ?>
      <span class="grid place-items-center w-20 h-20 rounded-xl bg-slate-100 text-slate-400 shrink-0"><i data-lucide="image" class="w-8 h-8"></i></span>
    <?php endif; ?>
    <div class="flex-1 min-w-0">
      <h2 class="text-xl font-bold text-slate-900"><?= e($barang['nama_barang']) ?></h2>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-sm text-slate-500">
        <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= e($barang['kode_barcode']) ?></span>
        <span><?= e($barang['merk'] ?: 'Tanpa merk') ?></span>
        <span class="text-slate-300">·</span>
        <span><?= e($barang['nama_kategori'] ?: 'Tanpa kategori') ?></span>
        <span class="text-slate-300">·</span>
        <span>Rp <?= number_format((int)$barang['harga'], 0, ',', '.') ?></span>
      </div>
    </div>
    <div class="shrink-0">
      <span class="inline-block px-3 py-1.5 rounded-xl text-sm font-bold <?= $stokCls ?>"><?= $stok ?> <?= e($barang['satuan']) ?></span>
    </div>
  </div>

  <div class="grid grid-cols-3 gap-3 mt-6">
    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
      <p class="text-xs font-medium text-emerald-700 flex items-center gap-1.5"><i data-lucide="arrow-down-to-line" class="w-3.5 h-3.5"></i> Total Masuk</p>
      <p class="text-2xl font-bold text-emerald-700 mt-1 tabular-nums">+<?= number_format($totMasuk,0,',','.') ?></p>
    </div>
    <div class="rounded-xl bg-rose-50 border border-rose-100 p-4">
      <p class="text-xs font-medium text-rose-700 flex items-center gap-1.5"><i data-lucide="arrow-up-from-line" class="w-3.5 h-3.5"></i> Total Keluar</p>
      <p class="text-2xl font-bold text-rose-700 mt-1 tabular-nums">−<?= number_format($totKeluar,0,',','.') ?></p>
    </div>
    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
      <p class="text-xs font-medium text-slate-500 flex items-center gap-1.5"><i data-lucide="layers" class="w-3.5 h-3.5"></i> Stok Saat Ini</p>
      <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums"><?= number_format($stok,0,',','.') ?></p>
    </div>
  </div>
</div>

<!-- ===== TIMELINE RIWAYAT ===== -->
<div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden mt-5">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
    <i data-lucide="history" class="w-5 h-5 text-blue-500"></i>
    <h2 class="font-semibold text-slate-900">Riwayat Transaksi</h2>
    <span class="ml-auto text-xs font-medium text-slate-400"><?= count($riwayat) ?> transaksi</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm table-pro">
      <thead class="text-left">
        <tr>
          <th class="px-5 py-3 font-medium">Tipe</th>
          <th class="px-5 py-3 font-medium">Tanggal</th>
          <th class="px-5 py-3 font-medium text-right">Jumlah</th>
          <th class="px-5 py-3 font-medium">Keterangan</th>
          <th class="px-5 py-3 font-medium">Petugas</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$riwayat): ?>
          <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">
            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
            Belum ada transaksi untuk barang ini.
          </td></tr>
        <?php else: foreach ($riwayat as $r): $masuk = $r['tipe'] === 'masuk'; ?>
          <tr class="hover:bg-slate-50/60 transition">
            <td class="px-5 py-3.5">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold <?= $masuk ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                <i data-lucide="<?= $masuk ? 'arrow-down-to-line' : 'arrow-up-from-line' ?>" class="w-3.5 h-3.5"></i>
                <?= $masuk ? 'Masuk' : 'Keluar' ?>
              </span>
            </td>
            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td class="px-5 py-3.5 text-right font-semibold tabular-nums <?= $masuk ? 'text-emerald-600' : 'text-rose-600' ?>">
              <?= $masuk ? '+' : '−' ?><?= (int)$r['jumlah'] ?>
            </td>
            <td class="px-5 py-3.5 text-slate-600">
              <?php if (!$masuk && $r['nomor_invoice']): ?>
                <span class="font-mono text-xs text-blue-600"><?= e($r['nomor_invoice']) ?></span>
                <?php if ($r['pelanggan']): ?><span class="text-slate-400"> · <?= e($r['pelanggan']) ?></span><?php endif; ?>
              <?php elseif ($r['keterangan']): ?>
                <?= e($r['keterangan']) ?>
              <?php else: ?>
                <span class="text-slate-300">—</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5 text-slate-500"><?= e($r['petugas'] ?: '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
