# Panduan Instalasi

## Opsi 1: Jalankan dengan PHP built-in server

Syarat:

- PHP 8.1 atau lebih baru.
- Ekstensi PDO SQLite aktif.

Langkah:

```bash
cd fishmarket-pro
php -S localhost:8000 -t public
```

Buka `http://localhost:8000`.

## Opsi 2: Jalankan di XAMPP atau Laragon

1. Ekstrak folder `fishmarket-pro` ke folder web server.
2. Arahkan document root ke folder `public`.
3. Pastikan folder `storage` dapat ditulis oleh server.
4. Buka alamat lokal sesuai konfigurasi server.

## Opsi 3: Implementasi MySQL

Aplikasi demo ini memakai SQLite otomatis agar langsung berjalan. Struktur MySQL sudah tersedia untuk implementasi lanjutan.

1. Buat database dengan menjalankan:

```sql
source database/schema_mysql.sql;
source database/sample_data_mysql.sql;
```

2. Untuk produksi, sesuaikan `src/db.php` agar memakai DSN MySQL:

```php
$pdo = new PDO('mysql:host=localhost;dbname=fishmarket_pro;charset=utf8mb4', 'root', 'password');
```

3. Pada mode MySQL, inisialisasi otomatis SQLite di `schema.php` tidak perlu digunakan.

## Checklist setelah instalasi

- Login admin berhasil.
- Menu role muncul sesuai hak akses.
- Data ikan tampil dengan gambar SVG.
- Transaksi pemasukan dapat disimpan.
- Transaksi penjualan menolak stok yang tidak cukup.
- Laporan CSV dapat diunduh.
- Cetak nota dapat dibuka melalui tombol cetak.
