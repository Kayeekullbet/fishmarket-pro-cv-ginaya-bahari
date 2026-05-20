# Checklist Hasil Uji

Tanggal build: 2026-05-20

## Pemeriksaan yang dilakukan

- PHP syntax lint untuk seluruh file `.php`: lulus.
- JavaScript syntax check untuk `public/assets/js/app.js`: lulus.
- Struktur folder dan asset SVG: tersedia.
- SQL MySQL: tersedia pada `database/schema_mysql.sql` dan `database/sample_data_mysql.sql`.
- Smoke test aplikasi: disediakan pada `tests/smoke.php`.

## Catatan lingkungan build

PHP CLI pada lingkungan build ini hanya memiliki modul `PDO` tanpa driver `pdo_sqlite`, sehingga smoke test penuh tidak dapat dijalankan di container ini. Aplikasi sudah dilengkapi `Dockerfile` dan `docker-compose.yml` yang mengaktifkan `pdo_sqlite` melalui image PHP Apache agar runtime dapat berjalan lebih konsisten.

Perintah uji yang sudah dijalankan:

```bash
find /mnt/data/fishmarket-pro -name '*.php' -print0 | xargs -0 -n1 php -l
node --check /mnt/data/fishmarket-pro/public/assets/js/app.js
cd /mnt/data/fishmarket-pro && php tests/smoke.php
```

Hasil smoke test pada container build:

```text
SKIP: PDO SQLite driver tidak aktif pada PHP CLI ini. Jalankan via Docker atau aktifkan pdo_sqlite untuk smoke test penuh.
```

## Rekomendasi pengujian setelah ekstrak ZIP

Jalankan via Docker:

```bash
docker compose up --build
```

Buka:

```text
http://localhost:8080
```

Lalu cek manual:

1. Login sebagai admin.
2. Buka dashboard.
3. Tambah data nelayan.
4. Tambah data ikan.
5. Tambah harga ikan.
6. Tambah transaksi pemasukan.
7. Tambah transaksi penjualan.
8. Coba input penjualan lebih besar dari stok, sistem harus menolak.
9. Cetak nota penjualan.
10. Export laporan CSV.


## Patch responsif v2

Perbaikan dilakukan pada `public/assets/css/style.css` setelah ditemukan form Manajemen User terlalu menyempit pada layar laptop/resolusi menengah.

Yang diperbaiki:
- Grid halaman `grid-2.unequal` dibuat turun menjadi satu kolom pada lebar layar sampai 1440px agar form dan tabel tidak saling menekan.
- Form dua kolom sekarang memakai `auto-fit` dan `minmax(min(100%, 240px), 1fr)`, sehingga otomatis berubah menjadi satu kolom ketika ruang card sempit.
- Tabel diberi `min-width` dan dibungkus `table-responsive`, sehingga tabel tidak memaksa kolom menjadi gepeng.
- Card, grid item, label form, dan table wrapper diberi `min-width: 0` untuk mencegah overflow grid.
- Tombol dibuat tidak pecah teks, dan pada layar kecil tombol form menjadi full width.

Hasil pengecekan setelah patch:
- Semua file PHP lolos `php -l`.
- JavaScript lolos `node --check`.

## Update Foto Real Ikan

- Semua referensi gambar ikan demo sudah dipindahkan dari SVG ilustratif ke JPG foto real.
- File baru: `fish-tuna.jpg`, `fish-cakalang.jpg`, `fish-tongkol.jpg`, `fish-kakap.jpg`, `fish-kerapu.jpg`, dan `fish-bandeng.jpg`.
- Tampilan kartu ikan, tabel stok, mini list dashboard, login showcase, dan hero dashboard sudah disesuaikan agar foto real tidak pecah atau gepeng.
- Ditambahkan helper `fish_image()` agar database lama yang masih menyimpan nama file `.svg` tetap otomatis diarahkan ke foto `.jpg`.
- Atribusi lisensi foto sudah disimpan di `ATRIBUSI_FOTO_IKAN.md`.
- Lint PHP: seluruh file PHP lolos `php -l`.
- Syntax JavaScript: `public/assets/js/app.js` lolos `node --check`.
