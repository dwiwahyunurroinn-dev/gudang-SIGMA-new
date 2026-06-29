<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

// ============ PROSES (POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $aksi = $_POST['aksi'] ?? '';

    // ---- PROSES KERANJANG → INVOICE ----
    if ($aksi === 'proses') {
        $pelanggan = trim($_POST['pelanggan'] ?? '');
        $tanggal   = $_POST['tanggal'] ?: date('Y-m-d');
        $ket       = trim($_POST['keterangan'] ?? '');
        $keranjang = json_decode($_POST['keranjang'] ?? '[]', true);

        if (!is_array($keranjang) || count($keranjang) === 0) {
            flash('error', 'Keranjang masih kosong.');
        } else {
            try {
                $pdo->beginTransaction();

                // Generate nomor invoice (urut harian)
                $tgl    = date('Ymd');
                $prefix = "INV-$tgl-";
                $c = $pdo->prepare("SELECT COUNT(DISTINCT nomor_invoice) FROM stok_keluar WHERE nomor_invoice LIKE ?");
                $c->execute([$prefix . '%']);
                $nomor = $prefix . str_pad(((int)$c->fetchColumn()) + 1, 4, '0', STR_PAD_LEFT);

                $cekStok    = $pdo->prepare("SELECT nama_barang, stok_sekarang, harga FROM barang WHERE id_barang = ? FOR UPDATE");
                $insert     = $pdo->prepare("INSERT INTO stok_keluar (id_barang, jumlah, harga_satuan, nomor_invoice, pelanggan, tanggal, keterangan, user_id) VALUES (?,?,?,?,?,?,?,?)");
                $kurangStok = $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang - ? WHERE id_barang = ?");

                foreach ($keranjang as $item) {
                    $idB = (int)($item['id'] ?? 0);
                    $jml = (int)($item['jumlah'] ?? 0);
                    if ($idB <= 0 || $jml <= 0) throw new Exception('Data keranjang tidak valid.');

                    $cekStok->execute([$idB]);
                    $bar = $cekStok->fetch();
                    if (!$bar) throw new Exception('Salah satu barang tidak ditemukan.');
                    if ((int)$bar['stok_sekarang'] < $jml) {
                        throw new Exception('Stok "' . $bar['nama_barang'] . '" tidak mencukupi (tersisa ' . $bar['stok_sekarang'] . ').');
                    }

                    $insert->execute([$idB, $jml, (int)$bar['harga'], $nomor, $pelanggan ?: null, $tanggal, $ket ?: null, current_user()['id']]);
                    $kurangStok->execute([$jml, $idB]);
                }

                $pdo->commit();
                flash('success', "Transaksi berhasil dibuat. No. Invoice: $nomor");
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', $ex->getMessage());
            }
        }
    }

    // ---- BATALKAN INVOICE ----
    if ($aksi === 'batal_invoice') {
        $nomor = trim($_POST['nomor_invoice'] ?? '');
        if ($nomor === '') {
            flash('error', 'Invoice tidak valid.');
        } else {
            try {
                $pdo->beginTransaction();
                $rows = $pdo->prepare("SELECT id_barang, jumlah FROM stok_keluar WHERE nomor_invoice = ?");
                $rows->execute([$nomor]);
                $items = $rows->fetchAll();
                if (!$items) {
                    $pdo->rollBack();
                    flash('error', 'Invoice tidak ditemukan.');
                } else {
                    $up = $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang + ? WHERE id_barang = ?");
                    foreach ($items as $it) $up->execute([$it['jumlah'], $it['id_barang']]);
                    $pdo->prepare("DELETE FROM stok_keluar WHERE nomor_invoice = ?")->execute([$nomor]);
                    $pdo->commit();
                    flash('success', "Invoice $nomor dibatalkan & stok dikembalikan.");
                }
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', 'Gagal membatalkan invoice.');
            }
        }
    }

    header("Location: " . BASE_URL . "/karyawan/stok_keluar.php");
    exit;
}

// ============ AMBIL DATA ============
$barangList = $pdo->query("SELECT id_barang, nama_barang, kode_barcode, satuan, harga, stok_sekarang
                           FROM barang WHERE stok_sekarang > 0 ORDER BY nama_barang")->fetchAll();

$riwayat = $pdo->query("
  SELECT nomor_invoice, pelanggan, tanggal, MAX(created_at) AS created_at,
         COUNT(*) AS jml_item, SUM(jumlah) AS total_qty, SUM(jumlah * harga_satuan) AS total_nilai
  FROM stok_keluar
  GROUP BY nomor_invoice, pelanggan, tanggal
  ORDER BY created_at DESC LIMIT 15")->fetchAll();

$page_title = 'Stok Keluar';
$active     = 'keluar';
require __DIR__ . '/../includes/header.php';

$inp = "w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition";
?>

<form method="POST">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="proses">
  <input type="hidden" name="keranjang" id="keranjang" value="[]">

  <div class="grid lg:grid-cols-5 gap-6">

    <!-- ===== KIRI: input item + detail ===== -->
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center gap-2.5 mb-5">
          <span class="grid place-items-center w-10 h-10 rounded-xl bg-rose-50 text-rose-600"><i data-lucide="arrow-up-from-line" class="w-5 h-5"></i></span>
          <h2 class="font-semibold text-slate-900">Tambah Barang ke Keranjang</h2>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Cari / Scan Barcode</label>
            <div class="flex gap-2 mt-1.5">
              <input type="text" id="f-barcode" placeholder="Scan / ketik, lalu Enter" autocomplete="off"
                onkeydown="if(event.key==='Enter'){event.preventDefault();pilihDariBarcode(this.value);}"
                class="<?= $inp ?> font-mono">
              <button type="button" onclick="toggleScan()" class="inline-flex items-center px-3.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition"><i data-lucide="camera" class="w-4 h-4"></i></button>
            </div>
            <div id="scanWrap" class="hidden mt-3">
              <div id="reader" class="w-full rounded-xl overflow-hidden border border-slate-200"></div>
              <button type="button" onclick="stopScan()" class="mt-2 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600"><i data-lucide="x" class="w-3.5 h-3.5"></i> Tutup kamera</button>
            </div>
          </div>

          <div>
            <label class="text-sm font-medium text-slate-700">Barang</label>
            <select id="f-barang" onchange="updateStok()" class="<?= $inp ?> mt-1.5">
              <option value="">— Pilih barang —</option>
              <?php foreach ($barangList as $b): ?>
                <option value="<?= (int)$b['id_barang'] ?>"
                  data-kode="<?= e($b['kode_barcode']) ?>" data-nama="<?= e($b['nama_barang']) ?>"
                  data-stok="<?= (int)$b['stok_sekarang'] ?>" data-harga="<?= (int)$b['harga'] ?>" data-satuan="<?= e($b['satuan']) ?>">
                  <?= e($b['nama_barang']) ?> · Stok: <?= (int)$b['stok_sekarang'] ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p id="f-stoknow" class="hidden text-xs text-blue-700 bg-blue-50 rounded-lg px-3 py-2 mt-2"></p>
          </div>

          <div class="flex gap-3">
            <div class="flex-1">
              <label class="text-sm font-medium text-slate-700">Jumlah</label>
              <input type="number" id="f-jumlah" min="1" value="1" class="<?= $inp ?> mt-1.5">
            </div>
            <button type="button" onclick="addToCart()" class="self-end inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
              <i data-lucide="plus" class="w-4 h-4"></i> Tambah
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-semibold text-slate-900 mb-4">Detail Transaksi</h2>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Nama Pelanggan / Tujuan <span class="text-slate-400 font-normal">(opsional)</span></label>
            <input type="text" name="pelanggan" placeholder="Mis. Toko Sinar Jaya" class="<?= $inp ?> mt-1.5">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="<?= $inp ?> mt-1.5">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
            <input type="text" name="keterangan" placeholder="Catatan transaksi" class="<?= $inp ?> mt-1.5">
          </div>
        </div>
      </div>
    </div>

    <!-- ===== KANAN: keranjang ===== -->
    <div class="lg:col-span-3">
      <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
          <i data-lucide="shopping-cart" class="w-5 h-5 text-blue-500"></i>
          <h2 class="font-semibold text-slate-900">Keranjang</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm table-pro">
            <thead class="bg-slate-50 text-slate-500 text-left">
              <tr>
                <th class="px-4 py-3 font-medium">Barang</th>
                <th class="px-4 py-3 font-medium text-center">Qty</th>
                <th class="px-4 py-3 font-medium text-right">Harga</th>
                <th class="px-4 py-3 font-medium text-right">Subtotal</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody id="cart-body"></tbody>
          </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
          <span class="text-sm text-slate-500">Total</span>
          <span id="cart-total" class="text-xl font-bold text-slate-900">Rp 0</span>
        </div>
        <div class="px-5 pb-5">
          <button type="submit" id="btnProses" disabled
            class="w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25 disabled:opacity-40 disabled:cursor-not-allowed">
            <i data-lucide="file-text" class="w-4 h-4"></i> Proses & Buat Invoice
          </button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- ===== RIWAYAT INVOICE ===== -->
<div class="bg-white border border-slate-200 rounded-2xl overflow-hidden mt-6">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
    <i data-lucide="receipt" class="w-5 h-5 text-slate-400"></i>
    <h2 class="font-semibold text-slate-900">Riwayat Invoice Terbaru</h2>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm table-pro">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr>
          <th class="px-5 py-3 font-medium">No. Invoice</th>
          <th class="px-5 py-3 font-medium">Pelanggan</th>
          <th class="px-5 py-3 font-medium">Tanggal</th>
          <th class="px-5 py-3 font-medium">Item</th>
          <th class="px-5 py-3 font-medium text-right">Total</th>
          <th class="px-5 py-3 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$riwayat): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada invoice.</td></tr>
        <?php else: foreach ($riwayat as $r): ?>
          <tr class="hover:bg-slate-50/60 transition">
            <td class="px-5 py-3.5"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-700"><?= e($r['nomor_invoice']) ?></span></td>
            <td class="px-5 py-3.5 text-slate-600"><?= e($r['pelanggan'] ?: '-') ?></td>
            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td class="px-5 py-3.5 text-slate-500"><?= (int)$r['jml_item'] ?> jenis · <?= (int)$r['total_qty'] ?> unit</td>
            <td class="px-5 py-3.5 text-right font-medium text-slate-800">Rp <?= number_format((int)$r['total_nilai'], 0, ',', '.') ?></td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-3">
                <a href="<?= BASE_URL ?>/karyawan/invoice_view.php?inv=<?= urlencode($r['nomor_invoice']) ?>" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><i data-lucide="eye" class="w-3.5 h-3.5"></i> Lihat</a>
                <form method="POST" onsubmit="return confirm('Batalkan invoice ini? Stok semua barang akan dikembalikan.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="batal_invoice">
                  <input type="hidden" name="nomor_invoice" value="<?= e($r['nomor_invoice']) ?>">
                  <button type="submit" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600 transition"><i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Batal</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
ob_start(); ?>
<script src="<?= BASE_URL ?>/assets/js/html5-qrcode.min.js"></script>
<script>
const sel = document.getElementById('f-barang');
let cart = [];

function updateStok(){
  const o = sel.options[sel.selectedIndex];
  const box = document.getElementById('f-stoknow');
  if (o && o.value){ box.textContent = 'Stok tersedia: ' + o.dataset.stok + ' ' + o.dataset.satuan + ' · Harga: Rp ' + parseInt(o.dataset.harga).toLocaleString('id-ID'); box.classList.remove('hidden'); }
  else box.classList.add('hidden');
}
function pilihDariBarcode(kode){
  kode = (kode||'').trim(); if(!kode) return;
  for (const o of sel.options){ if (o.dataset.kode === kode){ sel.value=o.value; updateStok(); return; } }
  alert('Barang dengan barcode "'+kode+'" tidak tersedia (mungkin stok 0 atau belum terdaftar).');
}

function addToCart(){
  const o = sel.options[sel.selectedIndex];
  if (!o || !o.value){ alert('Pilih barang dulu.'); return; }
  const jml = parseInt(document.getElementById('f-jumlah').value)||0;
  if (jml<=0){ alert('Jumlah harus lebih dari 0.'); return; }
  const stok = parseInt(o.dataset.stok);
  const ada = cart.find(c=>c.id===o.value);
  const totalReq = (ada?ada.jumlah:0)+jml;
  if (totalReq>stok){ alert('Stok tidak mencukupi. Tersisa '+stok+'.'); return; }
  if (ada) ada.jumlah = totalReq;
  else cart.push({ id:o.value, nama:o.dataset.nama, kode:o.dataset.kode, harga:parseInt(o.dataset.harga), satuan:o.dataset.satuan, jumlah:jml });
  renderCart();
  sel.value=''; document.getElementById('f-jumlah').value='1'; document.getElementById('f-barcode').value=''; updateStok();
}
function hapusItem(i){ cart.splice(i,1); renderCart(); }

function renderCart(){
  const tb = document.getElementById('cart-body'); tb.innerHTML=''; let total=0;
  if (cart.length===0){ tb.innerHTML='<tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Keranjang masih kosong</td></tr>'; }
  cart.forEach((c,i)=>{
    const sub=c.harga*c.jumlah; total+=sub;
    tb.innerHTML += '<tr>'+
      '<td class="px-4 py-3"><p class="font-medium text-slate-800">'+c.nama+'</p><p class="text-xs text-slate-400 font-mono">'+c.kode+'</p></td>'+
      '<td class="px-4 py-3 text-center text-slate-700">'+c.jumlah+' '+c.satuan+'</td>'+
      '<td class="px-4 py-3 text-right text-slate-600">Rp '+c.harga.toLocaleString('id-ID')+'</td>'+
      '<td class="px-4 py-3 text-right font-medium text-slate-800">Rp '+sub.toLocaleString('id-ID')+'</td>'+
      '<td class="px-4 py-3 text-right"><button type="button" onclick="hapusItem('+i+')" class="text-slate-400 hover:text-rose-600"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td>'+
    '</tr>';
  });
  document.getElementById('cart-total').textContent = 'Rp '+total.toLocaleString('id-ID');
  document.getElementById('keranjang').value = JSON.stringify(cart.map(c=>({id:c.id,jumlah:c.jumlah})));
  document.getElementById('btnProses').disabled = cart.length===0;
  lucide.createIcons();
}
renderCart();

// ===== Scanner =====
let html5Qr=null;
function toggleScan(){ const w=document.getElementById('scanWrap'); if(w.classList.contains('hidden')) startScan(); else stopScan(); }
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
  html5Qr.start({facingMode:"environment"},{fps:10,qrbox:{width:300,height:200}},
    (t)=>{ document.getElementById('f-barcode').value=t; pilihDariBarcode(t); stopScan(); }, ()=>{}
  ).catch(e=>{ alert('Tidak dapat mengakses kamera: '+e); document.getElementById('scanWrap').classList.add('hidden'); });
}
function stopScan(){ if(html5Qr){ html5Qr.stop().then(()=>html5Qr.clear()).catch(()=>{}); html5Qr=null; } document.getElementById('scanWrap').classList.add('hidden'); }
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>