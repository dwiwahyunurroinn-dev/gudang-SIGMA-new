# 📘 PENJELASAN KODE — SIGMA (Sistem Inventory Gudang)

Dokumen ini menjelaskan **seluruh kode** SIGMA untuk keperluan presentasi.
Bagian akhir memuat **deep-dive baris-per-baris** untuk file yang berat logikanya.

**Stack:** PHP 8 (native) · MySQL (PDO) · Tailwind CSS · Lucide · Chart.js · Html5-QrCode · JsBarcode (semua lokal/offline).

---

## DAFTAR ISI
1. Gambaran besar & alur request
2. Struktur database
3. Penjelasan ringkas tiap file
4. **Deep-dive logika** (file penting)
5. Konsep kunci untuk tanya-jawab

---

## 1. GAMBARAN BESAR

Setiap halaman mengikuti alur yang sama:

```
1. require database.php + security.php   → koneksi DB ($pdo) & cek login
2. Proses POST (jika ada)                → simpan/ubah/hapus data
3. Ambil data (SELECT)                   → siapkan untuk ditampilkan
4. require header.php                     → kerangka (sidebar + topbar)
5. Tampilkan HTML                         → isi halaman
6. require footer.php                     → penutup + JavaScript global
```

**Pemisahan tanggung jawab:**
- `config/`   → koneksi DB & keamanan (otak)
- `includes/` → kerangka tampilan dipakai ulang (header/footer)
- `admin/`    → halaman khusus admin
- `karyawan/` → halaman operasional harian
- `assets/`   → CSS & library JavaScript

---

## 2. STRUKTUR DATABASE

| Tabel | Kolom penting | Keterangan |
|---|---|---|
| `users` | id, username, password_hash, nama, role | Akun (admin/karyawan) |
| `kategori` | id_kategori, nama_kategori | Kategori barang |
| `barang` | id_barang, kode_barcode, nama_barang, merk, id_kategori, satuan, harga, gambar, stok_sekarang | Master produk |
| `stok_masuk` | id, id_barang, jumlah, tanggal, keterangan, user_id, created_at | Catatan barang masuk |
| `stok_keluar` | id, id_barang, jumlah, harga_satuan, nomor_invoice, pelanggan, tanggal, keterangan, user_id, created_at | Catatan barang keluar (per item invoice) |
| `audit_log` | id, user_id, user_nama, aksi, entitas, deskripsi, ip, created_at | Jejak audit (dibuat otomatis) |

**Aturan stok:** `stok_sekarang` HANYA berubah lewat transaksi resmi (Stok Masuk/Keluar), tidak pernah diisi manual → menjaga konsistensi data.

---

## 3. PENJELASAN RINGKAS TIAP FILE

### config/
- **database.php** — definisikan `BASE_URL`, buat objek `$pdo` (PDO) ke MySQL. Mode: error exception + prepared statement asli.
- **security.php** — semua fungsi keamanan: `require_login`, `require_role` (RBAC), `csrf_token/csrf_field/check_csrf` (anti-CSRF), `e()` (anti-XSS), `current_user`, `flash/get_flash`, `audit/audit_ensure` (audit trail).

### includes/
- **header.php** — menu sidebar, topbar (dark mode, lonceng stok menipis, command palette), query jumlah stok ≤5, skrip anti-FOUC tema.
- **footer.php** — JavaScript global: `toast()`, `confirmDialog()`, command palette (Ctrl+K), toggle dark mode, flash→toast.

### Publik
- **index.php** — landing page publik (hero, fitur), animasi scroll-reveal.
- **login.php** — autentikasi: `password_verify` (bcrypt), `session_regenerate_id`, redirect per role, catat audit.
- **logout.php** — kosongkan session, hapus cookie, `session_destroy`.

### karyawan/
- **dashboard.php** — 3 KPI + aksi cepat + transaksi terbaru.
- **barang.php** — CRUD barang, upload gambar tervalidasi, cek barcode unik (server + AJAX), filter stok menipis, tombol riwayat.
- **stok_masuk.php** — barang masuk (transaksi ACID) + void dengan validasi stok.
- **stok_keluar.php** — keranjang → invoice (ACID + row lock `FOR UPDATE`) + void invoice.
- **invoice_view.php** — invoice cetak (`@media print`).
- **label.php** — cetak label barcode (JsBarcode).
- **barang_riwayat.php** — riwayat masuk/keluar per barang (UNION).
- **ajax/** — endpoint JSON: `cek_barcode.php`, `cari_barang.php`, `palette_search.php`.

### admin/
- **dashboard.php** — 4 KPI + 2 grafik Chart.js + panel restock & aktivitas.
- **karyawan.php** — CRUD akun (aturan: tak bisa hapus diri/admin terakhir).
- **laporan.php** — laporan rentang tanggal, KPI, barang terlaris, export CSV, cetak.
- **audit.php** — viewer log aktivitas dengan filter + pagination.

### assets/
- **css/style.css** — gaya kustom (tabel, animasi) + seluruh aturan dark mode (`html.dark ...`).
- **js/** — library lokal: tailwind, lucide, chart, html5-qrcode, jsbarcode.

---

## 4. DEEP-DIVE LOGIKA (file penting)

### 4.1 `config/security.php` — keamanan

**CSRF (Cross-Site Request Forgery):**
```php
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // token acak 64 hex
    }
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        die("Token keamanan tidak valid...");
    }
}
```
- Setiap form menaruh token rahasia (`csrf_field()`). Saat submit, `check_csrf()` membandingkannya dengan token di session pakai `hash_equals` (perbandingan aman dari timing attack).
- **Kalau token tidak cocok → request ditolak.** Ini mencegah situs lain memalsukan POST atas nama user.

**RBAC (Role-Based Access Control):**
```php
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("⛔ Akses ditolak...");
    }
}
```
- Halaman admin memanggil `require_role('admin')` di baris pertama → karyawan langsung ditolak (403).

**Audit trail:**
```php
function audit(string $aksi, string $entitas, string $deskripsi = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        audit_ensure();                       // buat tabel bila belum ada
        $u = current_user();
        $pdo->prepare("INSERT INTO audit_log (...) VALUES (?,?,?,?,?,?)")
            ->execute([$u['id'], $u['nama'], $aksi, $entitas,
                       mb_substr($deskripsi,0,500), $_SERVER['REMOTE_ADDR']]);
    } catch (\Throwable $e) { /* diabaikan: audit tak boleh ganggu operasi utama */ }
}
```
- Mencatat siapa (user), apa (aksi+entitas), kapan (otomatis `created_at`), dari mana (IP).
- Dibungkus `try/catch` → kalau pencatatan gagal, operasi utama (simpan/hapus) **tetap jalan**.

---

### 4.2 `karyawan/stok_keluar.php` — keranjang → invoice ⭐ (logika tersulit)

Ini bagian paling penting untuk dipresentasikan. Alurnya:

```php
if ($aksi === 'proses') {
    $keranjang = json_decode($_POST['keranjang'] ?? '[]', true); // data keranjang dari JS

    if (count($keranjang) === 0) { flash('error','Keranjang kosong.'); }
    else {
        try {
            $pdo->beginTransaction();                 // (1) MULAI TRANSAKSI

            // (2) Generate nomor invoice urut harian: INV-YYYYMMDD-NNNN
            $prefix = "INV-".date('Ymd')."-";
            $c = $pdo->prepare("SELECT COUNT(DISTINCT nomor_invoice) FROM stok_keluar WHERE nomor_invoice LIKE ?");
            $c->execute([$prefix.'%']);
            $nomor = $prefix . str_pad($c->fetchColumn()+1, 4, '0', STR_PAD_LEFT);

            // (3) Siapkan 3 query yang dipakai berulang
            $cekStok    = $pdo->prepare("SELECT nama_barang, stok_sekarang, harga FROM barang WHERE id_barang = ? FOR UPDATE");
            $insert     = $pdo->prepare("INSERT INTO stok_keluar (...) VALUES (?,?,?,?,?,?,?,?)");
            $kurangStok = $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang - ? WHERE id_barang = ?");

            // (4) Proses tiap item dalam keranjang
            foreach ($keranjang as $item) {
                $cekStok->execute([$idB]);            // kunci baris stok (FOR UPDATE)
                $bar = $cekStok->fetch();
                if ($bar['stok_sekarang'] < $jml)     // (5) validasi ulang di server
                    throw new Exception('Stok tidak mencukupi');

                $insert->execute([...]);              // catat keluar
                $kurangStok->execute([$jml, $idB]);   // kurangi stok
            }

            $pdo->commit();                            // (6) SIMPAN SEMUA sekaligus
            audit('tambah','stok_keluar',"Buat invoice $nomor");
            flash('success',"Invoice: $nomor");
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack(); // (7) GAGAL → BATALKAN SEMUA
            flash('error', $ex->getMessage());
        }
    }
}
```

**Penjelasan konsep (yang harus kamu jelaskan ke dosen):**

1. **`beginTransaction()`** — membuka "transaksi". Semua perubahan setelah ini bersifat sementara sampai `commit()`.
2. **Nomor invoice** — hitung invoice hari ini, +1, lalu format 4 digit (`0007`). Reset tiap hari karena prefix memuat tanggal.
3. **Prepared statement** — query disiapkan sekali, dieksekusi berkali-kali dengan data berbeda (efisien + aman injection).
4. **`FOR UPDATE`** — saat `SELECT ... FOR UPDATE`, baris barang itu **dikunci**. Jika ada kasir lain memproses barang sama di saat bersamaan, dia harus **menunggu** sampai transaksi ini selesai. → **Mencegah stok minus / terjual ganda (race condition).**
5. **Validasi ulang di server** — meski JS sudah cek stok, server cek lagi (jangan percaya client).
6. **`commit()`** — kalau semua item sukses, semua perubahan disimpan permanen **sekaligus**.
7. **`rollBack()`** — kalau SATU item gagal (mis. stok kurang), **SEMUA dibatalkan**. Tidak ada invoice setengah jadi. Inilah sifat **ACID (Atomicity)** — "semua atau tidak sama sekali".

**Void invoice** (membatalkan): kembalikan stok semua item (`stok + jumlah`) lalu `DELETE` baris invoice — juga dalam satu transaksi.

---

### 4.3 `karyawan/stok_masuk.php` — barang masuk & void

**Barang masuk** (transaksi gabungan):
```php
$pdo->beginTransaction();
$pdo->prepare("INSERT INTO stok_masuk (id_barang,jumlah,tanggal,keterangan,user_id) VALUES (?,?,?,?,?)")
    ->execute([$idBarang,$jumlah,$tanggal,$ket,current_user()['id']]);
$pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang + ? WHERE id_barang = ?")
    ->execute([$jumlah,$idBarang]);
$pdo->commit();   // catat + tambah stok = satu kesatuan
```

**Void (batal) dengan proteksi:**
```php
$stok = (int)$s->fetchColumn();
if ($stok < (int)$row['jumlah']) {
    $pdo->rollBack();
    flash('error',"Tidak bisa dibatalkan: stok ($stok) lebih kecil dari jumlah transaksi...");
} else {
    $pdo->prepare("UPDATE barang SET stok_sekarang = stok_sekarang - ? ...")->execute([...]);
    $pdo->prepare("DELETE FROM stok_masuk WHERE id = ?")->execute([$id]);
    $pdo->commit();
}
```
- **Logika penting:** kalau barang sudah terlanjur keluar (stok sekarang < jumlah yang mau dibatalkan), void **ditolak** agar stok tidak menjadi minus.

---

### 4.4 `karyawan/barang.php` — upload gambar & barcode unik

**Validasi upload gambar** (`uploadGambar()`):
```php
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png'])) { $err='Format harus JPG/PNG'; return false; }
if ($file['size'] > 2*1024*1024)          { $err='Maks 2MB';            return false; }
if (@getimagesize($file['tmp_name'])===false) { $err='Bukan gambar valid'; return false; }
$namaFile = 'B_'.preg_replace('/[^A-Za-z0-9_-]/','',$barcode).'.'.$ext;  // nama aman
move_uploaded_file($file['tmp_name'], $dir.$namaFile);
```
- 3 lapis validasi: **ekstensi**, **ukuran**, dan **isi file benar gambar** (`getimagesize`, bukan sekadar percaya nama file).
- Nama file dibersihkan dari karakter berbahaya → cegah path traversal.

**Barcode unik (dobel pengaman):**
```php
// di server (wajib):
$cek = $pdo->prepare("SELECT id_barang FROM barang WHERE kode_barcode = ? AND id_barang <> ?");
$cek->execute([$barcode, $id]);
if ($cek->fetch()) flash('error', "Barcode sudah terdaftar");
```
- Plus pengecekan **AJAX real-time** lewat `ajax/cek_barcode.php` saat user mengetik → feedback instan, tapi server tetap validasi (jangan percaya client).

---

### 4.5 `includes/footer.php` — command palette (Ctrl+K)

```js
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {   // tangkap Ctrl+K
    e.preventDefault();
    openPalette();
  }
});

async function runSearch(q){
  const navHits = navMatches(q);                      // cari di menu (lokal)
  let barangHits = [];
  if (q.trim().length >= 1){
    const r = await fetch(BARANG_URL + '?q=' + encodeURIComponent(q)); // cari barang (AJAX)
    const d = await r.json();
    barangHits = d.items.map(b => ({ title:b.nama_barang, url: RIWAYAT_URL + b.id_barang }));
  }
  render(navHits, barangHits);                        // tampilkan hasil
}
```
- Menggabungkan **pencarian menu (sisi client)** + **pencarian barang (AJAX ke server)**.
- Navigasi keyboard (panah ↑↓, Enter) + **debounce** 160ms (tidak query tiap ketikan) + abaikan hasil usang (variabel `seq`).

**Intersepsi konfirmasi** (pengganti `confirm()` native):
```js
document.addEventListener('submit', function(e){
  const form = e.target;
  if (form.dataset.confirm && !form.dataset.confirmed){
    e.preventDefault();                               // tahan submit
    confirmDialog({ message: form.dataset.confirm }).then(ok => {
      if (ok){ form.dataset.confirmed = '1'; form.submit(); } // lanjut bila "Ya"
    });
  }
}, true);
```
- Form cukup diberi atribut `data-confirm="..."` → otomatis muncul modal kustom sebelum submit.

---

### 4.6 `admin/laporan.php` — export CSV

```php
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_..._.csv"'); // paksa download
    $out = fopen('php://output', 'w');
    fputcsv($out, ['No. Invoice','Pelanggan','Tanggal','Total Qty','Nilai']); // header kolom
    foreach ($rekap as $r) fputcsv($out, [...]);     // tiap baris
    fclose($out);
    exit;                                             // berhenti sebelum HTML
}
```
- **`Content-Disposition: attachment`** memberi tahu browser untuk **mengunduh** (bukan menampilkan).
- `fputcsv` menulis baris CSV dengan escaping otomatis. `exit` penting agar HTML halaman tidak ikut tercampur ke file CSV.

---

## 5. KONSEP KUNCI (untuk tanya-jawab dosen)

| Konsep | Di mana | Penjelasan 1 kalimat |
|---|---|---|
| **PDO + Prepared Statement** | semua query | Cegah SQL injection — data tidak disambung ke string query |
| **Transaksi ACID** | stok_masuk, stok_keluar | Operasi gabungan jadi satu kesatuan (semua/tidak sama sekali) |
| **Row Lock `FOR UPDATE`** | stok_keluar | Cegah konflik/stok minus saat akses bersamaan (race condition) |
| **bcrypt** (`password_hash`) | login, karyawan | Password di-hash, tak pernah disimpan plain text |
| **CSRF token** | semua form POST | Cegah pemalsuan request dari situs lain |
| **RBAC** (`require_role`) | halaman admin | Batasi akses berdasarkan peran |
| **Session** | security.php | Menjaga identitas user antar-halaman |
| **XSS escaping** (`e()`) | semua output | Output user di-escape sebelum ditampilkan |
| **AJAX / JSON** | ajax/ | Validasi & pencarian tanpa reload halaman |
| **Audit trail** | audit() | Mencatat semua perubahan untuk akuntabilitas |
| **`@media print`** | invoice, laporan, label | Sembunyikan UI layar saat dicetak → dokumen bersih |

---

## CONTOH KALIMAT PEMBUKA & PENUTUP PRESENTASI

**Pembuka:**
> "SIGMA adalah sistem inventory gudang berbasis web. Dibangun dengan PHP native dan MySQL, fokus pada tiga hal: kecepatan operasional, keamanan data, dan keandalan transaksi stok."

**Saat menunjukkan stok_keluar.php:**
> "Bagian ini paling krusial. Saat memproses penjualan, saya pakai transaksi ACID dan penguncian baris `FOR UPDATE`, sehingga stok tidak akan pernah salah hitung meskipun beberapa kasir bertransaksi bersamaan."

**Penutup:**
> "Selain fungsi inti, saya menambahkan UX modern — dark mode, command palette, dan audit trail. Untuk pengembangan ke depan: reset password, pembatasan percobaan login, dan build CSS untuk produksi."
