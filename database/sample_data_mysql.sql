USE fishmarket_pro;

INSERT INTO roles (nama_role, keterangan) VALUES
('admin', 'Mengelola seluruh fitur sistem.'),
('owner', 'Melihat dashboard dan laporan manajerial.'),
('pembelian', 'Mengelola pemasukan ikan dari nelayan.'),
('penjualan', 'Mengelola penjualan ikan kepada pembeli.'),
('gudang', 'Mengawasi stok dan mutasi ikan.'),
('auditor', 'Melihat data dan laporan tanpa perubahan data.');

INSERT INTO users (id_role, nama_user, username, email, password, status) VALUES
(1, 'Administrator Sistem', 'admin', 'admin@ginayabahari.local', '$2y$12$VlezbEb4PehN/sIvR5pbNegConiFrW1UpqiCqueiGGiEba9TG3RBW', 'aktif'),
(2, 'Haji Sumarni', 'owner', 'owner@ginayabahari.local', '$2y$12$0TO8/Y6BXk3.jjaTTCw3iO2w0xgyIZwY.pdv7pFcC7eXF4HUdEQci', 'aktif'),
(3, 'Sukma - Petugas Pemasukan', 'beli', 'beli@ginayabahari.local', '$2y$12$tGM6zq4ltgutC43j4sqhhuiFpdUS00rqOUWou3gLHjbpgSql6cV/y', 'aktif'),
(4, 'Petugas Penjualan', 'jual', 'jual@ginayabahari.local', '$2y$12$p0BIXlH42XhRT3svMBxKEu2tWwK.QruVdHBIKZ6HxIG6uxa7LWTyG', 'aktif'),
(5, 'Petugas Gudang', 'gudang', 'gudang@ginayabahari.local', '$2y$12$ipz9Y3FPy8cGGo9W7fR8c.DhiIN1qoAUKe8QwdHhtUBGHw4iJroX6', 'aktif'),
(6, 'Auditor Internal', 'auditor', 'auditor@ginayabahari.local', '$2y$12$44.2dUs9GHl3A2urVQ1e8.Z4YqMaHmPwOSB35NRe1w38UpoA3yto2', 'aktif');

INSERT INTO nelayan (nama_nelayan, nomor_telepon, alamat) VALUES
('Nelayan Pulau Gusung', '-', 'Pemasok ikan dari wilayah pulau sekitar Makassar'),
('Nelayan Pulau-pulau Makassar', '-', 'Nelayan langganan CV Ginaya Bahari'),
('Nelayan Dermaga Ujung Tanah', '-', 'Pemasok dari area pendaratan ikan Ujung Tanah'),
('Nelayan Mitra Harian', '-', 'Mitra yang rutin membawa hasil tangkapan ke lokasi usaha');

INSERT INTO ikan (nama_ikan, jenis_ikan, keterangan, gambar) VALUES
('Sunu', 'Karang/Premium', 'Jenis utama dari wawancara. Harga jual contoh Rp260.000/kg dan dapat berubah sesuai kualitas serta pasar.', 'fish-sunu.jpg'),
('Tidar', 'Nama lokal/Premium', 'Nama lokal dari wawancara. Harga contoh sekitar Rp400.000/kg. Identifikasi visual perlu divalidasi dengan data lapangan.', 'fish-tidar.jpg'),
('Tanete', 'Nama lokal/Pasar', 'Nama lokal dari wawancara. Harga contoh sekitar Rp45.000/kg.', 'fish-tanete.jpg'),
('Terapung', 'Nama lokal/Pasar', 'Nama lokal dari wawancara. Digunakan sebagai item operasional karena sering disebut dalam pemasukan.', 'fish-terapung.jpg'),
('Tuna Sirip Kuning', 'Pelagis Besar', 'Komoditas besar untuk kebutuhan pabrik dan pasar grosir.', 'fish-tuna.jpg'),
('Cakalang', 'Pelagis', 'Produk umum untuk pengolahan dan distribusi harian.', 'fish-cakalang.jpg'),
('Tongkol', 'Pelagis', 'Permintaan stabil untuk pasar harian.', 'fish-tongkol.jpg'),
('Kakap Merah', 'Demersal', 'Ikan bernilai tinggi untuk restoran, pabrik, dan pembeli umum.', 'fish-kakap.jpg'),
('Kerapu Macan', 'Karang', 'Komoditas premium bernilai tinggi.', 'fish-kerapu.jpg'),
('Bandeng', 'Budidaya/Pasar', 'Produk ekonomis untuk pasar lokal.', 'fish-bandeng.jpg'),
('Tenggiri', 'Pelagis Besar', 'Sering digunakan untuk olahan dan pembeli grosir.', 'fish-tenggiri.jpg'),
('Kembung', 'Pelagis Kecil', 'Ikan pasar dengan perputaran cepat.', 'fish-kembung.jpg'),
('Layang', 'Pelagis Kecil', 'Ikan pasar dan bahan olahan.', 'fish-layang.jpg'),
('Baronang', 'Karang/Pasar', 'Ikan konsumsi pasar dan restoran.', 'fish-baronang.jpg'),
('Kuwe/Bubara', 'Pelagis Karang', 'Ikan konsumsi bernilai sedang hingga tinggi.', 'fish-kuwe.jpg'),
('Bawal Putih', 'Demersal/Pasar', 'Ikan konsumsi dengan harga relatif stabil.', 'fish-bawal.jpg'),
('Lemuru', 'Pelagis Kecil', 'Ikan kecil untuk pasar dan olahan.', 'fish-lemuru.jpg'),
('Teri', 'Pelagis Kecil', 'Ikan kecil untuk pasar dan olahan.', 'fish-teri.jpg');

INSERT INTO harga_ikan (id_ikan, jenis_harga, harga_per_kg, tanggal_berlaku, status_aktif) VALUES
(1,'beli',220000,'2026-05-20','aktif'),(1,'jual',260000,'2026-05-20','aktif'),
(2,'beli',360000,'2026-05-20','aktif'),(2,'jual',400000,'2026-05-20','aktif'),
(3,'beli',38000,'2026-05-20','aktif'),(3,'jual',45000,'2026-05-20','aktif'),
(4,'beli',30000,'2026-05-20','aktif'),(4,'jual',38000,'2026-05-20','aktif'),
(5,'beli',38500,'2026-05-20','aktif'),(5,'jual',47000,'2026-05-20','aktif'),
(6,'beli',24500,'2026-05-20','aktif'),(6,'jual',31500,'2026-05-20','aktif'),
(7,'beli',20500,'2026-05-20','aktif'),(7,'jual',27000,'2026-05-20','aktif'),
(8,'beli',52000,'2026-05-20','aktif'),(8,'jual',66000,'2026-05-20','aktif'),
(9,'beli',78000,'2026-05-20','aktif'),(9,'jual',95000,'2026-05-20','aktif'),
(10,'beli',19000,'2026-05-20','aktif'),(10,'jual',25000,'2026-05-20','aktif'),
(11,'beli',55000,'2026-05-20','aktif'),(11,'jual',70000,'2026-05-20','aktif'),
(12,'beli',24000,'2026-05-20','aktif'),(12,'jual',32000,'2026-05-20','aktif'),
(13,'beli',18000,'2026-05-20','aktif'),(13,'jual',24000,'2026-05-20','aktif'),
(14,'beli',40000,'2026-05-20','aktif'),(14,'jual',52000,'2026-05-20','aktif'),
(15,'beli',48000,'2026-05-20','aktif'),(15,'jual',62000,'2026-05-20','aktif'),
(16,'beli',50000,'2026-05-20','aktif'),(16,'jual',65000,'2026-05-20','aktif'),
(17,'beli',12000,'2026-05-20','aktif'),(17,'jual',18000,'2026-05-20','aktif'),
(18,'beli',15000,'2026-05-20','aktif'),(18,'jual',21000,'2026-05-20','aktif');

INSERT INTO pembeli (nama_pembeli, jenis_pembeli, alamat, nomor_hp, nama_pabrik, lokasi_pabrik) VALUES
('BMJP', 'Pabrik', 'Maros, Sulawesi Selatan', '-', 'BMJP', 'Maros'),
('BMI', 'Pabrik', 'Maros, Sulawesi Selatan', '-', 'BMI', 'Maros'),
('Pelanggan Lelang Harian', 'Lelang', 'Datang langsung ke lokasi usaha', '-', '', ''),
('Pembeli Umum', 'Umum', 'Datang langsung, pilih ikan, timbang, lalu ambil sendiri', '-', '', '');

INSERT INTO settings (setting_key, setting_value) VALUES
('business_name','CV Ginaya Bahari'),
('business_tagline','Pendataan pemasukan, stok, dan penjualan ikan per kilogram'),
('business_address','Dekat Gusung, Kec. Ujung Tanah, Kota Makassar, Sulawesi Selatan'),
('business_phone','Belum tersedia'),
('business_owner','Haji Sumarni'),
('business_coordinate','-5.110385, 119.420455'),
('operational_note','Pembeli datang langsung, memilih ikan, menimbang, lalu mengambil sendiri. Risiko setelah pengambilan menjadi tanggung jawab pembeli.'),
('low_stock_threshold','30');
