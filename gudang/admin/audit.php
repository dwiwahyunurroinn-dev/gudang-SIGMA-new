<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

audit_ensure(); // pastikan tabel ada (mis. saat belum ada aktivitas tercatat)

// ====== FILTER ======
$from    = trim($_GET['from'] ?? '');
$to      = trim($_GET['to'] ?? '');
$entitas = trim($_GET['entitas'] ?? '');
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = [];
$params = [];
if ($from !== '') { $where[] = "DATE(created_at) >= ?"; $params[] = $from; }
if ($to   !== '') { $where[] = "DATE(created_at) <= ?"; $params[] = $to; }
if ($entitas !== '') { $where[] = "entitas = ?"; $params[] = $entitas; }
if ($q !== '') { $where[] = "(deskripsi LIKE ? OR user_nama LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$total = (int) (function() use ($pdo, $whereSql, $params) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM audit_log" . $whereSql);
    $s->execute($params);
    return $s->fetchColumn();
})();
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM audit_log" . $whereSql . " ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Tampilan badge aksi
$aksiCls = [
    'tambah' => 'bg-emerald-50 text-emerald-700',
    'edit'   => 'bg-blue-50 text-blue-700',
    'hapus'  => 'bg-rose-50 text-rose-700',
    'void'   => 'bg-amber-50 text-amber-700',
    'login'  => 'bg-violet-50 text-violet-700',
];
$aksiIcon = [
    'tambah' => 'plus', 'edit' => 'pencil', 'hapus' => 'trash-2', 'void' => 'rotate-ccw', 'login' => 'log-in',
];
$entitasLabel = [
    'barang' => 'Barang', 'stok_masuk' => 'Stok Masuk', 'stok_keluar' => 'Stok Keluar',
    'karyawan' => 'Karyawan', 'auth' => 'Autentikasi',
];

// Helper untuk membangun query string filter (mempertahankan filter saat pindah halaman)
function qs(array $over = []): string {
    $base = ['from'=>$_GET['from']??'', 'to'=>$_GET['to']??'', 'entitas'=>$_GET['entitas']??'', 'q'=>$_GET['q']??''];
    return http_build_query(array_merge($base, $over));
}

$page_title = 'Log Aktivitas';
$active     = 'audit';
require __DIR__ . '/../includes/header.php';

$inp = "w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition";
?>

<div class="mb-5">
  <h2 class="text-2xl font-bold text-slate-900">Log Aktivitas</h2>
  <p class="text-slate-500 mt-1">Jejak audit setiap perubahan data — siapa, apa, dan kapan.</p>
</div>

<!-- FILTER -->
<form method="GET" class="bg-white border border-slate-200/70 rounded-2xl p-4 sm:p-5 mb-5">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
    <div>
      <label class="text-xs font-medium text-slate-500">Dari tanggal</label>
      <input type="date" name="from" value="<?= e($from) ?>" class="<?= $inp ?> mt-1">
    </div>
    <div>
      <label class="text-xs font-medium text-slate-500">Sampai tanggal</label>
      <input type="date" name="to" value="<?= e($to) ?>" class="<?= $inp ?> mt-1">
    </div>
    <div>
      <label class="text-xs font-medium text-slate-500">Entitas</label>
      <select name="entitas" class="<?= $inp ?> mt-1">
        <option value="">Semua</option>
        <?php foreach ($entitasLabel as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $entitas === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-xs font-medium text-slate-500">Cari</label>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Deskripsi / pengguna" class="<?= $inp ?> mt-1">
    </div>
    <div class="flex items-end gap-2">
      <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
        <i data-lucide="filter" class="w-4 h-4"></i> Terapkan
      </button>
      <?php if ($from || $to || $entitas || $q): ?>
        <a href="<?= BASE_URL ?>/admin/audit.php" class="grid place-items-center w-11 h-11 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition" title="Reset"><i data-lucide="x" class="w-4 h-4"></i></a>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- TABEL -->
<div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
    <i data-lucide="scroll-text" class="w-5 h-5 text-blue-500"></i>
    <h2 class="font-semibold text-slate-900">Riwayat Aktivitas</h2>
    <span class="ml-auto text-xs font-medium text-slate-400"><?= number_format($total,0,',','.') ?> entri</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm table-pro">
      <thead class="text-left">
        <tr>
          <th class="px-5 py-3 font-medium">Waktu</th>
          <th class="px-5 py-3 font-medium">Pengguna</th>
          <th class="px-5 py-3 font-medium">Aksi</th>
          <th class="px-5 py-3 font-medium">Entitas</th>
          <th class="px-5 py-3 font-medium">Deskripsi</th>
          <th class="px-5 py-3 font-medium">IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">
            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
            Belum ada aktivitas tercatat<?= ($from||$to||$entitas||$q) ? ' untuk filter ini' : '' ?>.
          </td></tr>
        <?php else: foreach ($rows as $r):
          $a = $r['aksi'];
          $cls  = $aksiCls[$a]  ?? 'bg-slate-100 text-slate-600';
          $icon = $aksiIcon[$a] ?? 'activity'; ?>
          <tr class="hover:bg-slate-50/60 transition">
            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap"><?= date('d M Y · H:i', strtotime($r['created_at'])) ?></td>
            <td class="px-5 py-3.5 font-medium text-slate-700"><?= e($r['user_nama'] ?: 'Sistem') ?></td>
            <td class="px-5 py-3.5">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold <?= $cls ?>">
                <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i> <?= e(ucfirst($a)) ?>
              </span>
            </td>
            <td class="px-5 py-3.5 text-slate-600"><?= e($entitasLabel[$r['entitas']] ?? $r['entitas']) ?></td>
            <td class="px-5 py-3.5 text-slate-600"><?= e($r['deskripsi'] ?: '—') ?></td>
            <td class="px-5 py-3.5"><span class="font-mono text-xs text-slate-400"><?= e($r['ip'] ?: '-') ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
      <span class="text-xs text-slate-400">Halaman <?= $page ?> dari <?= $pages ?></span>
      <div class="flex items-center gap-2">
        <?php if ($page > 1): ?>
          <a href="?<?= qs(['page'=>$page-1]) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-100 transition"><i data-lucide="chevron-left" class="w-4 h-4"></i> Sebelumnya</a>
        <?php endif; ?>
        <?php if ($page < $pages): ?>
          <a href="?<?= qs(['page'=>$page+1]) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-100 transition">Berikutnya <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
