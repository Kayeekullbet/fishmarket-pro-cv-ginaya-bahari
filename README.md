# CV Ginaya Bahari FishMarket

Website sistem informasi transaksi ikan untuk CV Ginaya Bahari berbasis ERD nelayan, pemasukan ikan, detail pemasukan, ikan, harga ikan, pembeli, penjualan, dan detail penjualan.

Aplikasi ini dibuat sebagai prototipe web fungsional berbasis PHP native + SQLite otomatis agar mudah dijalankan tanpa Composer dan tanpa setup database manual. File SQL MySQL tetap disediakan di folder `database/` untuk implementasi server MySQL.

## Fitur utama

- Login berbasis role dengan pilihan role pada halaman login.
- Role: Admin, Owner, Petugas Pembelian, Petugas Penjualan, Petugas Gudang, Auditor.
- Dashboard interaktif dengan statistik transaksi, stok, ranking ikan, grafik CSS, dan animasi.
- CRUD master data: nelayan, ikan, harga ikan, pembeli.
- Transaksi pemasukan ikan dengan multi item.
- Transaksi penjualan ikan dengan validasi stok otomatis.
- Nota penjualan dan bukti pemasukan siap cetak.
- Stok ikan otomatis dari pemasukan dikurangi penjualan aktif.
- Laporan periode dan export CSV.
- Manajemen user dan pengaturan profil usaha.
- UI responsif untuk desktop, tablet, dan mobile.
- Animasi micro-interaction pada tombol, menu, kartu, dan login.
- Gambar ikan real: sebagian tersimpan lokal, sebagian diambil dari Wikimedia Commons melalui URL langsung agar jenis ikan tambahan tetap tampil realistis.

## Akun demo

| Role | Username | Password |
|---|---|---|
| Admin | admin | admin123 |
| Owner | owner | owner123 |
| Petugas Pembelian | beli | beli123 |
| Petugas Penjualan | jual | jual123 |
| Petugas Gudang | gudang | gudang123 |
| Auditor | auditor | auditor123 |

## Cara menjalankan cepat

```bash
cd fishmarket-pro
php -S localhost:8000 -t public
```

Buka browser:

```text
http://localhost:8000
```

SQLite akan dibuat otomatis di `storage/fishmarket.sqlite` saat aplikasi pertama kali dibuka.

## Struktur folder

```text
fishmarket-pro/
├── public/
│   ├── index.php
│   └── assets/
│       ├── css/style.css
│       ├── js/app.js
│       └── img/*.svg
├── src/
│   ├── auth.php
│   ├── db.php
│   ├── domain.php
│   ├── helpers.php
│   ├── layout.php
│   ├── pages.php
│   └── schema.php
├── database/
│   ├── schema_mysql.sql
│   └── sample_data_mysql.sql
├── tests/
│   └── smoke.php
└── storage/
```

## Catatan desain database

Perbaikan dari ERD awal:

1. Kolom `id_ikan` pada `detail_penjualan` diperbaiki menjadi foreign key ke tabel `ikan`.
2. Kolom harga, subtotal, dan total menggunakan konsep `DECIMAL` pada SQL MySQL. Pada SQLite runtime, tipe numeric disimpan sebagai `REAL` karena SQLite bersifat dynamic typing.
3. Tabel `harga_ikan` diberi `jenis_harga`, `tanggal_berlaku`, dan `status_aktif` agar sistem memiliki riwayat harga.
4. Stok dihitung dari detail pemasukan dikurangi detail penjualan dengan status bukan `dibatalkan`.
5. Total transaksi dihitung dari detail item ketika transaksi disimpan.

## Pengujian cepat

```bash
php tests/smoke.php
```

Pengujian ini melakukan inisialisasi database, membaca dashboard, memeriksa stok, dan menguji validasi stok saat penjualan melebihi ketersediaan.


## Data lapangan yang dimasukkan

Data contoh sudah disesuaikan dengan informasi wawancara CV Ginaya Bahari. Jenis ikan dari wawancara meliputi Sunu, Tidar, Tanete, dan Terapung. Sistem juga ditambah jenis ikan umum seperti Tuna Sirip Kuning, Cakalang, Tongkol, Kakap Merah, Kerapu Macan, Bandeng, Tenggiri, Kembung, Layang, Baronang, Kuwe/Bubara, Bawal Putih, Lemuru, dan Teri.

Lokasi usaha diisi berdasarkan tangkapan layar pengguna: Dekat Gusung, Kecamatan Ujung Tanah, Kota Makassar, Sulawesi Selatan, dengan koordinat -5.110385, 119.420455. Catatan lengkap ada di `CATATAN_DATA_CV_GINAYA_BAHARI.md`.
