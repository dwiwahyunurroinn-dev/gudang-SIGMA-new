<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

// ============ PROSES (POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $aksi = $_POST['aksi'] ?? '';

    // ---- TAMBAH (Barang Masuk) ----
    if ($aksi === 'tambah') {
        $idBarang = (int)($_POST['id_barang'] ?? 0);
        $jumlah   = (int)($_POST['jumlah'] ?? 0);
        $tanggal  = $_POST['tanggal'] ?? date('Y-m-d');
        $ket      = trim($_POST['keterangan'] ?? '');

        if ($idBarang <= 0) {
            flash('error', 'Silakan pilih barang terlebih dahulu.');
        } elseif ($jumlah <= 0) {
            flash('error', 'Jumlah masuk harus lebih dari 0.');
        } else {
            $cek = $pdo->prepare("SELECT id_barang FROM barang WHERE id_barang = ?");
            $cek->execute([$idBarang]);
            if (!$cek->fetch()) {
                flash('error', 'Barang tidak ditemukan.');
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare("INSERT INTO stok_masuk (id_barang, jumlah, tanggal, keterangan, user_id)
                                   VALUES (?,?,?,?,?)")
                        ->execute([$idBarang, $jumlah, $tanggal, $ket ?: null, current_user()['id']]);
                    $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang + ? WHERE id_barang = ?")
                        ->execute([$jumlah, $idBarang]);
                    $pdo->commit();
                    audit('tambah', 'stok_masuk', "Stok masuk +$jumlah unit (barang ID $idBarang)");
                    flash('success', "Barang masuk berhasil dicatat (+$jumlah unit).");
                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    flash('error', 'Gagal mencatat transaksi. Coba lagi.');
                }
            }
        }
    }

    // ---- BATAL (Void) ----
    if ($aksi === 'batal') {
        $id = (int)($_POST['id'] ?? 0);
        $r = $pdo->prepare("SELECT id_barang, jumlah FROM stok_masuk WHERE id = ?");
        $r->execute([$id]);
        $row = $r->fetch();

        if (!$row) {
            flash('error', 'Transaksi tidak ditemukan.');
        } else {
            try {
                $pdo->beginTransaction();
                $s = $pdo->prepare("SELECT stok_sekarang FROM barang WHERE id_barang = ? FOR UPDATE");
                $s->execute([$row['id_barang']]);
                $stok = (int)$s->fetchColumn();

                if ($stok < (int)$row['jumlah']) {
                    $pdo->rollBack();
                    flash('error', "Tidak bisa dibatalkan: stok sekarang ($stok) lebih kecil dari jumlah transaksi. Barang mungkin sudah keluar.");
                } else {
                    $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang - ? WHERE id_barang = ?")
                        ->execute([$row['jumlah'], $row['id_barang']]);
                    $pdo->prepare("DELETE FROM stok_masuk WHERE id = ?")->execute([$id]);
                    $pdo->commit();
                    audit('void', 'stok_masuk', "Batal stok masuk #$id (−{$row['jumlah']} unit, barang ID {$row['id_barang']})");
                    flash('success', 'Transaksi dibatalkan & stok disesuaikan.');
                }
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', 'Gagal membatalkan transaksi.');
            }
        }
    }

    header("Location: " . BASE_URL . "/karyawan/stok_masuk.php");
    exit;
}

// ============ AMBIL DATA ============
$barangList = $pdo->query("SELECT id_barang, nama_barang, kode_barcode, satuan, stok_sekarang
                           FROM barang ORDER BY nama_barang")->fetchAll();

$riwayat = $pdo->query("
  SELECT m.*, b.nama_barang, b.satuan, u.nama AS user_nama
  FROM stok_masuk m
  JOIN barang b ON b.id_barang = m.id_barang
  LEFT JOIN users u ON u.id = m.user_id
  ORDER BY m.created_at DESC LIMIT 15")->fetchAll();

$page_title = 'Stok Masuk';
$active     = 'masuk';
require __DIR__ . '/../includes/header.php';

$inp = "w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:bg-white transition";
?>

<div class="grid lg:grid-cols-3 gap-6">

  <!-- ===== FORM ===== -->
  <div class="lg:col-span-1">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
      <div class="flex items-center gap-2.5 mb-5">
        <span class="grid place-items-center w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="arrow-down-to-line" class="w-5 h-5"></i></span>
        <h2 class="font-semibold text-slate-900">Catat Barang Masuk</h2>
      </div>

      <form method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="tambah">

        <div>
          <label class="text-sm font-medium text-slate-700">Cari / Scan Barcode</label>
          <div class="flex gap-2 mt-1.5">
            <input type="text" id="f-barcode" placeholder="Scan atau ketik, lalu Enter" autocomplete="off"
              onkeydown="if(event.key==='Enter'){event.preventDefault();pilihDariBarcode(this.value);}"
              class="<?= $inp ?> font-mono">
            <button type="button" onclick="toggleScan()" class="inline-flex items-center px-3.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition"><i data-lucide="camera" class="w-4 h-4"></i></button>
          </div>
          <div id="scanWrap" class="hidden mt-3">
            <div id="reader" class="w-full rounded-xl overflow-hidden border border-slate-200"></div>
            <button type="button" onclick="stopScan()" class="mt-2 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600"><i data-lucide="x" class="w-3.5 h-3.5"></i> Tutup kamera</button>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-slate-700">Pilih Barang</label>
          <select name="id_barang" id="f-barang" required onchange="updateStok()" class="<?= $inp ?> mt-1.5">
            <option value="">— Pilih barang —</option>
            <?php foreach ($barangList as $b): ?>
              <option value="<?= (int)$b['id_barang'] ?>" data-kode="<?= e($b['kode_barcode']) ?>" data-stok="<?= (int)$b['stok_sekarang'] ?>" data-satuan="<?= e($b['satuan']) ?>">
                <?= e($b['nama_barang']) ?> · <?= e($b['kode_barcode']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p id="f-stoknow" class="hidden text-xs text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2 mt-2"></p>
        </div>

        <div>
          <label class="text-sm font-medium text-slate-700">Jumlah Masuk</label>
          <input type="number" name="jumlah" min="1" value="1" required class="<?= $inp ?> mt-1.5">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Tanggal</label>
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="<?= $inp ?> mt-1.5">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
          <input type="text" name="keterangan" placeholder="Mis. Pembelian dari supplier A" class="<?= $inp ?> mt-1.5">
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/25">
          <i data-lucide="check" class="w-4 h-4"></i> Simpan Transaksi
        </button>
      </form>
    </div>
  </div>

  <!-- ===== RIWAYAT ===== -->
  <div class="lg:col-span-2">
    <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
        <i data-lucide="history" class="w-5 h-5 text-slate-400"></i>
        <h2 class="font-semibold text-slate-900">Riwayat Barang Masuk Terbaru</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm table-pro">
          <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
              <th class="px-5 py-3 font-medium">Tanggal</th>
              <th class="px-5 py-3 font-medium">Barang</th>
              <th class="px-5 py-3 font-medium">Jumlah</th>
              <th class="px-5 py-3 font-medium">Oleh</th>
              <th class="px-5 py-3 font-medium text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (!$riwayat): ?>
              <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada transaksi masuk.</td></tr>
            <?php else: foreach ($riwayat as $r): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                <td class="px-5 py-3.5">
                  <p class="font-medium text-slate-800"><?= e($r['nama_barang']) ?></p>
                  <?php if ($r['keterangan']): ?><p class="text-xs text-slate-400"><?= e($r['keterangan']) ?></p><?php endif; ?>
                </td>
                <td class="px-5 py-3.5"><span class="font-bold text-emerald-600">+<?= (int)$r['jumlah'] ?></span> <span class="text-slate-400 text-xs"><?= e($r['satuan']) ?></span></td>
                <td class="px-5 py-3.5 text-slate-500"><?= e($r['user_nama'] ?: '-') ?></td>
                <td class="px-5 py-3.5">
                  <form method="POST" data-confirm="Stok barang akan dikurangi kembali sesuai jumlah transaksi ini." data-confirm-title="Batalkan transaksi?" data-confirm-ok="Ya, batalkan" data-confirm-variant="danger" class="text-right">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="batal">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600 transition"><i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Batal</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
ob_start(); ?>
<script src="<?= BASE_URL ?>/assets/js/html5-qrcode.min.js"></script>
<script>
const sel = document.getElementById('f-barang');

function updateStok(){
  const o = sel.options[sel.selectedIndex];
  const box = document.getElementById('f-stoknow');
  if (o && o.value){
    box.textContent = 'Stok saat ini: ' + o.dataset.stok + ' ' + o.dataset.satuan;
    box.classList.remove('hidden');
  } else box.classList.add('hidden');
}

function pilihDariBarcode(kode){
  kode = (kode || '').trim();
  if (!kode) return;
  for (const o of sel.options){
    if (o.dataset.kode === kode){ sel.value = o.value; updateStok(); return; }
  }
  toast('Barang dengan barcode "' + kode + '" belum terdaftar. Tambahkan dulu di menu Data Barang.', 'warning');
}

// ===== Scanner kamera =====
let html5Qr = null;
function toggleScan(){
  const w = document.getElementById('scanWrap');
  if (w.classList.contains('hidden')) startScan(); else stopScan();
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
    (text)=>{ document.getElementById('f-barcode').value = text; pilihDariBarcode(text); stopScan(); },
    ()=>{}
  ).catch(e=>{ toast('Tidak dapat mengakses kamera: ' + e, 'error'); document.getElementById('scanWrap').classList.add('hidden'); });
}
function stopScan(){
  if (html5Qr){ html5Qr.stop().then(()=>html5Qr.clear()).catch(()=>{}); html5Qr=null; }
  document.getElementById('scanWrap').classList.add('hidden');
}
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>