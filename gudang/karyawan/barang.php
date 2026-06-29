<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login(); // admin & karyawan boleh

// ===== Helper upload gambar =====
// return: null = tidak ada file | false = error (isi $err) | string = nama file
function uploadGambar($file, $barcode, &$err) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { $err = 'Gagal mengunggah gambar.'; return false; }

    $allowed = ['jpg','jpeg','png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) { $err = 'Format gambar harus JPG, JPEG, atau PNG.'; return false; }
    if ($file['size'] > 2 * 1024 * 1024) { $err = 'Ukuran gambar maksimal 2MB.'; return false; }
    if (@getimagesize($file['tmp_name']) === false) { $err = 'File yang diunggah bukan gambar valid.'; return false; }

    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $barcode);
    $namaFile = 'B_' . $safe . '.' . $ext;
    $dir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }   // buat folder bila belum ada
    if (!is_writable($dir)) { $err = 'Folder assets/uploads/ tidak bisa ditulis. Periksa izin folder.'; return false; }
    if (!move_uploaded_file($file['tmp_name'], $dir . $namaFile)) { $err = 'Gagal menyimpan gambar ke folder uploads.'; return false; }
    return $namaFile;
}

// ============ PROSES AKSI (POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $barcode = trim($_POST['kode_barcode'] ?? '');
        $nama    = trim($_POST['nama_barang'] ?? '');
        $merk    = trim($_POST['merk'] ?? '');
        $idKat   = ($_POST['id_kategori'] ?? '') !== '' ? (int)$_POST['id_kategori'] : null;
        $satuan  = trim($_POST['satuan'] ?? '') ?: 'Pcs';
        $harga   = max(0, (int)($_POST['harga'] ?? 0));

        if ($barcode === '' || $nama === '') {
            flash('error', 'Kode barcode dan nama barang wajib diisi.');
        } else {
            // Deteksi duplikat barcode (sisi server — wajib)
            $cek = $pdo->prepare("SELECT id_barang FROM barang WHERE kode_barcode = ? AND id_barang <> ?");
            $cek->execute([$barcode, $id]);
            if ($cek->fetch()) {
                flash('error', "Barcode \"$barcode\" sudah terdaftar.");
            } else {
                $err = '';
                $namaFile = uploadGambar($_FILES['gambar'] ?? null, $barcode, $err);
                if ($namaFile === false) {
                    flash('error', $err);
                } elseif ($aksi === 'tambah') {
                    $pdo->prepare("INSERT INTO barang
                        (kode_barcode, nama_barang, merk, id_kategori, satuan, harga, gambar, stok_sekarang)
                        VALUES (?,?,?,?,?,?,?,0)")
                        ->execute([$barcode, $nama, $merk ?: null, $idKat, $satuan, $harga, $namaFile]);
                    flash('success', "Barang \"$nama\" berhasil ditambahkan.");
                } else { // edit
                    $oldStmt = $pdo->prepare("SELECT gambar FROM barang WHERE id_barang = ?");
                    $oldStmt->execute([$id]);
                    $oldGambar = $oldStmt->fetchColumn();

                    if ($namaFile !== null) { // ada gambar baru
                        if ($oldGambar && $oldGambar !== $namaFile) {
                            $fp = __DIR__ . '/../assets/uploads/' . $oldGambar;
                            if (is_file($fp)) @unlink($fp);
                        }
                        $pdo->prepare("UPDATE barang SET kode_barcode=?, nama_barang=?, merk=?, id_kategori=?, satuan=?, harga=?, gambar=? WHERE id_barang=?")
                            ->execute([$barcode, $nama, $merk ?: null, $idKat, $satuan, $harga, $namaFile, $id]);
                    } else {
                        $pdo->prepare("UPDATE barang SET kode_barcode=?, nama_barang=?, merk=?, id_kategori=?, satuan=?, harga=? WHERE id_barang=?")
                            ->execute([$barcode, $nama, $merk ?: null, $idKat, $satuan, $harga, $id]);
                    }
                    flash('success', "Barang \"$nama\" berhasil diperbarui.");
                }
            }
        }
    }

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $g = $pdo->prepare("SELECT gambar FROM barang WHERE id_barang = ?");
        $g->execute([$id]);
        $gambar = $g->fetchColumn();
        // Hapus barang (transaksi terkait ikut terhapus via ON DELETE CASCADE)
        $pdo->prepare("DELETE FROM barang WHERE id_barang = ?")->execute([$id]);
        if ($gambar) { // bersihkan file gambar
            $fp = __DIR__ . '/../assets/uploads/' . $gambar;
            if (is_file($fp)) @unlink($fp);
        }
        flash('success', 'Barang berhasil dihapus.');
    }

    header("Location: " . BASE_URL . "/karyawan/barang.php");
    exit;
}

// ============ AMBIL DATA ============
$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT b.*, k.nama_kategori FROM barang b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori";
if ($q !== '') {
    $sql .= " WHERE b.nama_barang LIKE ? OR b.kode_barcode LIKE ? OR b.merk LIKE ?";
    $sql .= " ORDER BY b.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    $barang = $stmt->fetchAll();
} else {
    $sql .= " ORDER BY b.created_at DESC";
    $barang = $pdo->query($sql)->fetchAll();
}

$page_title = 'Data Barang';
$active     = 'barang';
require __DIR__ . '/../includes/header.php';
?>

<!-- TOOLBAR -->
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
  <form method="GET" class="relative flex-1 max-w-sm">
    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, barcode, atau merk..."
      class="w-full rounded-xl bg-white border border-slate-200 pl-10 pr-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
  </form>
  <button onclick="openTambah()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">
    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Barang
  </button>
</div>

<!-- TABEL -->
<div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm table-pro">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
          <th class="px-5 py-3 font-medium">Produk</th>
          <th class="px-5 py-3 font-medium">Barcode</th>
          <th class="px-5 py-3 font-medium">Kategori</th>
          <th class="px-5 py-3 font-medium">Harga</th>
          <th class="px-5 py-3 font-medium">Stok</th>
          <th class="px-5 py-3 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$barang): ?>
          <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">
            <i data-lucide="package-open" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
            Belum ada barang. Klik <b>Tambah Barang</b> untuk memulai.
          </td></tr>
        <?php else: foreach ($barang as $b):
          $stok = (int)$b['stok_sekarang'];
          $stokCls = $stok === 0 ? 'bg-rose-100 text-rose-700' : ($stok <= 5 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
          $data = json_encode([
            'id'=>(int)$b['id_barang'], 'kode_barcode'=>$b['kode_barcode'], 'nama_barang'=>$b['nama_barang'],
            'merk'=>$b['merk'], 'id_kategori'=>$b['id_kategori'], 'satuan'=>$b['satuan'],
            'harga'=>(int)$b['harga'], 'gambar'=>$b['gambar']
          ]); ?>
          <tr class="hover:bg-slate-50/60 transition">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <?php if ($b['gambar']): ?>
                  <img src="<?= BASE_URL ?>/assets/uploads/<?= e($b['gambar']) ?>" class="w-11 h-11 rounded-lg object-cover border border-slate-200">
                <?php else: ?>
                  <span class="grid place-items-center w-11 h-11 rounded-lg bg-slate-100 text-slate-400"><i data-lucide="image" class="w-5 h-5"></i></span>
                <?php endif; ?>
                <div>
                  <p class="font-medium text-slate-800"><?= e($b['nama_barang']) ?></p>
                  <p class="text-xs text-slate-400"><?= e($b['merk'] ?: '-') ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= e($b['kode_barcode']) ?></span></td>
            <td class="px-5 py-3.5 text-slate-600"><?= e($b['nama_kategori'] ?: '-') ?></td>
            <td class="px-5 py-3.5 text-slate-700">Rp <?= number_format((int)$b['harga'], 0, ',', '.') ?></td>
            <td class="px-5 py-3.5"><span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold <?= $stokCls ?>"><?= $stok ?> <?= e($b['satuan']) ?></span></td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-2">
                <button onclick='openEdit(<?= e($data) ?>)' class="grid place-items-center w-8 h-8 rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                <form method="POST" data-confirm="Riwayat transaksi barang ini juga akan ikut terhapus." data-confirm-title="Hapus barang?" data-confirm-ok="Ya, hapus" data-confirm-variant="danger" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int)$b['id_barang'] ?>">
                  <button type="submit" class="grid place-items-center w-8 h-8 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== MODAL TAMBAH/EDIT ===== -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl my-8">
    <form method="POST" enctype="multipart/form-data" class="p-6">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" id="m-aksi" value="tambah">
      <input type="hidden" name="id"   id="m-id"   value="">
      <div class="flex items-center justify-between mb-5">
        <h3 id="m-title" class="text-lg font-bold text-slate-900">Tambah Barang</h3>
        <button type="button" onclick="closeModal()" class="grid place-items-center w-8 h-8 rounded-lg hover:bg-slate-100 transition"><i data-lucide="x" class="w-4 h-4"></i></button>
      </div>

      <div class="space-y-4">
        <!-- Barcode + Scan -->
        <div>
          <label class="text-sm font-medium text-slate-700">Kode Barcode</label>
          <div class="flex gap-2 mt-1.5">
            <input type="text" name="kode_barcode" id="m-barcode" required onblur="cekBarcode(this.value)" autocomplete="off"
              class="flex-1 rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm font-mono outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition" placeholder="Ketik / scan barcode">
            <button type="button" onclick="toggleScan()" class="inline-flex items-center gap-1.5 px-4 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
              <i data-lucide="camera" class="w-4 h-4"></i> Scan
            </button>
          </div>
          <p id="bc-warn" class="hidden text-xs text-rose-600 mt-1.5"></p>
          <div id="scanWrap" class="hidden mt-3">
            <div id="reader" class="w-full rounded-xl overflow-hidden border border-slate-200"></div>
            <button type="button" onclick="stopScan()" class="mt-2 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600"><i data-lucide="x" class="w-3.5 h-3.5"></i> Tutup kamera</button>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-slate-700">Nama Barang</label>
          <input type="text" name="nama_barang" id="m-nama" required
            class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition" placeholder="Contoh: Laptop Asus ROG Strix">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Merk</label>
            <input type="text" name="merk" id="m-merk"
              class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition" placeholder="Asus">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Kategori</label>
            <select name="id_kategori" id="m-kategori"
              class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
              <option value="">— Pilih —</option>
              <?php foreach ($kategori as $k): ?>
                <option value="<?= (int)$k['id_kategori'] ?>"><?= e($k['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Harga (Rp)</label>
            <input type="number" name="harga" id="m-harga" min="0" value="0"
              class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Satuan</label>
            <input type="text" name="satuan" id="m-satuan" value="Pcs"
              class="mt-1.5 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition">
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-slate-700">Gambar Produk <span class="text-slate-400 font-normal">(JPG/PNG, maks 2MB)</span></label>
          <input type="file" name="gambar" id="m-gambar" accept=".jpg,.jpeg,.png"
            class="mt-1.5 w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100 file:cursor-pointer">
          <p id="m-imgnow" class="hidden text-xs text-slate-500 mt-1.5"></p>
        </div>

        <p class="text-xs text-slate-400 flex items-start gap-1.5">
          <i data-lucide="info" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
          Stok awal otomatis 0. Stok hanya berubah melalui transaksi Barang Masuk/Keluar.
        </p>
      </div>

      <div class="flex gap-3 mt-6">
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition">Batal</button>
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
ob_start(); ?>
<script src="<?= BASE_URL ?>/assets/js/html5-qrcode.min.js"></script>
<script>
function m(id){ return document.getElementById('m-' + id); }
function showModal(){ const x=document.getElementById('modal'); x.classList.remove('hidden'); x.classList.add('flex'); }
function closeModal(){ stopScan(); const x=document.getElementById('modal'); x.classList.add('hidden'); x.classList.remove('flex'); document.getElementById('bc-warn').classList.add('hidden'); }

function openTambah(){
  m('title').textContent='Tambah Barang'; m('aksi').value='tambah'; m('id').value='';
  m('barcode').value=''; m('nama').value=''; m('merk').value='';
  m('kategori').value=''; m('harga').value='0'; m('satuan').value='Pcs'; m('gambar').value='';
  document.getElementById('m-imgnow').classList.add('hidden');
  showModal();
}
function openEdit(d){
  m('title').textContent='Edit Barang'; m('aksi').value='edit'; m('id').value=d.id;
  m('barcode').value=d.kode_barcode; m('nama').value=d.nama_barang; m('merk').value=d.merk||'';
  m('kategori').value=d.id_kategori||''; m('harga').value=d.harga; m('satuan').value=d.satuan||'Pcs'; m('gambar').value='';
  const now=document.getElementById('m-imgnow');
  if(d.gambar){ now.textContent='Gambar saat ini: '+d.gambar+' (unggah baru untuk mengganti)'; now.classList.remove('hidden'); }
  else now.classList.add('hidden');
  showModal();
}

// ===== Cek duplikat barcode (AJAX) =====
function cekBarcode(kode){
  const warn=document.getElementById('bc-warn');
  if(!kode){ warn.classList.add('hidden'); return; }
  const id=m('id').value||0;
  fetch('<?= BASE_URL ?>/karyawan/ajax/cek_barcode.php?kode='+encodeURIComponent(kode)+'&id='+id)
    .then(r=>r.json()).then(d=>{
      if(d.ada){ warn.textContent='⚠️ Barcode sudah terdaftar untuk: '+d.nama; warn.classList.remove('hidden'); }
      else warn.classList.add('hidden');
    }).catch(()=>{});
}

// ===== Scanner kamera =====
let html5Qr=null;
function toggleScan(){
  const wrap=document.getElementById('scanWrap');
  if(wrap.classList.contains('hidden')) startScan(); else stopScan();
}
function startScan(){
  document.getElementById('scanWrap').classList.remove('hidden');
html5Qr = new Html5Qrcode("reader", {
    formatsToSupport: [
      Html5QrcodeSupportedFormats.CODE_128,
      Html5QrcodeSupportedFormats.CODE_39,
      Html5QrcodeSupportedFormats.EAN_13,
      Html5QrcodeSupportedFormats.EAN_8,
      Html5QrcodeSupportedFormats.UPC_A,
      Html5QrcodeSupportedFormats.UPC_E,
      Html5QrcodeSupportedFormats.QR_CODE
    ],
    experimentalFeatures: { useBarCodeDetectorIfSupported: true }
  });
  html5Qr.start({ facingMode:"environment" }, { fps:10, qrbox:{width:300,height:200} },
    (text)=>{ m('barcode').value=text; cekBarcode(text); stopScan(); },
    ()=>{}
  ).catch(e=>{ toast('Tidak dapat mengakses kamera: '+e+'. Pastikan izin kamera diberikan.', 'error'); document.getElementById('scanWrap').classList.add('hidden'); });
}
function stopScan(){
  if(html5Qr){ html5Qr.stop().then(()=>html5Qr.clear()).catch(()=>{}); html5Qr=null; }
  document.getElementById('scanWrap').classList.add('hidden');
}
document.getElementById('modal').addEventListener('click', e=>{ if(e.target.id==='modal') closeModal(); });
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>