# SIGMA — Product Document

**Sistem Inventory Gudang & Manajemen**
Version 1.0 · PT Wahyu Makmur Abadi, Kudus

---

## Overview

SIGMA adalah sistem manajemen inventaris gudang berbasis web yang dirancang khusus untuk operasional gudang elektronik skala menengah. Sistem berjalan sepenuhnya secara lokal (offline-first) di atas LAMPP/XAMPP — tidak membutuhkan koneksi internet, tidak bergantung pada layanan cloud, dan tidak mengirimkan data ke luar jaringan.

Filosofi desain produk ini adalah **zero-friction operations**: setiap alur kerja dari scan barcode hingga cetak invoice dapat diselesaikan dalam hitungan detik, bukan menit.

---

## Target Pengguna

| Peran | Tanggung Jawab |
|---|---|
| **Admin** | Kelola akun karyawan, pantau laporan, lihat semua metrik |
| **Karyawan** | Catat transaksi masuk/keluar, kelola data barang, cetak label & invoice |

Kedua peran mengakses sistem melalui browser. Admin mendapat akses ke seluruh fitur; karyawan hanya ke fitur operasional sehari-hari.

---

## Fitur Inti

### 1. Autentikasi & Keamanan

- Login dengan username dan password (hash bcrypt via `password_hash`)
- Session dengan `session_regenerate_id()` setelah login berhasil
- CSRF token pada setiap form POST
- Role-based access control: `require_role('admin')` memblokir akses karyawan ke halaman admin
- Output di-escape secara konsisten via helper `e()` (anti-XSS)
- Redirect otomatis setelah login berdasarkan role

### 2. Dashboard Admin

Empat KPI card di bagian atas:
- **Jenis Barang** — jumlah produk unik terdaftar
- **Total Stok** — akumulasi semua unit di gudang
- **Transaksi Hari Ini** — gabungan masuk + keluar pada tanggal berjalan
- **Total Karyawan** — jumlah akun dengan role karyawan

Dua visualisasi grafik:
- **Tren Transaksi** (14 hari) — bar chart dua seri: masuk (emerald) dan keluar (rose)
- **Stok per Kategori** — doughnut chart dengan cutout 64%

Dua panel informasi:
- **Perlu Restock** — barang dengan stok ≤5 unit, diurutkan dari yang paling sedikit; baris merah bila stok = 0
- **Aktivitas Terbaru** — 6 transaksi terakhir (masuk/keluar) dengan timestamp

### 3. Dashboard Karyawan

Tiga KPI card (Jenis Barang, Total Stok, Transaksi Hari Ini) dan tiga tombol aksi cepat:
- Tambah Barang → `/karyawan/barang.php`
- Barang Masuk → `/karyawan/stok_masuk.php`
- Barang Keluar → `/karyawan/stok_keluar.php`

Panel transaksi terbaru identik dengan milik admin.

### 4. Data Barang

Katalog master produk. Setiap barang memiliki:

| Field | Keterangan |
|---|---|
| `kode_barcode` | Unik, divalidasi di server dan via AJAX real-time |
| `nama_barang` | Nama produk |
| `merk` | Opsional |
| `id_kategori` | Foreign key ke tabel `kategori` |
| `satuan` | Default: Pcs |
| `harga` | Dipakai untuk perhitungan invoice |
| `gambar` | JPG/JPEG/PNG, maks 2MB, disimpan sebagai `B_{barcode}.{ext}` |
| `stok_sekarang` | Read-only di form ini; hanya berubah lewat transaksi |

Stok awal selalu 0. Barang baru tidak bisa memiliki stok awal di luar alur Stok Masuk — ini mencegah inkonsistensi data.

Fitur pencarian live (GET `?q=`) mencakup nama, barcode, dan merk. Kamera scanner (Html5-QrCode) tersedia di dalam modal tambah/edit.

### 5. Stok Masuk

Alur kerja:
1. Scan barcode via kamera atau ketik manual → sistem otomatis memilih barang dari dropdown
2. Pilih jumlah, tanggal, dan keterangan (opsional)
3. Submit → ACID transaction: INSERT ke `stok_masuk` + UPDATE `stok_sekarang` dalam satu transaksi

**Void (Batalkan):** Mengurangi `stok_sekarang` sebesar jumlah transaksi, lalu DELETE baris dari `stok_masuk`. Sistem memvalidasi bahwa stok saat ini tidak akan menjadi negatif sebelum mengeksekusi void.

Riwayat menampilkan 15 transaksi terbaru.

### 6. Stok Keluar & Invoice

Sistem keranjang belanja (cart) berbasis JavaScript:
1. Scan/pilih barang → masukkan jumlah → klik Tambah
2. Sistem memvalidasi stok secara client-side (mencegah over-order)
3. Isi detail: nama pelanggan/tujuan, tanggal, keterangan (semua opsional)
4. Klik "Proses & Buat Invoice"

Server-side:
- Validasi ulang stok dengan `FOR UPDATE` (row-level lock)
- Generate nomor invoice: `INV-YYYYMMDD-XXXX` (sequential harian, diisi nol sampai 4 digit)
- ACID transaction untuk semua item sekaligus — jika satu item gagal, seluruh transaksi dibatalkan

**Void Invoice:** Mengembalikan stok semua item dalam invoice, lalu DELETE semua baris invoice tersebut. Tidak ada partial void.

### 7. Invoice View

Halaman cetak/PDF untuk invoice yang sudah dibuat. Menampilkan:
- Header perusahaan (PT Wahyu Makmur Abadi)
- Nomor invoice, tanggal, pelanggan, petugas
- Tabel item dengan qty, harga satuan, subtotal
- Total keseluruhan
- Kolom tanda tangan petugas

CSS `@media print` menyembunyikan sidebar, topbar, dan tombol aksi — hanya dokumen invoice yang tercetak.

### 8. Cetak Label Barcode

Menggunakan JsBarcode untuk menghasilkan label barcode yang bisa dicetak dan ditempel langsung ke produk fisik.

### 9. Laporan (Admin)

Filter berdasarkan rentang tanggal (`from` → `to`). Default: bulan berjalan.

Lima KPI:
- Barang Masuk (unit)
- Barang Keluar (unit)
- Nilai Penjualan (Rp) + jumlah invoice
- Nilai Persediaan saat ini (snapshot tidak difilter tanggal)

Dua panel:
- **Barang Terlaris** — top 5 produk berdasarkan qty keluar, ditampilkan dengan progress bar relatif
- **Rekap Invoice** — tabel semua invoice dalam periode, footer menampilkan total

Export: CSV langsung via PHP (`Content-Disposition: attachment`). Print: `window.print()` dengan print stylesheet.

### 10. Kelola Karyawan (Admin)

Manajemen akun pengguna dengan role karyawan. Admin dapat membuat, mengedit, dan menghapus akun.

---

## Business Rules

**Stok:**
- Stok hanya berubah melalui transaksi resmi (Stok Masuk / Stok Keluar)
- Void stok masuk tidak diizinkan jika stok saat ini lebih kecil dari jumlah transaksi
- Stok tidak bisa negatif
- Threshold "Perlu Restock": ≤5 unit; "Habis": 0 unit

**Invoice:**
- Format nomor: `INV-{YYYYMMDD}-{NNNN}` (urut per hari, reset setiap hari)
- Void invoice mengembalikan 100% stok semua item; tidak ada partial void
- Satu invoice bisa berisi banyak jenis barang

**Barcode:**
- Harus unik per barang (divalidasi server + AJAX real-time)
- Digunakan sebagai kunci lookup di semua scanner kamera

**Gambar:**
- Format: JPG, JPEG, PNG
- Ukuran maks: 2MB
- Nama file: `B_{barcode_sanitized}.{ext}`
- Gambar lama dihapus otomatis saat diganti

---

## Arsitektur Teknis

```
/gudang
├── config/
│   ├── database.php     PDO connection + BASE_URL
│   └── security.php     Session, CSRF, RBAC helpers
├── includes/
│   ├── header.php       Shell: sidebar + topbar + flash
│   └── footer.php       Close layout + extra JS injection
├── admin/
│   ├── dashboard.php
│   ├── karyawan.php
│   └── laporan.php
├── karyawan/
│   ├── dashboard.php
│   ├── barang.php
│   ├── stok_masuk.php
│   ├── stok_keluar.php
│   ├── invoice_view.php
│   ├── label.php
│   └── ajax/
│       ├── cari_barang.php
│       └── cek_barcode.php
├── assets/
│   ├── css/style.css
│   ├── js/             Tailwind, Lucide, Chart.js, Html5QrCode, JsBarcode (semua lokal)
│   └── uploads/        Foto produk
├── index.php            Landing page publik
└── login.php / logout.php
```

Stack: PHP 8+ · MySQL (PDO) · Tailwind CSS (bundled) · Lucide Icons · Chart.js · Html5-QrCode · JsBarcode

Tidak ada dependency npm atau composer. Semua library disimpan lokal di `assets/js/`. Sistem berjalan tanpa internet.

---

## Batasan Saat Ini

- Tidak ada pagination pada tabel riwayat (dibatasi hardcoded LIMIT 15)
- Tidak ada audit trail permanen untuk perubahan data barang (hanya `created_at`)
- Invoice void tidak menyimpan riwayat pembatalan
- Tidak ada fitur lupa password / reset password
- Pencarian barang di halaman Stok Keluar tidak dipersistensikan di URL
- Satu gudang per instalasi (tidak multi-warehouse)
