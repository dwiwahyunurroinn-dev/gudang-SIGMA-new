<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

$me = current_user();

// ============ PROSES AKSI (POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $aksi = $_POST['aksi'] ?? '';

    // ---- TAMBAH / EDIT ----
    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = ($_POST['role'] ?? 'karyawan') === 'admin' ? 'admin' : 'karyawan';
        $id       = (int)($_POST['id'] ?? 0);

        if ($nama === '' || $username === '') {
            flash('error', 'Nama dan username wajib diisi.');
        } elseif (strlen($username) < 3) {
            flash('error', 'Username minimal 3 karakter.');
        } elseif ($aksi === 'tambah' && strlen($password) < 6) {
            flash('error', 'Password minimal 6 karakter.');
        } elseif ($aksi === 'edit' && $password !== '' && strlen($password) < 6) {
            flash('error', 'Password baru minimal 6 karakter.');
        } else {
            // Cek username unik (selain dirinya sendiri saat edit)
            $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
            $cek->execute([$username, $id]);
            if ($cek->fetch()) {
                flash('error', "Username '$username' sudah dipakai.");
            } elseif ($aksi === 'tambah') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, password_hash, nama, role) VALUES (?,?,?,?)")
                    ->execute([$username, $hash, $nama, $role]);
                flash('success', "Akun \"$nama\" berhasil ditambahkan.");
            } else { // edit
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET nama=?, username=?, role=?, password_hash=? WHERE id=?")
                        ->execute([$nama, $username, $role, $hash, $id]);
                } else {
                    $pdo->prepare("UPDATE users SET nama=?, username=?, role=? WHERE id=?")
                        ->execute([$nama, $username, $role, $id]);
                }
                flash('success', "Akun \"$nama\" berhasil diperbarui.");
            }
        }
    }

    // ---- HAPUS ----
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$me['id']) {
            flash('error', 'Anda tidak bisa menghapus akun sendiri.');
        } else {
            $t = $pdo->prepare("SELECT role FROM users WHERE id=?");
            $t->execute([$id]);
            $trole = $t->fetchColumn();
            $jmlAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
            if ($trole === 'admin' && $jmlAdmin <= 1) {
                flash('error', 'Tidak bisa menghapus admin terakhir.');
            } else {
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
                flash('success', 'Akun berhasil dihapus.');
            }
        }
    }

    header("Location: " . BASE_URL . "/admin/karyawan.php");
    exit;
}

// ============ AMBIL DATA ============
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE nama LIKE ? OR username LIKE ?
                           ORDER BY (role='admin') DESC, nama ASC");
    $stmt->execute(["%$q%", "%$q%"]);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY (role='admin') DESC, nama ASC")->fetchAll();
}

$page_title = 'Kelola Karyawan';
$active     = 'karyawan';
require __DIR__ . '/../includes/header.php';
?>

<!-- TOOLBAR -->
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
  <form method="GET" class="relative flex-1 max-w-sm">
    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau username..."
      class="w-full rounded-xl bg-white border border-slate-200 pl-10 pr-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
  </form>
  <button onclick="openTambah()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">
    <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah Karyawan
  </button>
</div>

<!-- TABEL -->
<div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm table-pro">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
          <th class="px-5 py-3 font-medium">Nama</th>
          <th class="px-5 py-3 font-medium">Username</th>
          <th class="px-5 py-3 font-medium">Role</th>
          <th class="px-5 py-3 font-medium">Dibuat</th>
          <th class="px-5 py-3 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$users): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Tidak ada data.</td></tr>
        <?php else: foreach ($users as $row):
          $isMe = (int)$row['id'] === (int)$me['id'];
          $data = json_encode(['id'=>(int)$row['id'],'nama'=>$row['nama'],'username'=>$row['username'],'role'=>$row['role']]); ?>
          <tr class="hover:bg-slate-50/60 transition">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <span class="grid place-items-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-bold text-xs"><?= e(strtoupper(mb_substr($row['nama'],0,1))) ?></span>
                <span class="font-medium text-slate-800"><?= e($row['nama']) ?><?php if($isMe): ?> <span class="text-xs text-slate-400">(Anda)</span><?php endif; ?></span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-slate-600"><?= e($row['username']) ?></td>
            <td class="px-5 py-3.5">
              <?php if ($row['role']==='admin'): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-violet-100 text-violet-700 text-xs font-medium"><i data-lucide="shield" class="w-3 h-3"></i> Admin</span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-medium"><i data-lucide="user" class="w-3 h-3"></i> Karyawan</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5 text-slate-500"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-2">
                <button onclick='openEdit(<?= e($data) ?>)' class="grid place-items-center w-8 h-8 rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition" title="Edit">
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                </button>
                <?php if (!$isMe): ?>
                <form method="POST" data-confirm="Tindakan ini tidak bisa dibatalkan." data-confirm-title="Hapus akun?" data-confirm-ok="Ya, hapus" data-confirm-variant="danger" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="grid place-items-center w-8 h-8 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== MODAL TAMBAH/EDIT ===== -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
    <form method="POST" class="p-6">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" id="m-aksi" value="tambah">
      <input type="hidden" name="id"   id="m-id"   value="">
      <div class="flex items-center justify-between mb-5">
        <h3 id="m-title" class="text-lg font-bold text-slate-900">Tambah Karyawan</h3>
        <button type="button" onclick="closeModal()" class="grid place-items-center w-8 h-8 rounded-lg hover:bg-slate-100 transition"><i data-lucide="x" class="w-4 h-4"></i></button>
      </div>
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Nama Lengkap</label>
          <input type="text" name="nama" id="m-nama" required
            class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Username</label>
          <input type="text" name="username" id="m-username" required autocomplete="off"
            class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Password</label>
          <input type="password" name="password" id="m-password" autocomplete="new-password"
            class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
          <p id="m-passhint" class="text-xs text-slate-400 mt-1">Minimal 6 karakter.</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Role</label>
          <select name="role" id="m-role"
            class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
            <option value="karyawan">Karyawan</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="flex gap-3 mt-6">
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition">Batal</button>
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
// Script khusus halaman ini (dikirim ke footer)
ob_start(); ?>
<script>
function openTambah(){
  m('title').textContent = 'Tambah Karyawan';
  m('aksi').value = 'tambah'; m('id').value = '';
  m('nama').value = ''; m('username').value = ''; m('password').value = '';
  m('password').required = true;
  m('passhint').textContent = 'Minimal 6 karakter.';
  m('role').value = 'karyawan';
  showModal();
}
function openEdit(d){
  m('title').textContent = 'Edit Akun';
  m('aksi').value = 'edit'; m('id').value = d.id;
  m('nama').value = d.nama; m('username').value = d.username; m('password').value = '';
  m('password').required = false;
  m('passhint').textContent = 'Kosongkan jika tidak ingin mengubah password.';
  m('role').value = d.role;
  showModal();
}
function m(id){ return document.getElementById('m-' + id); }
function showModal(){ const x=document.getElementById('modal'); x.classList.remove('hidden'); x.classList.add('flex'); }
function closeModal(){ const x=document.getElementById('modal'); x.classList.add('hidden'); x.classList.remove('flex'); }
// tutup modal saat klik area gelap
document.getElementById('modal').addEventListener('click', e => { if (e.target.id === 'modal') closeModal(); });
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>