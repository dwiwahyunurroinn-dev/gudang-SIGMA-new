<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT id_barang, kode_barcode, nama_barang, merk, harga
                           FROM barang WHERE nama_barang LIKE ? OR kode_barcode LIKE ? ORDER BY nama_barang");
    $stmt->execute(["%$q%", "%$q%"]);
    $barang = $stmt->fetchAll();
} else {
    $barang = $pdo->query("SELECT id_barang, kode_barcode, nama_barang, merk, harga
                           FROM barang ORDER BY nama_barang")->fetchAll();
}

$page_title = 'Cetak Label';
$active     = 'label';
require __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
  #sidebar, #overlay, header, .no-print { display: none !important; }
  body { background: #fff !important; }
  main { padding: 0 !important; }
  @page { margin: 1cm; }
}
.label-card {
  border: 1px dashed #cbd5e1; border-radius: 8px; padding: 8px 6px;
  display: flex; flex-direction: column; align-items: center; text-align: center; background: #fff;
}
.label-card .nm { font-size: 11px; font-weight: 600; color: #1e293b; line-height: 1.2; margin-bottom: 4px; min-height: 26px; }
.label-card .pr { font-size: 11px; font-weight: 700; color: #0f172a; margin-top: 2px; }
.label-card svg { max-width: 100%; height: auto; }
</style>

<!-- TOOLBAR -->
<div class="no-print bg-white border border-slate-200 rounded-2xl p-5 mb-5">
  <div class="flex flex-col lg:flex-row lg:items-end gap-3">
    <form method="GET" class="relative flex-1 max-w-sm">
      <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari barang..."
        class="w-full rounded-xl bg-slate-50 border border-slate-200 pl-10 pr-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
    </form>
    <div>
      <label class="text-xs font-medium text-slate-500">Salinan / barang</label>
      <input type="number" id="copies" min="1" value="1"
        class="mt-1 block w-28 rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
    </div>
    <div class="flex gap-2 lg:ml-auto">
      <button type="button" onclick="toggleAll()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition"><i data-lucide="check-square" class="w-4 h-4"></i> Pilih Semua</button>
      <button type="button" onclick="buatLabel()" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/25"><i data-lucide="tag" class="w-4 h-4"></i> Buat Label</button>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition"><i data-lucide="printer" class="w-4 h-4"></i> Cetak</button>
    </div>
  </div>
</div>

<!-- PILIH BARANG -->
<div class="no-print bg-white border border-slate-200 rounded-2xl p-5 mb-6">
  <h2 class="font-semibold text-slate-900 mb-1">Pilih Barang</h2>
  <p class="text-sm text-slate-400 mb-4">Centang barang yang ingin dibuatkan label.</p>
  <?php if (!$barang): ?>
    <p class="text-sm text-slate-400 py-6 text-center">Belum ada barang. Tambahkan dulu di menu Data Barang.</p>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <?php foreach ($barang as $b): ?>
        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-300 cursor-pointer transition">
          <input type="checkbox" class="bk w-4 h-4 accent-blue-600"
            data-kode="<?= e($b['kode_barcode']) ?>"
            data-nama="<?= e($b['nama_barang']) ?>"
            data-harga="<?= (int)$b['harga'] ?>">
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-800 truncate"><?= e($b['nama_barang']) ?></p>
            <p class="text-xs text-slate-400 font-mono truncate"><?= e($b['kode_barcode']) ?></p>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- LEMBAR LABEL (area cetak) -->
<div id="labelSheet" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
  <p id="labelEmpty" class="col-span-full text-center text-slate-400 text-sm py-12">Pilih barang lalu klik <b>"Buat Label"</b> untuk menampilkan pratinjau.</p>
</div>

<?php
ob_start(); ?>
<script src="<?= BASE_URL ?>/assets/js/jsbarcode.min.js"></script>
<script>
function toggleAll(){
  const boxes = document.querySelectorAll('.bk');
  const semua = [...boxes].every(b => b.checked);
  boxes.forEach(b => b.checked = !semua);
}

function buatLabel(){
  const copies = Math.max(1, parseInt(document.getElementById('copies').value) || 1);
  const dipilih = [...document.querySelectorAll('.bk')].filter(b => b.checked);
  if (dipilih.length === 0){ alert('Pilih minimal satu barang dulu.'); return; }

  const sheet = document.getElementById('labelSheet');
  sheet.innerHTML = '';

  dipilih.forEach(b => {
    for (let i = 0; i < copies; i++){
      const card = document.createElement('div');
      card.className = 'label-card';

      const nm = document.createElement('p');
      nm.className = 'nm'; nm.textContent = b.dataset.nama;

      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

      const pr = document.createElement('p');
      pr.className = 'pr'; pr.textContent = 'Rp ' + parseInt(b.dataset.harga).toLocaleString('id-ID');

      card.appendChild(nm); card.appendChild(svg); card.appendChild(pr);
      sheet.appendChild(card);

      JsBarcode(svg, b.dataset.kode, {
        format: 'CODE128', width: 2.2, height: 70, fontSize: 14, margin: 4, displayValue: true
      });
    }
  });
}
</script>
<?php
$extra_js = ob_get_clean();
require __DIR__ . '/../includes/footer.php';
?>