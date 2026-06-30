# 🧱 PENJELASAN DASAR: `database.php` & `security.php`

Khusus untuk pemula. Setiap istilah dijelaskan pelan-pelan. 😊

---

## 📕 KAMUS ISTILAH DASAR (baca ini dulu)

| Istilah | Artinya gampang |
|---|---|
| `<?php` | Tanda "mulai kode PHP". Semua kode PHP ada di dalamnya. |
| `$nama` | **Variabel** = wadah/kotak untuk menyimpan nilai. Selalu diawali `$`. |
| `=` | "isi dengan". `$x = 5` artinya kotak `x` diisi angka 5. |
| `function` | **Fungsi** = mesin mini yang bisa dipanggil berkali-kali untuk satu tugas. |
| `define('X', ...)` | Membuat **konstanta** — nilai tetap yang tidak berubah (huruf besar). |
| `PDO` | "PHP Data Objects" = **alat resmi PHP untuk ngobrol dengan database**. |
| `new PDO(...)` | Membuat **objek** PDO = membuka "sambungan telepon" ke database. |
| `try { } catch { }` | "Coba lakukan ini; kalau **error**, tangani di sini" (anti-gagal-total). |
| `if (...)` | "Kalau syarat benar, lakukan...". |
| `->` | Tanda panah = "panggil kemampuan dari sebuah objek". (`$pdo->query(...)`) |
| `$_SESSION` | "Kotak ingatan" server tentang user yang login (gelang masuk). |
| `$_POST` | Data yang **dikirim dari formulir** saat tombol Simpan ditekan. |
| `$_SERVER` | Info tentang server & pengunjung (mis. alamat IP). |
| `isset($x)` | "Apakah `$x` **ada/terisi**?" → jawabannya benar/salah. |
| `empty($x)` | "Apakah `$x` **kosong**?" |
| `??` | "Kalau kiri tidak ada, pakai kanan". `$x ?? 0` = pakai `$x`, kalau tak ada pakai 0. |
| `return` | Fungsi **mengembalikan/menyerahkan** hasil. |
| `exit` / `die()` | **Hentikan** program di sini juga. |
| `header("Location: ...")` | Perintah **pindah/redirect** ke halaman lain. |

---

## 🔑 APA ITU `define()`? (penjelasan lengkap)

`define('NAMA', nilai)` membuat sebuah **KONSTANTA**.

**Konstanta = nilai tetap yang tidak bisa diubah** setelah dibuat.

Bedakan dengan **variabel** (`$x`) yang isinya bisa ganti-ganti:

> 🪧 **Variabel `$x`** itu seperti **papan tulis** — bisa dihapus & ditulis ulang kapan saja.
> 🪨 **Konstanta `define`** itu seperti **pahatan di batu** — sekali dibuat, permanen.

**Ciri-ciri konstanta:**
- Ditulis **HURUF BESAR** (kebiasaan programmer), contoh: `BASE_URL`.
- **Tanpa tanda `$`** di depannya.
- Bisa dipakai di **mana saja** di seluruh program.

**Contoh di proyek:**
```php
define('BASE_URL', '/gudang');
```
→ Membuat konstanta `BASE_URL` berisi teks `/gudang`.
Dipakai untuk menyusun alamat link. Contoh `BASE_URL . "/login.php"` menghasilkan
`/gudang/login.php`. **Keuntungannya:** kalau folder ganti nama, cukup ubah **satu baris ini**,
semua link di seluruh aplikasi otomatis ikut berubah.

| | Variabel `$x` | Konstanta `define` |
|---|---|---|
| Pakai tanda `$`? | Ya (`$x`) | Tidak (`BASE_URL`) |
| Bisa diubah? | Ya | **Tidak** (tetap/permanen) |
| Penulisan | huruf kecil | HURUF BESAR |
| Ibarat | papan tulis | pahatan di batu |

---

# 📄 FILE 1: `config/database.php` — menyambung ke database

Tugasnya cuma satu: **membuka sambungan ke database** supaya halaman lain bisa
menyimpan/mengambil data. Mari baca per bagian.

```php
define('BASE_URL', '/gudang');
```
**Artinya:**
> Membuat konstanta `BASE_URL` berisi `/gudang` (nama folder aplikasi). Dipakai di
> banyak tempat untuk menyusun alamat link. Kalau folder ganti nama, cukup ubah di sini.

```php
$host   = 'localhost';   // alamat server database (komputer ini sendiri)
$dbname = 'gudang_db';   // nama database-nya
$user   = 'root';        // username database
$pass   = '';            // password database (kosong, default XAMPP)
```
**Artinya:**
> Empat "kotak" berisi **info untuk login ke database** — seperti alamat, nama gudang,
> user, dan password. Ini yang dipakai PDO untuk menyambung.

```php
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
```
**Artinya, baris demi baris:**
> - `try { ... }` → "**coba** sambung ke database; kalau gagal lompat ke `catch`".
> - `$pdo = new PDO(...)` → **membuka sambungan**. Hasilnya disimpan di kotak `$pdo`.
>   Mulai sekarang, `$pdo` inilah "telepon" yang dipakai semua halaman untuk bicara ke database.
> - `"mysql:host=...;dbname=...;charset=utf8mb4"` → alamat lengkap + jenis database (MySQL) +
>   set huruf `utf8mb4` (mendukung emoji & semua karakter).
> - Bagian `[ ... ]` adalah **3 pengaturan**:
>   - `ERRMODE_EXCEPTION` → "kalau ada error, beri tahu dengan jelas" (bukan diam-diam).
>   - `FETCH_ASSOC` → "kalau ambil data, susun pakai nama kolom" (mis. `$row['nama']`).
>   - `EMULATE_PREPARES => false` → "pakai prepared statement **asli** dari database" (lebih aman).
> - `catch (PDOException $e) { die(...) }` → **kalau sambungan gagal**, tampilkan pesan
>   error dan hentikan program. (Inilah pesan "Koneksi database gagal" yang sempat kamu lihat.)

**Ringkas:** file ini = **"buka telepon ke database, simpan di `$pdo`"**. Itu saja.

---

# 📄 FILE 2: `config/security.php` — satpam & alat bantu

File ini **tidak menjalankan apa-apa langsung** — isinya **kumpulan fungsi** (mesin mini)
yang dipanggil halaman lain saat dibutuhkan. Mari bedah.

### Bagian pembuka
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```
**Artinya:**
> "Kalau **sesi belum dimulai**, mulai sekarang." `session_start()` mengaktifkan
> `$_SESSION` — "kotak ingatan" server tentang user. Tanpa ini, sistem lupa siapa yang login.

```php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gudang');
}
```
**Artinya:**
> "Kalau `BASE_URL` belum dibuat, buat sekarang." Pengaman bila file ini dipanggil
> tanpa `database.php`. (`!` artinya "tidak").

### Fungsi: wajib login
```php
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}
```
**Artinya:**
> Fungsi `require_login` = **"halaman ini wajib login"**.
> - `if (!isset($_SESSION['user_id']))` → "kalau **tidak ada** tanda user login...".
> - `header("Location: .../login.php"); exit;` → "...**lempar ke halaman login** dan stop."
>
> Jadi halaman yang memanggil ini tidak bisa dibuka kalau belum login.

### Fungsi: wajib role tertentu (admin/karyawan)
```php
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("⛔ Akses ditolak...");
    }
}
```
**Artinya:**
> - `require_login();` → pastikan login dulu.
> - `($_SESSION['role'] ?? '') !== $role` → "kalau peran user **bukan** yang diminta...".
>   (`!==` artinya "tidak sama dengan").
> - `die("Akses ditolak")` → tolak & hentikan.
>
> Contoh: halaman admin memanggil `require_role('admin')` → karyawan langsung ditolak.

### Bagian CSRF (anti pemalsuan formulir)
```php
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
```
**Artinya:**
> Membuat **token rahasia** (deretan karakter acak). `random_bytes(32)` menghasilkan
> 32 byte acak, `bin2hex` mengubahnya jadi teks. Token ini disimpan & dipakai sebagai
> "tiket berstempel" untuk tiap formulir.

```php
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
```
**Artinya:**
> Membuat sepotong HTML berisi token tadi (tersembunyi) untuk **ditempel di formulir**.

```php
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        die("Token keamanan tidak valid...");
    }
}
```
**Artinya:**
> Saat formulir dikirim, fungsi ini **mencocokkan token**:
> - `$_POST['csrf']` = token yang dikirim formulir.
> - `$_SESSION['csrf']` = token asli yang server simpan.
> - `hash_equals(...)` = membandingkan keduanya **secara aman**.
> - Kalau **tidak cocok / tidak ada** → tolak (`die`).
>
> Ini mencegah situs jahat memalsukan perintah atas nama user.

### Helper: anti-XSS
```php
function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
```
**Artinya:**
> Fungsi pendek `e()` dipakai **setiap menampilkan data dari user**.
> `htmlspecialchars` mengubah karakter berbahaya (seperti `<` `>`) jadi teks biasa,
> sehingga **kode jahat tidak bisa jalan** di halaman (serangan XSS dicegah).

### Helper: data user yang login
```php
function current_user(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'nama' => $_SESSION['nama']    ?? '',
        'role' => $_SESSION['role']    ?? '',
    ];
}
```
**Artinya:**
> Mengembalikan data user yang sedang login (id, nama, role) dari kotak ingatan `$_SESSION`.
> Dipakai untuk menyapa user & mencatat siapa pelaku sebuah aksi.

### Helper: pesan notifikasi (flash)
```php
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);   // hapus setelah dibaca → cuma tampil sekali
        return $f;
    }
    return null;
}
```
**Artinya:**
> - `flash(...)` → **menitipkan pesan** (mis. "Berhasil disimpan") untuk ditampilkan
>   di halaman berikutnya.
> - `get_flash()` → **mengambil** pesan itu lalu `unset` (menghapusnya) supaya hanya
>   muncul **sekali**, tidak terus-menerus.

### Audit trail (pencatatan aktivitas)
```php
function audit(string $aksi, string $entitas, string $deskripsi = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        audit_ensure();                      // buat tabel bila belum ada
        $u = current_user();
        $pdo->prepare("INSERT INTO audit_log (...) VALUES (?,?,?,?,?,?)")
            ->execute([$u['id'], $u['nama'], $aksi, $entitas, ..., $_SERVER['REMOTE_ADDR']]);
    } catch (\Throwable $e) { /* diabaikan */ }
}
```
**Artinya:**
> Fungsi `audit()` **mencatat satu aktivitas** ke tabel `audit_log`.
> - `global $pdo;` → "pinjam sambungan database `$pdo`" (yang dibuat di database.php).
> - `audit_ensure()` → **membuat tabel otomatis** kalau belum ada (jadi tak perlu SQL manual).
> - `INSERT INTO ... VALUES (?,?,...)` → menyimpan: siapa (`$u`), aksi apa, dari IP mana
>   (`$_SERVER['REMOTE_ADDR']`).
> - Tanda `?` lagi-lagi *prepared statement* (aman).
> - `try/catch` → kalau pencatatan gagal, **diabaikan** supaya tidak mengganggu operasi utama.

---

## 🎤 CARA MENJELASKAN 2 FILE INI KE DOSEN (singkat)

> "`database.php` tugasnya **membuka sambungan ke database** dan menyimpannya di variabel
> `$pdo`, yang dipakai seluruh halaman.
>
> `security.php` adalah **pusat keamanan & alat bantu** — berisi fungsi-fungsi seperti
> wajib login, pembatasan hak akses admin/karyawan, perlindungan CSRF dengan token,
> penyaringan output anti-XSS, dan pencatatan aktivitas (audit). Semua halaman memanggil
> dua file ini di awal, jadi keamanan terpusat dan konsisten."

## 🧠 5 ISTILAH PALING SERING DITANYA
1. **PDO** → alat PHP untuk bicara ke database (dengan aman).
2. **`$_SESSION`** → ingatan server soal user yang login.
3. **`$_POST`** → data yang dikirim dari formulir.
4. **`isset()`** → cek "ada/terisi atau tidak".
5. **Token CSRF** → tiket rahasia anti-pemalsuan formulir.

---

# 🔁 CARA BACA KODE DENGAN "JIKA … MAKA …"

Setiap `if (...)` di PHP sebenarnya berarti **"JIKA (syarat) MAKA (lakukan)"**.
Kalau kamu bisa membaca tiap baris seperti ini, kamu otomatis paham logikanya.
Berikut SEMUA logika di dua file, diterjemahkan ke "jika–maka".

### Dari `database.php`

```php
try { $pdo = new PDO(...); }
catch (PDOException $e) { die("Koneksi database gagal: ..."); }
```
> **JIKA** sambungan ke database **berhasil**,
> **MAKA** simpan sambungan itu di `$pdo` (siap dipakai).
>
> **JIKA** sambungan **gagal** (database mati / salah nama),
> **MAKA** tampilkan pesan "Koneksi database gagal" dan **hentikan** program.

### Dari `security.php`

```php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
```
> **JIKA** sesi belum dimulai,
> **MAKA** mulai sesi (aktifkan "kotak ingatan" `$_SESSION`).

```php
if (!defined('BASE_URL')) { define('BASE_URL', '/gudang'); }
```
> **JIKA** konstanta `BASE_URL` belum dibuat,
> **MAKA** buat sekarang berisi `/gudang`.

```php
// require_login()
if (!isset($_SESSION['user_id'])) {
    header("Location: .../login.php");
    exit;
}
```
> **JIKA** user **belum login** (tidak ada tanda `user_id` di ingatan),
> **MAKA** lempar ke halaman login lalu **stop**.
> *(JIKA sudah login, MAKA tidak terjadi apa-apa — lanjut buka halaman.)*

```php
// require_role()
if (($_SESSION['role'] ?? '') !== $role) {
    http_response_code(403);
    die("⛔ Akses ditolak...");
}
```
> **JIKA** peran user **bukan** peran yang diminta (mis. karyawan buka halaman admin),
> **MAKA** tolak akses (403) dan **hentikan**.

```php
// csrf_token()
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
```
> **JIKA** belum punya token rahasia,
> **MAKA** buatkan token acak baru lalu simpan.
> *(JIKA sudah punya, MAKA pakai yang lama.)*

```php
// check_csrf()
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    http_response_code(419);
    die("Token keamanan tidak valid...");
}
```
> **JIKA** formulir tidak mengirim token, **ATAU** token yang dikirim **tidak cocok**
> dengan token asli,
> **MAKA** tolak permintaan dan **hentikan** (cegah pemalsuan).
> *(Tanda `||` artinya "ATAU".)*

```php
// get_flash()
if (!empty($_SESSION['flash'])) {
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
return null;
```
> **JIKA** ada pesan notifikasi yang dititipkan,
> **MAKA** ambil pesannya, hapus (agar tampil sekali saja), lalu kembalikan.
> **JIKA tidak ada**, **MAKA** kembalikan kosong (`null`).

```php
// audit_ensure()
if (!isset($pdo)) return;
if ($done)       return;
```
> **JIKA** tidak ada sambungan database, **MAKA** berhenti (jangan lakukan apa-apa).
> **JIKA** tabel sudah pernah dipastikan ada, **MAKA** berhenti (tidak usah diulang).

```php
// audit()
if (!isset($pdo)) return;
try { ... }
catch (\Throwable $e) { /* diabaikan */ }
```
> **JIKA** tidak ada sambungan database, **MAKA** berhenti.
> **JIKA** saat mencatat aktivitas terjadi error, **MAKA** abaikan saja
> (operasi utama tetap jalan, audit tidak boleh mengganggu).

---

## 🎯 TRIK MENGHAFAL

Saat membaca kode apa pun, ubah `if` jadi pertanyaan **"JIKA apa?"** lalu lihat
isi `{ }` sebagai **"MAKA lakukan ini"**. Contoh cepat:

| Kode | Dibaca |
|---|---|
| `if (!isset($x))` | "JIKA `$x` tidak ada..." |
| `if (empty($x))` | "JIKA `$x` kosong..." |
| `if ($a !== $b)` | "JIKA `$a` tidak sama dengan `$b`..." |
| `if ($a == $b)` | "JIKA `$a` sama dengan `$b`..." |
| `... || ...` | "...ATAU..." |
| `... && ...` | "...DAN..." |
| `!` (tanda seru) | "TIDAK / BUKAN" |

Dengan trik ini, kamu bisa "membaca" hampir semua kode PHP seperti kalimat biasa. 😎
