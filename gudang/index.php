<?php
require __DIR__ . '/config/security.php';

$fitur = [
  ['scan-line','Scan Barcode Kamera','Catat barang masuk & keluar cukup dengan mengarahkan kamera ke barcode produk.'],
  ['refresh-cw','Stok Otomatis (ACID)','Setiap transaksi memperbarui stok dalam satu proses aman — tidak akan pernah salah hitung.'],
  ['receipt','Invoice & Cetak PDF','Terbitkan nota bernomor unik untuk barang keluar, siap diunduh atau dicetak.'],
  ['users','Multi Pengguna','Admin mengelola banyak akun karyawan dengan hak akses berjenjang.'],
  ['bar-chart-3','Dashboard & Laporan','Pantau tren transaksi, distribusi stok, dan rekap penjualan dengan filter tanggal.'],
  ['tag','Cetak Label Barcode','Hasilkan label barcode sendiri untuk ditempel — alur kerja gudang yang lengkap.'],
];
$steps = [
  ['Daftarkan Barang','Tambah master barang lengkap dengan barcode, foto, dan harga.'],
  ['Catat Transaksi','Scan barcode untuk barang masuk, atau susun keranjang untuk barang keluar.'],
  ['Pantau & Laporkan','Lihat stok realtime di dashboard, lalu terbitkan invoice & laporan.'],
];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIGMA — Sistem Inventory Gudang & Manajemen</title>
  <script src="<?= BASE_URL ?>/assets/js/tailwind.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-white text-slate-800 antialiased">

<!-- NAVBAR -->
<nav class="fixed top-0 inset-x-0 z-50 glass border-b border-slate-200/80">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2.5 font-semibold text-slate-900">
      <span class="grid place-items-center w-9 h-9 rounded-xl bg-blue-600 text-white"><i data-lucide="package" class="w-5 h-5"></i></span>
      <span class="tracking-tight text-lg">SIGMA</span>
    </div>
    <div class="flex items-center gap-6">
      <a href="#fitur" class="hidden sm:block text-sm text-slate-600 hover:text-slate-900 transition">Fitur</a>
      <a href="#cara" class="hidden sm:block text-sm text-slate-600 hover:text-slate-900 transition">Cara Kerja</a>
      <a href="login.php" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 transition">Masuk <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
  </div>
</nav>

<!-- HERO -->
<header class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
  <div class="absolute inset-0 -z-10">
    <div class="absolute top-0 right-0 w-[36rem] h-[36rem] bg-blue-100/50 rounded-full blur-3xl -translate-y-1/3 translate-x-1/4"></div>
    <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:26px_26px]"></div>
  </div>

  <div class="max-w-6xl mx-auto px-6 grid lg:grid-cols-2 gap-12 lg:gap-10 items-center">
    <div>
      <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-medium text-slate-600 mb-6">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Manajemen Gudang Elektronik
      </span>
      <h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-bold text-slate-900 leading-[1.08] tracking-tight">
        Kelola stok gudang dengan <span class="text-blue-600">presisi</span> & kecepatan.
      </h1>
      <p class="mt-6 text-lg text-slate-500 leading-relaxed max-w-md">
        Scan barcode, hitung stok otomatis, terbitkan invoice, dan pantau semuanya dari satu dasbor — dirancang khusus untuk gudang elektronik.
      </p>
      <div class="mt-8 flex flex-wrap items-center gap-3">
        <a href="login.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Mulai Sekarang <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
        <a href="#fitur" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-100 transition">Pelajari fitur</a>
      </div>
      <div class="mt-10 flex items-center gap-6 text-sm text-slate-400">
        <span class="flex items-center gap-2"><i data-lucide="scan-line" class="w-4 h-4"></i> Scan kamera</span>
        <span class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4"></i> Aman</span>
        <span class="flex items-center gap-2"><i data-lucide="wifi-off" class="w-4 h-4"></i> Offline</span>
      </div>
    </div>

    <!-- MOCKUP PRODUK -->
    <div class="relative">
      <div class="float rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/50 overflow-hidden">
        <div class="flex items-center gap-1.5 px-4 h-9 bg-slate-100 border-b border-slate-200">
          <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          <span class="ml-3 text-[10px] text-slate-400 font-mono">SIGMA | Sistem Inventory & Manajemen </span>
        </div>
        <div class="flex h-80">
          <div class="w-16 bg-slate-700 flex flex-col items-center py-3 gap-2.5">
            <span class="grid place-items-center w-8 h-8 rounded-lg bg-blue-600 text-white"><i data-lucide="package" class="w-4 h-4"></i></span>
            <span class="grid place-items-center w-8 h-8 rounded-lg bg-white/15 text-white"><i data-lucide="layout-dashboard" class="w-4 h-4"></i></span>
            <span class="grid place-items-center w-8 h-8 rounded-lg text-slate-400"><i data-lucide="box" class="w-4 h-4"></i></span>
            <span class="grid place-items-center w-8 h-8 rounded-lg text-slate-400"><i data-lucide="receipt" class="w-4 h-4"></i></span>
          </div>
          <div class="flex-1 p-4 bg-slate-50">
            <div class="grid grid-cols-3 gap-2 mb-3">
              <div class="bg-white border border-slate-200 rounded-lg p-2.5"><p class="text-[8px] text-slate-400 uppercase tracking-wide">Stok</p><p class="text-lg font-bold text-slate-900 leading-none mt-1">1.240</p></div>
              <div class="bg-white border border-slate-200 rounded-lg p-2.5"><p class="text-[8px] text-slate-400 uppercase tracking-wide">Jenis</p><p class="text-lg font-bold text-slate-900 leading-none mt-1">87</p></div>
              <div class="bg-white border border-slate-200 rounded-lg p-2.5"><p class="text-[8px] text-slate-400 uppercase tracking-wide">Transaksi</p><p class="text-lg font-bold text-emerald-600 leading-none mt-1">23</p></div>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-3">
              <p class="text-[10px] font-semibold text-slate-700 mb-2.5">Tren Transaksi</p>
              <div class="flex items-end gap-1.5 h-32">
                <?php foreach ([45,68,32,82,55,72,40,90,60,76,50,86] as $bv): ?>
                  <div class="flex-1 h-full flex items-end"><div class="w-full rounded-sm bg-blue-500" style="height: <?= $bv ?>%"></div></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="absolute -left-5 bottom-10 hidden sm:flex bg-white rounded-xl border border-slate-200 shadow-xl px-3 py-2.5 items-center gap-2.5">
        <span class="grid place-items-center w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="check" class="w-4 h-4"></i></span>
        <div><p class="text-xs font-semibold text-slate-800">Invoice diterbitkan</p><p class="text-[10px] text-slate-400 font-mono">INV-20260625-0007</p></div>
      </div>
    </div>
  </div>
</header>

<!-- STRIP KAPABILITAS -->
<section class="border-y border-slate-100">
  <div class="max-w-6xl mx-auto px-6 py-7 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-slate-500">
    <span class="flex items-center gap-2"><i data-lucide="lock" class="w-4 h-4 text-slate-400"></i> Password terenkripsi</span>
    <span class="flex items-center gap-2"><i data-lucide="database" class="w-4 h-4 text-slate-400"></i> Transaksi ACID</span>
    <span class="flex items-center gap-2"><i data-lucide="wifi-off" class="w-4 h-4 text-slate-400"></i> Berjalan 100% offline</span>
    <span class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-slate-400"></i> Pembaruan realtime</span>
  </div>
</section>

<!-- FITUR -->
<section id="fitur" class="py-20 lg:py-28">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-xl reveal">
      <p class="text-sm font-semibold text-blue-600 uppercase tracking-wider">Fitur</p>
      <h2 class="text-3xl font-bold text-slate-900 mt-2 tracking-tight">Semua alat untuk operasional gudang</h2>
      <p class="text-slate-500 mt-3">Dari pencatatan barang hingga laporan — terintegrasi dalam satu sistem yang ringan.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-x-8 gap-y-12 mt-14">
      <?php foreach ($fitur as $f): ?>
        <div class="reveal">
          <div class="w-11 h-11 grid place-items-center rounded-xl bg-blue-50 text-blue-600 mb-4"><i data-lucide="<?= $f[0] ?>" class="w-5 h-5"></i></div>
          <h3 class="font-semibold text-slate-900"><?= $f[1] ?></h3>
          <p class="text-slate-500 text-sm mt-2 leading-relaxed"><?= $f[2] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CARA KERJA -->
<section id="cara" class="py-20 lg:py-28 bg-slate-50/60 border-y border-slate-100">
  <div class="max-w-5xl mx-auto px-6">
    <div class="text-center max-w-xl mx-auto reveal">
      <p class="text-sm font-semibold text-blue-600 uppercase tracking-wider">Cara Kerja</p>
      <h2 class="text-3xl font-bold text-slate-900 mt-2 tracking-tight">Mulai dalam tiga langkah</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-10 mt-14">
      <?php $i = 1; foreach ($steps as $s): ?>
        <div class="reveal">
          <div class="w-12 h-12 grid place-items-center rounded-2xl bg-blue-600 text-white font-bold"><?= $i++ ?></div>
          <h3 class="font-semibold text-slate-900 mt-5"><?= $s[0] ?></h3>
          <p class="text-slate-500 text-sm mt-2 leading-relaxed"><?= $s[1] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-20 lg:py-28">
  <div class="max-w-5xl mx-auto px-6">
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 px-8 py-14 sm:px-14 text-center reveal">
      <div class="absolute top-0 right-0 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl"></div>
      <h2 class="relative text-3xl sm:text-4xl font-bold text-white tracking-tight">Siap mengelola gudang lebih efisien?</h2>
      <p class="relative text-slate-400 mt-4 max-w-md mx-auto">Masuk ke sistem dan mulai catat transaksi pertama Anda hari ini.</p>
      <a href="login.php" class="relative inline-flex items-center gap-2 mt-8 px-7 py-3.5 rounded-xl bg-white text-slate-900 font-semibold hover:bg-slate-100 transition">Masuk ke Sistem <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="border-t border-slate-100 bg-slate-50/50">
  <div class="max-w-2xl mx-auto px-6 py-16 text-center">
    <div class="inline-flex items-center gap-2.5 font-bold text-slate-900 text-xl">
      <span class="grid place-items-center w-10 h-10 rounded-xl bg-blue-600 text-white"><i data-lucide="package" class="w-5 h-5"></i></span>
      SIGMA
    </div>
    <p class="text-slate-500 mt-4 leading-relaxed">
      Sistem Inventory Gudang &amp; Manajemen — kelola stok, transaksi, dan invoice gudang elektronik Anda dalam satu platform.
    </p>
    <div class="flex items-center justify-center gap-8 mt-7 text-sm font-medium">
      <a href="#fitur" class="text-slate-600 hover:text-blue-600 transition">Fitur</a>
      <a href="#cara" class="text-slate-600 hover:text-blue-600 transition">Cara Kerja</a>
      <a href="login.php" class="text-slate-600 hover:text-blue-600 transition">Masuk</a>
    </div>
    <div class="mt-10 pt-7 border-t border-slate-200/70">
      <p class="text-sm text-slate-400">© <?= date('Y') ?> <span class="font-semibold text-slate-500">SIGMA</span> · Sistem Inventory Gudang &amp; Manajemen</p>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/lucide.min.js"></script>
<script>
  lucide.createIcons();
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('show'); });
  }, { threshold: .15 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
</script>
</body>
</html>