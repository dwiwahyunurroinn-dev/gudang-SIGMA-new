<?php
if (!isset($_SESSION['user_id'])) { require_login(); }
$u     = current_user();
$flash = get_flash();

$menuMain = [
  ['dashboard','Dashboard','layout-dashboard', BASE_URL . ($u['role']==='admin' ? '/admin/dashboard.php' : '/karyawan/dashboard.php')],
  ['barang',   'Data Barang','package',          BASE_URL . '/karyawan/barang.php'],
  ['masuk',    'Stok Masuk','arrow-down-to-line',BASE_URL . '/karyawan/stok_masuk.php'],
  ['keluar',   'Stok Keluar','arrow-up-from-line',BASE_URL . '/karyawan/stok_keluar.php'],
  ['label',    'Cetak Label','tag',              BASE_URL . '/karyawan/label.php'],
];
$menuAdmin = [
  ['karyawan', 'Kelola Karyawan','users',        BASE_URL . '/admin/karyawan.php'],
  ['laporan',  'Laporan','bar-chart-3',          BASE_URL . '/admin/laporan.php'],
];
$initial = strtoupper(mb_substr($u['nama'] ?: 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title ?? 'Dashboard') ?> — SIGMA</title>
  <script src="<?= BASE_URL ?>/assets/js/tailwind.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-slate-50 text-slate-800">
<div class="flex min-h-screen">

  <!-- ===== SIDEBAR (terang) ===== -->
  <aside id="sidebar" class="fixed lg:sticky top-0 z-40 -translate-x-full lg:translate-x-0 transition-transform duration-300 w-64 shrink-0 h-screen bg-white border-r border-slate-200 flex flex-col">
    <!-- Logo -->
    <div class="h-16 flex items-center gap-2.5 px-5 border-b border-slate-100">
      <span class="grid place-items-center w-10 h-10 rounded-xl bg-blue-600 text-white"><i data-lucide="package" class="w-[22px] h-[22px]"></i></span>
      <span class="font-bold text-xl text-slate-900 tracking-tight">SIGMA</span>
    </div>

    <nav class="flex-1 px-3 py-5 overflow-y-auto">
      <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu</p>
      <div class="space-y-1">
        <?php foreach ($menuMain as [$key,$label,$icon,$url]): $isActive = ($active ?? '')===$key; ?>
          <a href="<?= $url ?>" class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium transition <?= $isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <?php if ($isActive): ?><span class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-blue-600"></span><?php endif; ?>
            <i data-lucide="<?= $icon ?>" class="w-[22px] h-[22px]"></i> <?= $label ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($u['role'] === 'admin'): ?>
      <p class="px-3 mt-6 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>
      <div class="space-y-1">
        <?php foreach ($menuAdmin as [$key,$label,$icon,$url]): $isActive = ($active ?? '')===$key; ?>
          <a href="<?= $url ?>" class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium transition <?= $isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <?php if ($isActive): ?><span class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-blue-600"></span><?php endif; ?>
            <i data-lucide="<?= $icon ?>" class="w-[22px] h-[22px]"></i> <?= $label ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </nav>

    <!-- User -->
    <div class="p-3 border-t border-slate-100">
      <div class="flex items-center gap-3 px-2 py-2">
        <span class="grid place-items-center w-10 h-10 rounded-full bg-blue-600 text-white font-semibold"><?= e($initial) ?></span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-800 truncate"><?= e($u['nama']) ?></p>
          <p class="text-xs text-slate-400 capitalize"><?= e($u['role']) ?></p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition">
        <i data-lucide="log-out" class="w-[22px] h-[22px]"></i> Keluar
      </a>
    </div>
  </aside>
  <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 z-30 hidden lg:hidden"></div>

  <!-- ===== KONTEN UTAMA ===== -->
  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-20 glass border-b border-slate-200 h-16 flex items-center gap-4 px-4 sm:px-6">
      <button onclick="toggleSidebar()" class="lg:hidden grid place-items-center w-10 h-10 rounded-lg hover:bg-slate-100 transition"><i data-lucide="menu" class="w-5 h-5"></i></button>
      <h1 class="font-semibold text-slate-900"><?= e($page_title ?? 'Dashboard') ?></h1>
      <div class="ml-auto flex items-center gap-3">
        <span class="hidden sm:block text-sm text-slate-500">Halo, <b class="text-slate-700 font-medium"><?= e($u['nama']) ?></b></span>
        <span class="grid place-items-center w-9 h-9 rounded-full bg-blue-600 text-white font-semibold text-sm"><?= e($initial) ?></span>
      </div>
    </header>

    <main class="p-4 sm:p-6 lg:p-8 flex-1">
      <?php if ($flash): ?>
        <div class="mb-6 flex items-center gap-2 rounded-xl px-4 py-3 text-sm border
          <?= $flash['type']==='success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
          <i data-lucide="<?= $flash['type']==='success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
          <?= e($flash['msg']) ?>
        </div>
      <?php endif; ?>