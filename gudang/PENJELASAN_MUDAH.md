# 🎈 PENJELASAN SUPER GAMPANG — SIGMA

Versi bahasa santai pakai perumpamaan sehari-hari. Buat yang baru belajar. 😄

---

## 🏪 SIGMA itu apa?

Bayangkan sebuah **toko/gudang elektronik**. Setiap hari ada barang **masuk** (kulakan)
dan **keluar** (terjual). SIGMA adalah **"buku catatan digital pintar"** yang:
- mencatat semua barang & stoknya,
- mencatat tiap barang masuk/keluar,
- membuat **nota (invoice)** otomatis,
- dan memantau semuanya lewat **dasbor**.

Semua lewat **website**, dibuka di browser.

---

## 🧩 Website ini terdiri dari 3 "bagian"

Bayangkan sebuah **restoran**:

| Bagian SIGMA | Diibaratkan | Tugasnya |
|---|---|---|
| **Database (MySQL)** | Gudang penyimpanan | Tempat semua data disimpan permanen |
| **PHP** | Koki di dapur | Mengolah data, menghitung, memutuskan |
| **HTML/CSS/JS** | Penyajian di meja | Yang dilihat & diklik pengguna |

Saat kamu klik tombol di website → pesananmu dikirim ke "koki PHP" → koki ambil/simpan
data di "gudang database" → hasilnya disajikan kembali ke layar. Begitu terus.

---

## 🔑 Konsep-konsep penting (pakai perumpamaan)

### 1. Password yang diacak (Hashing / bcrypt)
Saat kamu daftar, password **tidak disimpan apa adanya**. Password "diacak" jadi
kode rahasia yang **tidak bisa dibalik**.

> 🥚 Ibarat **telur diceplok**: gampang dari telur jadi ceplok, tapi mustahil
> mengembalikan ceplok jadi telur utuh. Jadi kalau pun data bocor, password asli tetap aman.

### 2. Tiket berstempel (CSRF Token)
Setiap formulir punya "tiket rahasia" tersembunyi. Saat kamu menyimpan data,
sistem cek dulu: "tiketnya asli atau palsu?"

> 🎫 Ibarat **tiket konser ber-hologram**. Kalau ada orang jahat dari situs lain
> mencoba "menitipkan" perintah palsu atas namamu, tiketnya tidak cocok → ditolak.

### 3. Kartu identitas (Session)
Setelah login, kamu dapat "gelang masuk". Selama gelang masih ada, sistem tahu
"oh ini si Budi, dia karyawan". Jadi kamu tidak perlu login ulang tiap pindah halaman.

> 🎟️ Ibarat **gelang masuk kolam renang** — sekali pakai, bebas masuk-keluar wahana.

### 4. Kunci kamar berbeda (Hak Akses / RBAC)
**Admin** dan **karyawan** punya "kunci" berbeda. Karyawan tidak bisa masuk ke
halaman admin (mis. kelola akun, laporan).

> 🔑 Ibarat **kartu hotel**: kartu tamu hanya buka kamarnya sendiri, kartu manajer
> bisa buka semua ruangan.

### 5. Formulir anti-curang (Prepared Statement)
Saat menyimpan data ke database, sistem pakai "formulir isian" yang ketat.
Data yang kamu ketik **tidak akan pernah** disalahartikan sebagai perintah.

> 📝 Ibarat **formulir bank**: kolom "nama" hanya dibaca sebagai nama, mau kamu tulis
> apa pun di situ tidak bisa jadi "perintah" ke sistem. Ini mencegah pembobolan
> (istilahnya *SQL injection*).

### 6. Transaksi "semua atau batal" (ACID Transaction) ⭐ PENTING
Saat barang terjual, ada **2 langkah** yang harus terjadi bersamaan:
1. catat penjualannya, **dan** 2. kurangi stoknya.

Sistem memastikan **keduanya berhasil, atau dua-duanya batal**. Tidak ada yang setengah jadi.

> 🏧 Ibarat **transfer ATM**: uang keluar dari rekeningmu DAN masuk ke rekening tujuan.
> Kalau di tengah jalan mati listrik, transaksi **dibatalkan total** — uangmu tidak
> hilang entah ke mana. Sama: stok tidak akan kacau walau ada error.

### 7. Mengunci barang saat melayani (`FOR UPDATE`) ⭐ PENTING
Kalau 2 kasir kebetulan menjual barang yang **sama** di **detik yang sama**, bisa
kacau (stok kebaca dobel). Maka saat satu kasir memproses, barang itu **"dikunci"
sebentar**, kasir lain menunggu giliran.

> 🛒 Ibarat **kasir minimarket**: saat kamu sedang dilayani, barang di tanganmu
> "milikmu dulu" — orang lain tidak bisa ambil yang sama di saat bersamaan. Jadi
> stok tidak pernah salah hitung.

### 8. CCTV / buku tamu (Audit Trail)
Setiap perubahan penting (tambah barang, hapus, batalkan invoice, login) **dicatat
otomatis**: siapa, apa, kapan, dari komputer mana.

> 📹 Ibarat **CCTV toko**. Kalau ada yang janggal, tinggal "putar rekaman":
> "oh, jam 2 siang si Budi yang menghapus barang ini."

### 9. Pelayan yang sat-set (AJAX)
Beberapa hal terjadi **tanpa memuat ulang halaman** — misal saat mengetik barcode,
sistem langsung cek "sudah ada belum?" secara real-time.

> 🍽️ Ibarat **pelayan yang ambilkan info ke dapur tanpa menutup restoran**. Kamu
> tetap duduk, infonya datang sendiri.

---

## 📄 Tiap halaman ngapain? (versi gampang)

| Halaman | Tugasnya (bahasa manusia) |
|---|---|
| **Login** | Pintu masuk — cek nama & password |
| **Dashboard** | Papan ringkasan — "hari ini berapa transaksi, stok apa yang menipis" |
| **Data Barang** | Buku katalog — daftar semua produk, bisa tambah/edit/hapus + foto |
| **Stok Masuk** | Catat barang datang (kulakan) → stok bertambah |
| **Stok Keluar** | Catat barang terjual (keranjang belanja) → buat nota, stok berkurang |
| **Invoice** | Nota penjualan yang bisa dicetak/jadi PDF |
| **Cetak Label** | Bikin stiker barcode untuk ditempel ke produk |
| **Laporan** (admin) | Rekap penjualan per tanggal + export Excel/CSV |
| **Kelola Karyawan** (admin) | Tambah/hapus akun pegawai |
| **Log Aktivitas** (admin) | "Rekaman CCTV" semua aktivitas |

---

## 🎤 Kalau dosen bertanya, jawab santai begini:

**"Aman dari peretas?"**
> "Password diacak (tidak bisa dibalik), tiap formulir ada tiket rahasia anti-pemalsuan,
> dan data yang diketik tidak bisa jadi perintah ke database. Jadi cukup aman."

**"Kalau 2 orang jual barang sama bersamaan, stok kacau tidak?"**
> "Tidak. Saya pakai sistem 'kunci sementara' — saat satu transaksi jalan, barangnya
> dikunci dulu, yang lain menunggu. Jadi stok tidak pernah salah hitung."

**"Kalau di tengah transaksi error?"**
> "Sistemnya 'semua atau batal' — seperti transfer ATM. Kalau gagal di tengah,
> semuanya dibatalkan, data tetap rapi."

**"Siapa yang bisa akses apa?"**
> "Ada 2 peran: admin bisa semua, karyawan hanya operasional harian. Seperti kartu
> hotel tamu vs manajer."

**"Kalau ada data hilang/janggal, bisa dilacak?"**
> "Bisa. Ada log aktivitas — semua perubahan tercatat otomatis: siapa, apa, kapan."

---

## 😎 Intinya untuk dipresentasikan

SIGMA itu **buku catatan gudang digital** yang:
1. **Cepat** — scan barcode, langsung kebuat nota.
2. **Aman** — password diacak, anti-pembobolan, hak akses bertingkat.
3. **Andal** — stok tidak pernah salah hitung (sistem "kunci" & "semua-atau-batal").
4. **Bisa dilacak** — semua aktivitas ada rekamannya.
5. **Modern** — ada mode gelap, pencarian cepat (Ctrl+K), notifikasi stok menipis.

Tenang, kamu pasti bisa! 💪
