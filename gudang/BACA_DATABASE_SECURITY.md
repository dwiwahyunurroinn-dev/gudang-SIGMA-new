# 📖 BACA KODE LENGKAP: `database.php` & `security.php`

Format: **potongan kode asli** → lalu **"Dibaca"** (pakai pola JIKA…MAKA bila ada syarat).
Lengkap baris demi baris. Cocok dibaca sambil menunjuk layar. 😊

> Catatan: `if (...)` selalu berarti **"JIKA (syarat) MAKA (lakukan isi { })"**.

---

# 📄 FILE 1: `config/database.php`

```php
<?php
```
📖 **Dibaca:** "Tanda **mulai kode PHP**. Semua perintah PHP ditulis setelah ini."

```php
define('BASE_URL', '/gudang');
```
📖 **Dibaca:** "Buat **konstanta** `BASE_URL` berisi `/gudang` (nama folder aplikasi).
Konstanta = nilai tetap, dipakai untuk menyusun alamat link di seluruh aplikasi."

```php
$host   = 'localhost';
$dbname = 'gudang_db';
$user   = 'root';
$pass   = '';
```
📖 **Dibaca:** "Siapkan 4 kotak (variabel) berisi **info untuk masuk ke database**:
alamat server (`localhost` = komputer ini sendiri), nama database (`gudang_db`),
username (`root`), dan password (kosong)."

```php
try {
```
📖 **Dibaca:** "**COBA** jalankan kode berikut. **JIKA** di dalamnya terjadi error,
**MAKA** lompat ke bagian `catch` di bawah."

```php
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [ /* pengaturan di bawah */ ]
    );
```
📖 **Dibaca:** "**Buka sambungan** ke database MySQL memakai info tadi, lalu simpan
sambungannya di kotak `$pdo`. Inilah **'telepon' ke database** yang dipakai semua halaman.
(`charset=utf8mb4` = dukung semua karakter termasuk emoji.)"

```php
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
```
📖 **Dibaca:** "Atur: **JIKA** terjadi masalah database, **MAKA** laporkan dengan jelas
(bukan diam-diam) — supaya error mudah ketahuan."

```php
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
```
📖 **Dibaca:** "Atur: **JIKA** mengambil data, **MAKA** susun memakai **nama kolom**,
contoh `$row['nama_barang']`. Jadi mudah dibaca."

```php
        PDO::ATTR_EMULATE_PREPARES => false,
```
📖 **Dibaca:** "Atur: pakai *prepared statement* **asli** dari database (lebih aman dari
pembobolan/SQL injection)."

```php
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
```
📖 **Dibaca:** "**JIKA** sambungan tadi **GAGAL** (database mati / salah nama),
**MAKA** tampilkan pesan *'Koneksi database gagal'* beserta sebabnya, lalu **hentikan**
program. (Inilah pesan yang sempat muncul saat MySQL belum siap.)"

**Kesimpulan file ini:** *"Buka telepon ke database, simpan di `$pdo`. JIKA gagal, hentikan."*

---

# 📄 FILE 2: `config/security.php`

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```
📖 **Dibaca:** "**JIKA** sesi belum dimulai, **MAKA** mulai sesi sekarang.
`session_start()` mengaktifkan `$_SESSION` = **'kotak ingatan' server** tentang siapa
yang login. Tanpa ini, sistem lupa user tiap pindah halaman."

```php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gudang');
}
```
📖 **Dibaca:** "**JIKA** konstanta `BASE_URL` belum dibuat, **MAKA** buat sekarang.
(Pengaman, kalau file ini dipanggil tanpa `database.php`.) Tanda `!` artinya **'tidak/belum'**."

---

### 🔒 Fungsi `require_login` — wajib login

```php
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}
```
📖 **Dibaca:**
> "Ini fungsi **'halaman wajib login'**.
> **JIKA** user **belum login** (tidak ada tanda `user_id` di ingatan),
> **MAKA** lempar ke halaman login (`header Location`) lalu **stop** (`exit`).
> **JIKA sudah login**, **MAKA** tidak terjadi apa-apa — halaman lanjut dibuka."

---

### 🔑 Fungsi `require_role` — wajib peran tertentu

```php
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("⛔ Akses ditolak. Halaman ini khusus untuk: " . htmlspecialchars($role));
    }
}
```
📖 **Dibaca:**
> "Fungsi **'halaman khusus peran tertentu'**.
> Baris 1: pastikan **login dulu** (`require_login`).
> Lalu: **JIKA** peran user **bukan** peran yang diminta (`!==` = tidak sama dengan) —
> misal karyawan membuka halaman admin —
> **MAKA** tolak dengan kode 403 dan **hentikan** (`die`)."

---

### 🎫 Bagian CSRF — anti pemalsuan formulir

```php
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
```
📖 **Dibaca:**
> "Fungsi pembuat **token rahasia**.
> **JIKA** belum punya token (`empty`), **MAKA** buatkan token acak baru
> (`random_bytes(32)` = 32 byte acak, `bin2hex` = ubah jadi teks) dan simpan.
> Terakhir, **kembalikan** token itu (`return`)."

```php
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
```
📖 **Dibaca:**
> "Membuat sepotong HTML berisi token tadi (tersembunyi), untuk **ditempel di formulir**.
> Tidak ada syarat di sini — langsung mengembalikan hasilnya."

```php
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        die("Token keamanan tidak valid. Silakan muat ulang halaman.");
    }
}
```
📖 **Dibaca:**
> "Pemeriksa token saat formulir dikirim.
> **JIKA** formulir **tidak mengirim** token (`!isset`) **ATAU** (`||`) token yang dikirim
> **tidak cocok** dengan token asli (`!hash_equals`),
> **MAKA** tolak permintaan (kode 419) dan **hentikan**.
> Ini mencegah situs lain memalsukan perintah atas nama user."

---

### 🛡️ Helper `e` — anti-XSS

```php
function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
```
📖 **Dibaca:**
> "Fungsi pendek `e()` dipakai **setiap menampilkan data dari user**.
> `htmlspecialchars` mengubah karakter berbahaya (seperti `<` `>`) jadi teks biasa,
> jadi **kode jahat tidak bisa jalan** (serangan XSS dicegah).
> `$str ?? ''` = JIKA `$str` kosong, pakai teks kosong (biar tidak error)."

---

### 👤 Helper `current_user` — siapa yang login

```php
function current_user(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'nama' => $_SESSION['nama']    ?? '',
        'role' => $_SESSION['role']    ?? '',
    ];
}
```
📖 **Dibaca:**
> "Mengembalikan **data user yang sedang login** (id, nama, role) dari ingatan `$_SESSION`.
> Tanda `??` artinya: **JIKA** datanya tidak ada, **MAKA** pakai nilai cadangan
> (`null` atau teks kosong) agar tidak error."

---

### 💬 Helper flash — pesan notifikasi sekali tampil

```php
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
```
📖 **Dibaca:**
> "**Menitipkan pesan** (mis. tipe `success` + teks 'Berhasil disimpan') ke ingatan,
> untuk ditampilkan di halaman berikutnya."

```php
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
```
📖 **Dibaca:**
> "**JIKA** ada pesan yang dititipkan (`!empty`),
> **MAKA** ambil pesannya (`$f`), **hapus** dari ingatan (`unset`) supaya hanya tampil
> **sekali**, lalu kembalikan pesannya.
> **JIKA tidak ada**, **MAKA** kembalikan kosong (`null`)."

---

### 📹 Audit — pencatatan aktivitas

```php
function audit_ensure(): void {
    global $pdo;
    if (!isset($pdo)) return;
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log ( ... )");
    $done = true;
}
```
📖 **Dibaca:**
> "Fungsi pemastian tabel audit.
> `global $pdo;` = pinjam 'telepon' database yang dibuat di database.php.
> **JIKA** tidak ada sambungan database (`!isset`), **MAKA** berhenti (`return`).
> **JIKA** tabel sudah pernah dipastikan (`$done` true), **MAKA** berhenti (tidak diulang).
> Kalau lolos: **buat tabel `audit_log` JIKA belum ada**, lalu tandai sudah selesai."

```php
function audit(string $aksi, string $entitas, string $deskripsi = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        audit_ensure();
        $u = current_user();
        $pdo->prepare("INSERT INTO audit_log (...) VALUES (?,?,?,?,?,?)")
            ->execute([$u['id'], $u['nama'], $aksi, $entitas, ..., $_SERVER['REMOTE_ADDR']]);
    } catch (\Throwable $e) {
        // diabaikan
    }
}
```
📖 **Dibaca:**
> "Fungsi **mencatat satu aktivitas** ke tabel audit.
> **JIKA** tidak ada sambungan database, **MAKA** berhenti.
> Kalau lanjut: pastikan tabel ada, ambil data user, lalu **simpan catatan**:
> siapa, aksi apa, dari IP mana (`$_SERVER['REMOTE_ADDR']`). Tanda `?` = prepared statement (aman).
> **JIKA** saat mencatat terjadi error (`catch`), **MAKA** abaikan saja —
> karena audit **tidak boleh mengganggu** operasi utama (simpan/hapus tetap jalan)."

---

# 🎯 RINGKASAN POLA

| Kode | Cara baca |
|---|---|
| `if (syarat) { ... }` | **JIKA** syarat benar **MAKA** lakukan isi `{ }` |
| `!` | TIDAK / BUKAN / BELUM |
| `\|\|` | ATAU |
| `&&` | DAN |
| `!==` | tidak sama dengan |
| `??` | JIKA kiri tidak ada, pakai kanan |
| `return` | kembalikan/serahkan hasil |
| `exit` / `die()` | hentikan program di sini |
| `try { } catch { }` | COBA; JIKA error, tangani di catch |

Dengan pola ini, kamu bisa "membaca" kode seperti kalimat sehari-hari. 🚀
