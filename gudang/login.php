<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/security.php';

if (isset($_SESSION['user_id'])) {
    $dest = $_SESSION['role'] === 'admin' ? '/admin/dashboard.php' : '/karyawan/dashboard.php';
    header("Location: " . BASE_URL . $dest);
    exit;
}

$error    = '';
$redirect = ''; // jika terisi → login sukses, mainkan animasi keluar lalu pindah
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            $dest = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/karyawan/dashboard.php';
            $redirect = BASE_URL . $dest; // tidak langsung pindah — animasi keluar dulu
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk — SIGMA</title>
  <?php if ($redirect): ?><noscript><meta http-equiv="refresh" content="0;url=<?= e($redirect) ?>"></noscript><?php endif; ?>
  <script src="<?= BASE_URL ?>/assets/js/tailwind.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="min-h-screen bg-white text-slate-800 lg:grid lg:grid-cols-2 overflow-x-hidden">

  <!-- ===== PANEL KIRI ===== -->
  <div id="panelLeft" class="<?= $redirect ? '' : 'anim-left' ?> relative hidden lg:flex flex-col justify-between p-12 overflow-hidden bg-gradient-to-br from-blue-600 to-blue-700">
    <div class="absolute inset-0 opacity-[0.12] [background-image:radial-gradient(#fff_1px,transparent_1px)] [background-size:26px_26px]"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-32 -left-20 w-96 h-96 bg-blue-400/30 rounded-full blur-3xl"></div>

    <a href="index.php" class="relative z-10 inline-flex items-center gap-2.5 font-bold text-xl text-white">
      <span class="grid place-items-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur"><i data-lucide="package" class="w-5 h-5"></i></span>
      SIGMA
    </a>

    <div class="relative z-10 max-w-md">
      <h2 class="text-4xl font-bold text-white leading-tight tracking-tight">Kelola gudang Anda dengan tenang.</h2>
      <p class="mt-4 text-blue-100 leading-relaxed">Masuk untuk mengelola stok, mencatat transaksi, dan menerbitkan invoice — semua dari satu dasbor.</p>
      <ul class="mt-8 space-y-3">
        <?php foreach (['Scan barcode masuk & keluar', 'Stok terhitung otomatis (ACID)', 'Invoice & laporan instan'] as $poin): ?>
          <li class="flex items-center gap-3 text-white/95">
            <span class="grid place-items-center w-6 h-6 rounded-full bg-white/20"><i data-lucide="check" class="w-3.5 h-3.5"></i></span>
            <?= $poin ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <p class="relative z-10 text-blue-200/80 text-sm">© <?= date('Y') ?> SIGMA · Sistem Inventory Gudang & Manajemen</p>
  </div>

  <!-- ===== PANEL KANAN (form) ===== -->
  <div id="panelRight" class="<?= $redirect ? '' : 'anim-right' ?> flex items-center justify-center min-h-screen lg:min-h-0 p-6 sm:p-8 bg-white">
    <div class="w-full max-w-sm">
      <a href="index.php" class="lg:hidden inline-flex items-center gap-2.5 font-bold text-lg text-slate-900 mb-10">
        <span class="grid place-items-center w-9 h-9 rounded-xl bg-blue-600 text-white"><i data-lucide="package" class="w-5 h-5"></i></span> SIGMA
      </a>

      <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Masuk</p>
      <h1 class="text-2xl font-bold text-slate-900 mt-1.5 tracking-tight">Selamat datang kembali</h1>
      <p class="text-slate-500 text-sm mt-1.5">Gunakan akun Anda untuk masuk ke sistem.</p>

      <?php if ($error): ?>
        <div class="mt-6 flex items-center gap-2 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-xl px-4 py-3">
          <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="mt-7 space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="text-sm font-medium text-slate-700">Username</label>
          <div class="relative mt-1.5">
            <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="username" autofocus required
              class="w-full rounded-xl bg-slate-50 border border-slate-200 pl-10 pr-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition" placeholder="admin">
          </div>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Password</label>
          <div class="relative mt-1.5">
            <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="password" name="password" id="pw" required
              class="w-full rounded-xl bg-slate-50 border border-slate-200 pl-10 pr-11 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition" placeholder="••••••••">
            <button type="button" onclick="togglePw()" class="absolute right-2.5 top-1/2 -translate-y-1/2 grid place-items-center w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
              <i data-lucide="eye"     id="iconShow" class="w-4 h-4"></i>
              <i data-lucide="eye-off" id="iconHide" class="w-4 h-4 hidden"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">
          Masuk <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
      </form>

      <a href="index.php" class="inline-flex items-center gap-1.5 justify-center w-full text-sm text-slate-400 mt-7 hover:text-slate-600 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke beranda
      </a>
    </div>
  </div>

  <script src="<?= BASE_URL ?>/assets/js/lucide.min.js"></script>
  <script>
    lucide.createIcons();
    function togglePw(){
      const pw = document.getElementById('pw');
      const show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      document.getElementById('iconShow').classList.toggle('hidden', show);
      document.getElementById('iconHide').classList.toggle('hidden', !show);
    }

    <?php if ($redirect): ?>
    // Login sukses → mainkan animasi keluar (panel saling menjauh) lalu pindah
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.getElementById('panelLeft').classList.add('exit-left');
        document.getElementById('panelRight').classList.add('exit-right');
      }, 150);
      setTimeout(() => { window.location.href = <?= json_encode($redirect) ?>; }, 900);
    });
    <?php endif; ?>
  </script>
</body>
</html>