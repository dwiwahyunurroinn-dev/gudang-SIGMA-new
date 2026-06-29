<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

$today         = date('Y-m-d');
$totalJenis    = (int) $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$totalStok     = (int) $pdo->query("SELECT COALESCE(SUM(stok_sekarang),0) FROM barang")->fetchColumn();
$totalKaryawan = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='karyawan'")->fetchColumn();

$stmt = $pdo->prepare("SELECT
  (SELECT COUNT(*) FROM stok_masuk  WHERE tanggal = ?) +
  (SELECT COUNT(*) FROM stok_keluar WHERE tanggal = ?) AS total");
$stmt->execute([$today, $today]);
$trxHariIni = (int) $stmt->fetchColumn();

$menipis = $pdo->query("SELECT nama_barang, merk, stok_sekarang, satuan
  FROM barang WHERE stok_sekarang <= 5 ORDER BY stok_sekarang ASC LIMIT 5")->fetchAll();

$recent = $pdo->query("
  SELECT 'masuk' AS tipe, b.nama_barang, m.jumlah, m.created_at
    FROM stok_masuk m JOIN barang b ON b.id_barang = m.id_barang
  UNION ALL
  SELECT 'keluar' AS tipe, b.nama_barang, k.jumlah, k.created_at
    FROM stok_keluar k JOIN barang b ON b.id_barang = k.id_barang
  ORDER BY created_at DESC LIMIT 6")->fetchAll();

$days = [];
for ($i = 13; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $days[$d] = ['masuk' => 0, 'keluar' => 0]; }
foreach ($pdo->query("SELECT tanggal, SUM(jumlah) AS t FROM stok_masuk  WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY tanggal") as $r)
    if (isset($days[$r['tanggal']])) $days[$r['tanggal']]['masuk'] = (int)$r['t'];
foreach ($pdo->query("SELECT tanggal, SUM(jumlah) AS t FROM stok_keluar WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY tanggal") as $r)
    if (isset($days[$r['tanggal']])) $days[$r['tanggal']]['keluar'] = (int)$r['t'];
$labels = []; $dMasuk = []; $dKeluar = [];
foreach ($days as $d => $v) { $labels[] = date('d/m', strtotime($d)); $dMasuk[] = $v['masuk']; $dKeluar[] = $v['keluar']; }

$catRows = $pdo->query("SELECT COALESCE(k.nama_kategori,'Tanpa Kategori') AS kategori, COALESCE(SUM(b.stok_sekarang),0) AS total
  FROM barang b LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
  GROUP BY k.id_kategori, k.nama_kategori HAVING total > 0 ORDER BY total DESC")->fetchAll();
$catLabels = array_map(fn($r) => $r['kategori'], $catRows);
$catData   = array_map(fn($r) => (int)$r['total'], $catRows);

$h  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$bl = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$tglIndo = $h[date('l')] . ', ' . date('d') . ' ' . $bl[date('F')] . ' ' . date('Y');
$first   = explode(' ', current_user()['nama'])[0];

$page_title = 'Dashboard';
$active     = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-7">
  <h2 class="text-2xl font-bold text-slate-900">Halo, <?= e($first) ?> 👋</h2>
  <p class="text-slate-500 mt-1"><?= e($tglIndo) ?></p>
</div>

<!-- KARTU RINGKASAN -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
  <?php
  $cards = [
    ['Jenis Barang', number_format($totalJenis,0,',','.'),     'package', 'blue',    'macam produk'],
    ['Total Stok',   number_format($totalStok,0,',','.'),      'layers',  'emerald', 'unit tersedia'],
    ['Transaksi Hari Ini', number_format($trxHariIni,0,',','.'),'repeat', 'amber',   'masuk & keluar'],
    ['Total Karyawan', number_format($totalKaryawan,0,',','.'),'users',   'violet',  'akun terdaftar'],
  ];
  foreach ($cards as [$label,$val,$icon,$c,$sub]): ?>
    <div class="stat-card bg-white border border-slate-200/70 rounded-2xl p-5">
      <span class="grid place-items-center w-11 h-11 rounded-xl bg-gradient-to-br from-<?= $c ?>-500 to-<?= $c ?>-600 text-white shadow-lg shadow-<?= $c ?>-500/30"><i data-lucide="<?= $icon ?>" class="w-5 h-5"></i></span>
      <p class="mt-4 text-3xl font-bold text-slate-900"><?= $val ?></p>
      <p class="text-sm text-slate-500 mt-1"><?= $label ?> <span class="text-slate-400">· <?= $sub ?></span></p>
    </div>
  <?php endforeach; ?>
</div>

<!-- GRAFIK -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
  <div class="lg:col-span-2 bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-500"></i>
      <h2 class="font-semibold text-slate-900">Tren Transaksi</h2>
      <span class="ml-auto text-xs font-medium uppercase tracking-wider text-slate-400">14 hari terakhir</span>
    </div>
    <div class="p-5"><div class="h-64"><canvas id="chartTren"></canvas></div></div>
  </div>

  <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="pie-chart" class="w-5 h-5 text-violet-500"></i>
      <h2 class="font-semibold text-slate-900">Stok per Kategori</h2>
    </div>
    <div class="p-5">
      <?php if ($catData): ?>
        <div class="h-64"><canvas id="chartKategori"></canvas></div>
      <?php else: ?>
        <p class="text-sm text-slate-400 py-16 text-center">Belum ada stok untuk ditampilkan.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- PANEL BAWAH -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
  <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
        <h2 class="font-semibold text-slate-900">Perlu Restock</h2>
      </div>
      <a href="<?= BASE_URL ?>/karyawan/stok_masuk.php" class="text-xs font-medium text-blue-600 hover:text-blue-700">Tambah stok →</a>
    </div>
    <div class="px-5 py-2">
      <?php if (!$menipis): ?>
        <p class="text-sm text-slate-400 py-6 text-center">Semua stok dalam kondisi aman.</p>
      <?php else: foreach ($menipis as $m): $habis = (int)$m['stok_sekarang']===0; ?>
        <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
          <div>
            <p class="text-sm font-medium text-slate-800"><?= e($m['nama_barang']) ?></p>
            <p class="text-xs text-slate-400"><?= e($m['merk'] ?: 'Tanpa merk') ?></p>
          </div>
          <span class="text-sm font-semibold <?= $habis ? 'text-rose-600' : 'text-amber-600' ?>"><?= (int)$m['stok_sekarang'] ?> <?= e($m['satuan']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="history" class="w-5 h-5 text-blue-500"></i>
      <h2 class="font-semibold text-slate-900">Aktivitas Terbaru</h2>
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
</div>

<?php
ob_start(); ?>
<script src="<?= BASE_URL ?>/assets/js/chart.min.js"></script>
<script>
Chart.defaults.font.family = "Inter, ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#94a3b8';
const _dark = document.documentElement.classList.contains('dark');
const _grid = _dark ? '#1e2a44' : '#f1f5f9';
const _arcBorder = _dark ? '#111c30' : '#fff';
new Chart(document.getElementById('chartTren'), {
  type: 'bar',
  data: { labels: <?= json_encode($labels) ?>,
    datasets: [
      { label: 'Masuk',  data: <?= json_encode($dMasuk) ?>,  backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 16 },
      { label: 'Keluar', data: <?= json_encode($dKeluar) ?>, backgroundColor: '#f43f5e', borderRadius: 4, maxBarThickness: 16 } ] },
  options: { responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 7, padding: 16 } } },
    scales: { x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 } } },
      y: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, font: { size: 11 } }, grid: { color: _grid } } } }
});
<?php if ($catData): ?>
new Chart(document.getElementById('chartKategori'), {
  type: 'doughnut',
  data: { labels: <?= json_encode($catLabels) ?>,
    datasets: [{ data: <?= json_encode($catData) ?>,
      backgroundColor: ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#ec4899'], borderWidth: 3, borderColor: _arcBorder }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: '64%',
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 14, font: { size: 11 } } } } }
});
<?php endif; ?>
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>