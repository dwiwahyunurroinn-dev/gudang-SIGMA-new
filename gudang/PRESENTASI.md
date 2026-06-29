# 🎤 SKENARIO PRESENTASI SIGMA (Demo + Baca Kode)

Dokumen ini adalah **panduan presentasi dari awal sampai akhir**. Ada:
1. Alur logika aplikasi (cerita besarnya)
2. Skenario demo (apa diklik + apa diucapkan)
3. Cara baca kode (disambungkan ke demo)
4. Poin penting & antisipasi pertanyaan

> Bahasa sengaja dibuat sederhana. Tinggal **baca bagian "UCAPKAN"** sambil jalan. 😎

---

# BAGIAN 0 — ALUR LOGIKA APLIKASI (pahami ini dulu)

Bayangkan SIGMA seperti **toko dengan kasir digital**. Alur besarnya:

```
        ┌─────────────────────────────────────────────────────────┐
        │  1. User buka website  →  diminta LOGIN                  │
        │  2. Login benar  →  dapat "gelang masuk" (session)       │
        │  3. Tiap halaman cek gelang: "kamu siapa? boleh masuk?"  │
        │  4. User klik tombol (mis. Jual Barang)                  │
        │  5. Data dikirim ke "koki" PHP                           │
        │  6. PHP olah data + simpan ke DATABASE (gudang data)     │
        │  7. Hasil ditampilkan kembali ke layar                  │
        └─────────────────────────────────────────────────────────┘
```

**3 pemain utama** (analogi restoran):
- **Database (MySQL)** = gudang penyimpanan data
- **PHP** = koki yang mengolah
- **Tampilan (HTML/CSS/JS)** = yang dilihat & diklik user

**UCAPKAN (pembukaan):**
> "Selamat pagi. Saya akan presentasikan SIGMA — sistem inventory gudang berbasis web.
> Cara kerjanya sederhana: user login, lalu setiap aksi seperti mencatat barang masuk
> atau menjual barang, datanya diolah oleh PHP dan disimpan di database MySQL.
> Saya bangun dengan fokus pada **tiga hal: cepat, aman, dan andal**."

---

# BAGIAN 1 — DEMO (sekitar 4–5 menit)

Lakukan berurutan. Di tiap langkah ada yang **DIKLIK** dan yang **DIUCAPKAN**.

### Langkah 1 — Login
- **KLIK:** masuk pakai akun admin.
- **UCAPKAN:** "Saya login sebagai admin. Sistem mengecek password, lalu memberi sesi
  login supaya tahu siapa yang sedang memakai."

### Langkah 2 — Dashboard
- **KLIK:** tunjukkan dashboard.
- **UCAPKAN:** "Ini dashboard — ringkasan cepat: jumlah barang, total stok, transaksi
  hari ini. Ada grafik tren dan daftar barang yang **stoknya menipis**."

### Langkah 3 — Tambah Barang
- **KLIK:** Data Barang → Tambah Barang → isi form → (kalau bisa) scan barcode → simpan.
- **UCAPKAN:** "Saya tambahkan produk baru. Perhatikan saat selesai, muncul **notifikasi
  hijau** di pojok. Stok awalnya 0 — karena stok hanya boleh berubah lewat transaksi resmi."

### Langkah 4 — Barang Masuk
- **KLIK:** Stok Masuk → pilih barang → isi jumlah → simpan.
- **UCAPKAN:** "Ini mencatat barang datang dari supplier. Stoknya otomatis bertambah."

### Langkah 5 — Barang Keluar (JUALAN) ⭐
- **KLIK:** Stok Keluar → tambah beberapa barang ke keranjang → Proses & Buat Invoice.
- **UCAPKAN:** "Ini bagian inti — penjualan. Saya susun keranjang belanja, lalu proses.
  Sistem otomatis **membuat nomor nota**, **mengurangi stok**, dan **menampilkan invoice**
  yang siap dicetak jadi PDF."

### Langkah 6 — Fitur unggulan (pamer sebentar)
- **KLIK & UCAPKAN:**
  - Tekan **Ctrl+K** → "pencarian cepat, bisa lompat ke halaman atau cari barang."
  - Klik **🌙** → "ada mode gelap, tersimpan otomatis."
  - Tunjuk **🔔** → "notifikasi barang yang perlu di-restock."
  - Coba **hapus** sesuatu → "muncul **kotak konfirmasi** yang rapi, bukan popup standar."
  - Buka **Log Aktivitas** → "semua perubahan tercatat: siapa, apa, kapan. Untuk akuntabilitas."

---

# BAGIAN 2 — BACA KODE (sekitar 3–4 menit)

Sekarang sambungkan demo tadi ke kodenya. **Buka file di VS Code**, tunjuk blok yang disebut.

> Strategi: jelaskan **3 file** saja — login, stok_keluar (andalan), barang.
> Jangan baca semua baris; tunjuk blok penting lalu ucapkan artinya.

---

## 📄 KODE 1 — `login.php` (sambungan dari Demo Langkah 1)

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();
```
**UCAPKAN:**
> "Saat login, saya cari user di database. Lihat tanda tanya `?` ini — namanya
> *prepared statement*, supaya data yang diketik **tidak bisa dipakai membobol** database."

```php
if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
}
```
**UCAPKAN:**
> "Password di database **sudah diacak** (di-hash), jadi saya cocokkan pakai
> `password_verify`, bukan teks biasa. Kalau benar, saya buat **sesi login** — ini
> 'gelang masuk' yang tadi saya ceritakan."

---

## 📄 KODE 2 — `karyawan/stok_keluar.php` ⭐ (sambungan dari Demo Langkah 5)

Ini bagian terpenting. Pecah jadi 4 langkah kecil:

```php
$pdo->beginTransaction();
```
**UCAPKAN:**
> "Saat memproses penjualan, saya buka **transaksi**. Artinya semua perubahan setelah ini
> sifatnya 'sementara' dulu — belum benar-benar disimpan."

```php
$cekStok = $pdo->prepare("SELECT ... FROM barang WHERE id_barang = ? FOR UPDATE");
```
**UCAPKAN:**
> "Ini `FOR UPDATE` — saat saya cek stok, barangnya **saya kunci sebentar**. Jadi kalau
> ada kasir lain menjual barang yang sama persis di saat bersamaan, dia menunggu giliran.
> **Stok tidak akan pernah salah hitung.**"

```php
foreach ($keranjang as $item) {
    if ($bar['stok_sekarang'] < $jml) throw new Exception('Stok tidak cukup');
    $insert->execute([...]);              // catat penjualan
    $kurangStok->execute([$jml, $idB]);   // kurangi stok
}
```
**UCAPKAN:**
> "Saya proses tiap barang di keranjang: cek stok cukup, catat penjualan, kurangi stok."

```php
    $pdo->commit();                       // SUKSES → simpan semua
} catch (Exception $ex) {
    $pdo->rollBack();                     // GAGAL → batalkan semua
}
```
**UCAPKAN:**
> "Kalau semua sukses, `commit` — **baru di sini semua disimpan permanen sekaligus**.
> Tapi kalau ada satu saja gagal, `rollBack` — **semuanya dibatalkan**. Prinsipnya
> **'semua atau tidak sama sekali'**, persis seperti **transfer ATM**."

---

## 📄 KODE 3 — `karyawan/barang.php` (sambungan dari Demo Langkah 3)

```php
$cek = $pdo->prepare("SELECT id_barang FROM barang WHERE kode_barcode = ? AND id_barang <> ?");
if ($cek->fetch()) flash('error', "Barcode sudah terdaftar.");
```
**UCAPKAN:**
> "Sebelum menyimpan barang, saya pastikan **barcode-nya belum dipakai** barang lain."

```php
if (!in_array($ext, ['jpg','jpeg','png'])) return false;   // jenis file
if ($file['size'] > 2*1024*1024)           return false;   // ukuran maks 2MB
if (@getimagesize($file['tmp_name'])===false) return false; // benar-benar gambar
```
**UCAPKAN:**
> "Untuk upload foto, saya cek 3 lapis: jenis file, ukuran, dan **benar-benar gambar**
> (bukan file berbahaya yang disamarkan). Ini demi keamanan."

---

# BAGIAN 3 — POIN PENTING (kesimpulan, ~1 menit)

**UCAPKAN (penutup):**
> "Jadi singkatnya, SIGMA punya 4 kekuatan:
> 1. **Cepat** — scan barcode langsung jadi nota.
> 2. **Aman** — password diacak, anti-pembobolan database, hak akses bertingkat.
> 3. **Andal** — stok tidak pernah salah hitung berkat sistem 'kunci' dan 'semua-atau-batal'.
> 4. **Terlacak** — semua aktivitas tercatat otomatis.
>
> Untuk ke depan, saya berencana menambah reset password dan pembatasan percobaan login.
> Terima kasih."

---

# 🛡️ ANTISIPASI PERTANYAAN DOSEN

| Pertanyaan | Jawaban santai |
|---|---|
| "Aman dari pembobolan database?" | "Ya, semua query pakai *prepared statement* — data yang diketik tidak bisa jadi perintah." |
| "Password disimpan bagaimana?" | "Diacak pakai bcrypt, tidak pernah disimpan apa adanya — seperti telur diceplok, tak bisa dibalik." |
| "Kalau 2 orang jual barang sama bersamaan?" | "Stok dikunci sementara (`FOR UPDATE`), yang lain menunggu — jadi tidak salah hitung." |
| "Kalau transaksi error di tengah?" | "Sistem 'semua atau batal' seperti transfer ATM — dibatalkan total, data tetap rapi." |
| "Bedanya admin & karyawan?" | "Admin bisa semua, karyawan hanya operasional. Seperti kartu hotel tamu vs manajer." |
| "Kenapa pakai PHP & MySQL?" | "Gratis, populer, dan cocok untuk aplikasi web berbasis data seperti ini." |
| "Kenapa offline/lokal?" | "Gudang sering koneksi tidak stabil; semua library disimpan lokal, jalan tanpa internet." |
| "Kalau ada data janggal, bisa dilacak?" | "Bisa, lewat Log Aktivitas — tercatat siapa, apa, kapan." |

---

# ✅ CHECKLIST SEBELUM MAJU

- [ ] XAMPP nyala (Apache + MySQL)
- [ ] Sudah login, data contoh ada (barang & transaksi)
- [ ] Folder `assets/uploads` bisa ditulis (upload foto jalan)
- [ ] Tab browser + VS Code (file: login, stok_keluar, barang) sudah dibuka
- [ ] Video/screenshot cadangan disiapkan (jaga-jaga error)
- [ ] Latihan 1–2× pakai stopwatch (target 10 menit)

**Kamu pasti bisa! Tarik napas, pelan-pelan, ceritakan seperti mengobrol. 🚀**
