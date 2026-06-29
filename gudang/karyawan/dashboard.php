<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$today      = date('Y-m-d');
$totalJenis = (int) $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$totalStok  = (int) $pdo->query("SELECT COALESCE(SUM(stok_sekarang),0) FROM barang")->fetchColumn();

$stmt = $pdo->prepare("SELECT
  (SELECT COUNT(*) FROM stok_masuk  WHERE tanggal = ?) +
  (SELECT COUNT(*) FROM stok_keluar WHERE tanggal = ?) AS total");
$stmt->execute([$today, $today]);
$trxHariIni = (int) $stmt->fetchColumn();

$recent = $pdo->query("
  SELECT 'masuk' AS tipe, b.nama_barang, m.jumlah, m.created_at
    FROM stok_masuk m JOIN barang b ON b.id_barang = m.id_barang
  UNION ALL
  SELECT 'keluar' AS tipe, b.nama_barang, k.jumlah, k.created_at
    FROM stok_keluar k JOIN barang b ON b.id_barang = k.id_barang
  ORDER BY created_at DESC LIMIT 6")->fetchAll();

$first = explode(' ', current_user()['nama'])[0];

$page_title = 'Dashboard';
$active     = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-7">
  <h2 class="text-2xl font-bold text-slate-900">Halo, <?= e($first) ?> 👋</h2>
  <p class="text-slate-500 mt-1">Selamat bekerja hari ini.</p>
</div>

<!-- KARTU RINGKASAN -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
  <?php
  $cards = [
    ['Jenis Barang', number_format($totalJenis,0,',','.'), 'package','blue',   'macam produk'],
    ['Total Stok',   number_format($totalStok,0,',','.'),  'layers','emerald', 'unit tersedia'],
    ['Transaksi Hari Ini', number_format($trxHariIni,0,',','.'),'repeat','amber','masuk & keluar'],
  ];
  foreach ($cards as [$label,$val,$icon,$c,$sub]): ?>
    <div class="stat-card bg-white border border-slate-200/70 rounded-2xl p-5">
      <span class="grid place-items-center w-11 h-11 rounded-xl bg-gradient-to-br from-<?= $c ?>-500 to-<?= $c ?>-600 text-white shadow-lg shadow-<?= $c ?>-500/30"><i data-lucide="<?= $icon ?>" class="w-5 h-5"></i></span>
      <p class="mt-4 text-3xl font-bold text-slate-900"><?= $val ?></p>
      <p class="text-sm text-slate-500 mt-1"><?= $label ?> <span class="text-slate-400">· <?= $sub ?></span></p>
    </div>
  <?php endforeach; ?>
</div>

<!-- AKSI CEPAT -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mt-5">
  <?php
  $aksi = [
    ['Tambah Barang','Daftarkan produk baru','plus-circle', BASE_URL.'/karyawan/barang.php'],
    ['Barang Masuk','Catat stok masuk','arrow-down-to-line', BASE_URL.'/karyawan/stok_masuk.php'],
    ['Barang Keluar','Catat stok keluar','arrow-up-from-line', BASE_URL.'/karyawan/stok_keluar.php'],
  ];
  foreach ($aksi as [$t,$d,$icon,$url]): ?>
    <a href="<?= $url ?>" class="group bg-white border border-slate-200/70 rounded-2xl p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100 transition">
      <span class="grid place-items-center w-12 h-12 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition"><i data-lucide="<?= $icon ?>" class="w-6 h-6"></i></span>
      <div>
        <p class="font-semibold text-slate-900"><?= $t ?></p>
        <p class="text-sm text-slate-500"><?= $d ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<!-- TRANSAKSI TERBARU -->
<div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden mt-5">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
    <i data-lucide="history" class="w-5 h-5 text-blue-500"></i>
    <h2 class="font-semibold text-slate-900">Transaksi Terbaru</h2>
  </div>
  <div class="px-5 py-2">
    <?php if (!$recent): ?>
      <p class="text-sm text-slate-400 py-6 text-center">Belum ada transaksi.</p>
    <?php else: foreach ($recent as $r): $masuk = $r['tipe']==='masuk'; ?>
      <div class="flex items-center gap-3 py-3 border-b border-slate-100 last:border-0">
        <span class="grid place-items-center w-9 h-9 rounded-lg <?= $masuk ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?>">
          <i data-lucide="<?= $masuk ? 'arrow-down-to-line' : 'arrow-up-from-line' ?>" class="w-4 h-4"></i>
        </span>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-800 truncate"><?= e($r['nama_barang']) ?></p>
          <p class="text-xs text-slate-400"><?= $masuk ? 'Barang masuk' : 'Barang keluar' ?> · <?= date('d M · H:i', strtotime($r['created_at'])) ?></p>
        </div>
        <span class="text-sm font-semibold tabular-nums <?= $masuk ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $masuk ? '+' : '−' ?><?= (int)$r['jumlah'] ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>