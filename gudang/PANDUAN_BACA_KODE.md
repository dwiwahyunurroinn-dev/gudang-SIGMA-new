# 🗣️ PANDUAN MEMBACA KODE — SIGMA

Cara pakai dokumen ini: saat presentasi, **buka file aslinya di VS Code**, lalu
**ucapkan kalimat "Cara baca"** di bawah tiap potongan kode. Sudah ditulis dengan
bahasa santai — tinggal kamu baca sambil menunjuk layar. 😎

> Tips: tidak perlu menjelaskan SEMUA baris. Jelaskan blok-blok penting saja.
> Urutan rekomendasi presentasi: **login → stok_keluar → barang**.

---

## 📄 FILE 1: `login.php` — pintu masuk (mulai dari sini, paling gampang)

```php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
```
**Cara baca:**
> "Di dua baris paling atas, saya memanggil dua file penting: satu untuk **menyambung
> ke database**, satu lagi berisi **fungsi-fungsi keamanan**. Hampir semua halaman
> diawali seperti ini."

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();
```
**Cara baca:**
> "Di sini saya **mencari user berdasarkan username** yang diketik. Perhatikan saya
> pakai tanda tanya `?` lalu mengisinya terpisah — ini namanya *prepared statement*,
> supaya aman dari pembobolan database."

```php
if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    audit('login', 'auth', "Login berhasil sebagai {$user['role']}");
}
```
**Cara baca:**
> "Kalau user ditemukan, saya cocokkan password-nya pakai `password_verify`. Password
> di database itu **sudah diacak**, jadi saya tidak membandingkan teks biasa.
> Kalau cocok, saya **buat sesi login** (`$_SESSION`) supaya sistem mengingat siapa
> yang masuk, lalu **catat ke log aktivitas**."

---

## 📄 FILE 2: `config/security.php` — pusat keamanan

```php
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("⛔ Akses ditolak...");
    }
}
```
**Cara baca:**
> "Ini fungsi penjaga hak akses. Halaman admin memanggil `require_role('admin')` di
> baris pertama. Kalau yang membuka **bukan admin**, langsung **ditolak** (kode 403).
> Jadi karyawan tidak bisa masuk ke halaman admin walau tahu alamatnya."

```php
function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
```
**Cara baca:**
> "Fungsi kecil `e()` ini saya pakai setiap menampilkan data dari user. Tugasnya
> **menetralkan kode berbahaya** supaya tidak ada yang bisa menyisipkan script jahat
> (serangan namanya XSS)."

```php
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        die("Token keamanan tidak valid...");
    }
}
```
**Cara baca:**
> "Tiap formulir punya **token rahasia**. Sebelum menyimpan data, fungsi ini cek
> token-nya asli atau tidak. Kalau palsu → ditolak. Ini mencegah orang lain
> memalsukan perintah atas nama user (serangan CSRF)."

---

## 📄 FILE 3: `karyawan/stok_keluar.php` — JUALAN & NOTA ⭐ (andalan!)

Ini bagian paling penting. Tenang, aku pecah jadi langkah kecil.

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
```
**Cara baca:**
> "Kode ini jalan saat tombol *Proses* ditekan. Hal pertama: **cek token keamanan**."

```php
$keranjang = json_decode($_POST['keranjang'] ?? '[]', true);
```
**Cara baca:**
> "Saya ambil isi **keranjang belanja** yang tadi disusun di layar — daftar barang
> dan jumlahnya."

```php
$pdo->beginTransaction();
```
**Cara baca:**
> "Nah ini kuncinya. `beginTransaction` artinya **'mulai transaksi'**. Semua perubahan
> setelah ini sifatnya sementara — belum benar-benar disimpan sampai saya bilang
> 'simpan'."

```php
$prefix = "INV-" . date('Ymd') . "-";
$c = $pdo->prepare("SELECT COUNT(DISTINCT nomor_invoice) FROM stok_keluar WHERE nomor_invoice LIKE ?");
$c->execute([$prefix . '%']);
$nomor = $prefix . str_pad($c->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
```
**Cara baca:**
> "Di sini saya **bikin nomor invoice otomatis**, formatnya `INV-tanggal-nomorurut`.
> Caranya: hitung sudah ada berapa invoice hari ini, lalu tambah satu. Jadi tiap hari
> nomornya mulai dari 0001 lagi."

```php
$cekStok = $pdo->prepare("SELECT nama_barang, stok_sekarang, harga FROM barang WHERE id_barang = ? FOR UPDATE");
```
**Cara baca:**
> "Ini bagian penting kedua: `FOR UPDATE`. Saat saya mengecek stok barang, baris itu
> **saya kunci sementara**. Jadi kalau ada kasir lain menjual barang yang sama
> di detik yang sama, dia harus menunggu giliran — **stok tidak akan salah hitung**."

```php
foreach ($keranjang as $item) {
    $cekStok->execute([$idB]);
    $bar = $cekStok->fetch();
    if ($bar['stok_sekarang'] < $jml) {
        throw new Exception('Stok tidak mencukupi...');
    }
    $insert->execute([...]);                 // catat barang keluar
    $kurangStok->execute([$jml, $idB]);      // kurangi stok
}
```
**Cara baca:**
> "Saya **putar satu per satu** barang di keranjang. Untuk tiap barang: cek dulu
> stoknya cukup atau tidak. Kalau kurang, saya **lempar error** (transaksi gagal).
> Kalau cukup, saya **catat penjualannya** dan **kurangi stoknya**."

```php
    $pdo->commit();
    audit('tambah', 'stok_keluar', "Buat invoice $nomor");
    flash('success', "Transaksi berhasil. No. Invoice: $nomor");
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $ex->getMessage());
}
```
**Cara baca:**
> "Kalau semua barang berhasil, saya panggil `commit` — **baru di sinilah semua
> perubahan benar-benar disimpan sekaligus**. Tapi kalau ada SATU saja yang gagal,
> masuk ke bagian `catch` dan saya panggil `rollBack` — **semuanya dibatalkan**,
> seolah tidak pernah terjadi. Prinsipnya **'semua atau tidak sama sekali'**,
> persis seperti transfer ATM."

---

## 📄 FILE 4: `karyawan/barang.php` — kelola produk + foto

```php
if ($barcode === '' || $nama === '') {
    flash('error', 'Kode barcode dan nama barang wajib diisi.');
}
```
**Cara baca:**
> "Sebelum menyimpan, saya **cek dulu** barcode dan nama tidak boleh kosong."

```php
$cek = $pdo->prepare("SELECT id_barang FROM barang WHERE kode_barcode = ? AND id_barang <> ?");
$cek->execute([$barcode, $id]);
if ($cek->fetch()) {
    flash('error', "Barcode \"$barcode\" sudah terdaftar.");
}
```
**Cara baca:**
> "Lalu saya pastikan **barcode-nya belum dipakai barang lain** — supaya tidak ada
> dua barang dengan barcode sama."

```php
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png'])) { $err = 'Format harus JPG/PNG'; return false; }
if ($file['size'] > 2 * 1024 * 1024)       { $err = 'Maksimal 2MB';        return false; }
if (@getimagesize($file['tmp_name']) === false) { $err = 'Bukan gambar valid'; return false; }
```
**Cara baca:**
> "Untuk **upload foto barang**, saya cek 3 hal: jenis file-nya (harus JPG/PNG),
> ukurannya (maksimal 2MB), dan **benar-benar gambar** (bukan file virus yang
> namanya diubah jadi .jpg). Tiga lapis pengaman."

```php
$pdo->prepare("INSERT INTO barang (...) VALUES (?,?,?,?,?,?,?,0)")->execute([...]);
audit('tambah', 'barang', "Tambah barang \"$nama\"...");
```
**Cara baca:**
> "Terakhir, simpan barang ke database. Perhatikan angka **0** di akhir — itu
> **stok awal selalu 0**, karena stok hanya boleh bertambah lewat menu Stok Masuk,
> bukan diisi manual. Dan setiap aksi saya catat ke log aktivitas."

---

## 📄 FILE 5: contoh QUERY di dashboard (kalau ditanya soal data)

```php
$totalStok = (int) $pdo->query("SELECT COALESCE(SUM(stok_sekarang),0) FROM barang")->fetchColumn();
```
**Cara baca:**
> "Untuk kartu 'Total Stok' di dashboard, saya **menjumlahkan semua stok barang**.
> `COALESCE(...,0)` artinya: kalau belum ada barang sama sekali, tampilkan 0
> (bukan kosong)."

```php
$menipis = $pdo->query("SELECT nama_barang, stok_sekarang FROM barang
                        WHERE stok_sekarang <= 5 ORDER BY stok_sekarang ASC LIMIT 5")->fetchAll();
```
**Cara baca:**
> "Untuk panel 'Perlu Restock', saya **ambil barang yang stoknya 5 atau kurang**,
> diurutkan dari yang paling sedikit, maksimal 5 barang."

---

## 🧠 4 KATA KUNCI YANG WAJIB KAMU INGAT

Kalau lupa segalanya, ingat 4 ini saja — dosen pasti terkesan:

1. **Prepared statement** (tanda `?`) → "biar aman dari pembobolan database."
2. **`beginTransaction` / `commit` / `rollBack`** → "semua atau tidak sama sekali, seperti transfer ATM."
3. **`FOR UPDATE`** → "kunci stok biar tidak salah hitung kalau bersamaan."
4. **`password_verify` + hash** → "password diacak, tidak disimpan apa adanya."

---

## ✅ Cara latihan paling efektif

1. Buka VS Code, taruh dokumen ini di sebelah file kodenya (split screen).
2. Tunjuk satu blok kode → baca bagian **"Cara baca"**-nya dengan suara.
3. Ulangi 2–3 kali sampai kamu bisa **tanpa melihat teks** (cukup lihat kodenya).

Kamu pasti bisa! 🚀
