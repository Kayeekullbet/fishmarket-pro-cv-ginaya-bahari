<?php
function initialize_database(PDO $pdo): void {
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id_role INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_role TEXT NOT NULL UNIQUE,
        keterangan TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        id_role INTEGER NOT NULL,
        nama_user TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        email TEXT UNIQUE,
        password TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'aktif' CHECK(status IN ('aktif','nonaktif')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_role) REFERENCES roles(id_role) ON UPDATE CASCADE ON DELETE RESTRICT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS nelayan (
        id_nelayan INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_nelayan TEXT NOT NULL,
        nomor_telepon TEXT,
        alamat TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ikan (
        id_ikan INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_ikan TEXT NOT NULL,
        jenis_ikan TEXT,
        keterangan TEXT,
        gambar TEXT DEFAULT 'fish-tuna.jpg',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS harga_ikan (
        id_harga INTEGER PRIMARY KEY AUTOINCREMENT,
        id_ikan INTEGER NOT NULL,
        jenis_harga TEXT NOT NULL DEFAULT 'jual' CHECK(jenis_harga IN ('beli','jual')),
        harga_per_kg REAL NOT NULL DEFAULT 0,
        tanggal_berlaku TEXT NOT NULL,
        status_aktif TEXT NOT NULL DEFAULT 'aktif' CHECK(status_aktif IN ('aktif','nonaktif')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pembeli (
        id_pembeli INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_pembeli TEXT NOT NULL,
        jenis_pembeli TEXT,
        alamat TEXT,
        nomor_hp TEXT,
        nama_pabrik TEXT,
        lokasi_pabrik TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pemasukan_ikan (
        id_pemasuk INTEGER PRIMARY KEY AUTOINCREMENT,
        id_nelayan INTEGER NOT NULL,
        tanggal_masuk TEXT NOT NULL,
        total_berat_kg REAL NOT NULL DEFAULT 0,
        total_harga REAL NOT NULL DEFAULT 0,
        keterangan TEXT,
        created_by INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_nelayan) REFERENCES nelayan(id_nelayan) ON UPDATE CASCADE ON DELETE RESTRICT,
        FOREIGN KEY(created_by) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS detail_pemasukan (
        id_detail_pemasukan INTEGER PRIMARY KEY AUTOINCREMENT,
        id_pemasuk INTEGER NOT NULL,
        id_ikan INTEGER NOT NULL,
        berat_kg REAL NOT NULL DEFAULT 0,
        harga_beli_per_kg REAL NOT NULL DEFAULT 0,
        subtotal REAL NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_pemasuk) REFERENCES pemasukan_ikan(id_pemasuk) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY(id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS penjualan (
        id_penjualan INTEGER PRIMARY KEY AUTOINCREMENT,
        id_pembeli INTEGER NOT NULL,
        tanggal_jual TEXT NOT NULL,
        total_berat_kg REAL NOT NULL DEFAULT 0,
        total_harga REAL NOT NULL DEFAULT 0,
        status_pengambilan TEXT NOT NULL DEFAULT 'belum_diambil' CHECK(status_pengambilan IN ('belum_diambil','sudah_diambil','dibatalkan')),
        created_by INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_pembeli) REFERENCES pembeli(id_pembeli) ON UPDATE CASCADE ON DELETE RESTRICT,
        FOREIGN KEY(created_by) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS detail_penjualan (
        id_detail_jual INTEGER PRIMARY KEY AUTOINCREMENT,
        id_penjualan INTEGER NOT NULL,
        id_ikan INTEGER NOT NULL,
        berat_kg REAL NOT NULL DEFAULT 0,
        harga_jual_per_kg REAL NOT NULL DEFAULT 0,
        subtotal REAL NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_penjualan) REFERENCES penjualan(id_penjualan) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY(id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id_log INTEGER PRIMARY KEY AUTOINCREMENT,
        id_user INTEGER,
        aktivitas TEXT NOT NULL,
        detail TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(id_user) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
    )");

    seed_database($pdo);
}

function seed_database(PDO $pdo): void {
    $exists = (int)$pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    if ($exists > 0) return;

    $pdo->beginTransaction();
    try {
        $roles = [
            ['admin', 'Mengelola seluruh fitur sistem.'],
            ['owner', 'Melihat dashboard dan laporan manajerial.'],
            ['pembelian', 'Mengelola pemasukan ikan dari nelayan.'],
            ['penjualan', 'Mengelola penjualan ikan kepada pembeli.'],
            ['gudang', 'Mengawasi stok dan mutasi ikan.'],
            ['auditor', 'Melihat data dan laporan tanpa perubahan data.'],
        ];
        $stmt = $pdo->prepare('INSERT INTO roles (nama_role, keterangan) VALUES (?, ?)');
        foreach ($roles as $role) $stmt->execute($role);

        $roleIds = [];
        foreach ($pdo->query('SELECT id_role, nama_role FROM roles') as $row) {
            $roleIds[$row['nama_role']] = $row['id_role'];
        }

        $users = [
            ['admin', 'Administrator Sistem', 'admin@ginayabahari.local', 'admin123', 'admin'],
            ['owner', 'Haji Sumarni', 'owner@ginayabahari.local', 'owner123', 'owner'],
            ['beli', 'Sukma - Petugas Pemasukan', 'beli@ginayabahari.local', 'beli123', 'pembelian'],
            ['jual', 'Petugas Penjualan', 'jual@ginayabahari.local', 'jual123', 'penjualan'],
            ['gudang', 'Petugas Gudang', 'gudang@ginayabahari.local', 'gudang123', 'gudang'],
            ['auditor', 'Auditor Internal', 'auditor@ginayabahari.local', 'auditor123', 'auditor'],
        ];
        $stmt = $pdo->prepare('INSERT INTO users (id_role, nama_user, username, email, password, status) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($users as $u) {
            $stmt->execute([$roleIds[$u[4]], $u[1], $u[0], $u[2], password_hash($u[3], PASSWORD_DEFAULT), 'aktif']);
        }

        $nelayan = [
            ['Nelayan Pulau Gusung', '-', 'Pemasok ikan dari wilayah pulau sekitar Makassar'],
            ['Nelayan Pulau-pulau Makassar', '-', 'Nelayan langganan CV Ginaya Bahari'],
            ['Nelayan Dermaga Ujung Tanah', '-', 'Pemasok dari area pendaratan ikan Ujung Tanah'],
            ['Nelayan Mitra Harian', '-', 'Mitra yang rutin membawa hasil tangkapan ke lokasi usaha'],
        ];
        $stmt = $pdo->prepare('INSERT INTO nelayan (nama_nelayan, nomor_telepon, alamat) VALUES (?, ?, ?)');
        foreach ($nelayan as $n) $stmt->execute($n);

        $ikan = [
            ['Sunu', 'Karang/Premium', 'Jenis utama dari wawancara. Harga jual contoh Rp260.000/kg dan dapat berubah sesuai kualitas serta pasar.', 'fish-sunu.jpg'],
            ['Tidar', 'Nama lokal/Premium', 'Nama lokal dari wawancara. Harga contoh sekitar Rp400.000/kg. Identifikasi visual perlu divalidasi dengan data lapangan.', 'fish-tidar.jpg'],
            ['Tanete', 'Nama lokal/Pasar', 'Nama lokal dari wawancara. Harga contoh sekitar Rp45.000/kg.', 'fish-tanete.jpg'],
            ['Terapung', 'Nama lokal/Pasar', 'Nama lokal dari wawancara. Digunakan sebagai item operasional karena sering disebut dalam pemasukan.', 'fish-terapung.jpg'],
            ['Tuna Sirip Kuning', 'Pelagis Besar', 'Komoditas besar untuk kebutuhan pabrik dan pasar grosir.', 'fish-tuna.jpg'],
            ['Cakalang', 'Pelagis', 'Produk umum untuk pengolahan dan distribusi harian.', 'fish-cakalang.jpg'],
            ['Tongkol', 'Pelagis', 'Permintaan stabil untuk pasar harian.', 'fish-tongkol.jpg'],
            ['Kakap Merah', 'Demersal', 'Ikan bernilai tinggi untuk restoran, pabrik, dan pembeli umum.', 'fish-kakap.jpg'],
            ['Kerapu Macan', 'Karang', 'Komoditas premium bernilai tinggi.', 'fish-kerapu.jpg'],
            ['Bandeng', 'Budidaya/Pasar', 'Produk ekonomis untuk pasar lokal.', 'fish-bandeng.jpg'],
            ['Tenggiri', 'Pelagis Besar', 'Sering digunakan untuk olahan dan pembeli grosir.', 'fish-tenggiri.jpg'],
            ['Kembung', 'Pelagis Kecil', 'Ikan pasar dengan perputaran cepat.', 'fish-kembung.jpg'],
            ['Layang', 'Pelagis Kecil', 'Ikan pasar dan bahan olahan.', 'fish-layang.jpg'],
            ['Baronang', 'Karang/Pasar', 'Ikan konsumsi pasar dan restoran.', 'fish-baronang.jpg'],
            ['Kuwe/Bubara', 'Pelagis Karang', 'Ikan konsumsi bernilai sedang hingga tinggi.', 'fish-kuwe.jpg'],
            ['Bawal Putih', 'Demersal/Pasar', 'Ikan konsumsi dengan harga relatif stabil.', 'fish-bawal.jpg'],
            ['Lemuru', 'Pelagis Kecil', 'Ikan kecil untuk pasar dan olahan.', 'fish-lemuru.jpg'],
            ['Teri', 'Pelagis Kecil', 'Ikan kecil untuk pasar dan olahan.', 'fish-teri.jpg'],
        ];
        $stmt = $pdo->prepare('INSERT INTO ikan (nama_ikan, jenis_ikan, keterangan, gambar) VALUES (?, ?, ?, ?)');
        foreach ($ikan as $i) $stmt->execute($i);

        $harga = [
            [1, 'beli', 220000, '2026-05-20', 'aktif'], [1, 'jual', 260000, '2026-05-20', 'aktif'],
            [2, 'beli', 360000, '2026-05-20', 'aktif'], [2, 'jual', 400000, '2026-05-20', 'aktif'],
            [3, 'beli', 38000, '2026-05-20', 'aktif'], [3, 'jual', 45000, '2026-05-20', 'aktif'],
            [4, 'beli', 30000, '2026-05-20', 'aktif'], [4, 'jual', 38000, '2026-05-20', 'aktif'],
            [5, 'beli', 38500, '2026-05-20', 'aktif'], [5, 'jual', 47000, '2026-05-20', 'aktif'],
            [6, 'beli', 24500, '2026-05-20', 'aktif'], [6, 'jual', 31500, '2026-05-20', 'aktif'],
            [7, 'beli', 20500, '2026-05-20', 'aktif'], [7, 'jual', 27000, '2026-05-20', 'aktif'],
            [8, 'beli', 52000, '2026-05-20', 'aktif'], [8, 'jual', 66000, '2026-05-20', 'aktif'],
            [9, 'beli', 78000, '2026-05-20', 'aktif'], [9, 'jual', 95000, '2026-05-20', 'aktif'],
            [10, 'beli', 19000, '2026-05-20', 'aktif'], [10, 'jual', 25000, '2026-05-20', 'aktif'],
            [11, 'beli', 55000, '2026-05-20', 'aktif'], [11, 'jual', 70000, '2026-05-20', 'aktif'],
            [12, 'beli', 24000, '2026-05-20', 'aktif'], [12, 'jual', 32000, '2026-05-20', 'aktif'],
            [13, 'beli', 18000, '2026-05-20', 'aktif'], [13, 'jual', 24000, '2026-05-20', 'aktif'],
            [14, 'beli', 40000, '2026-05-20', 'aktif'], [14, 'jual', 52000, '2026-05-20', 'aktif'],
            [15, 'beli', 48000, '2026-05-20', 'aktif'], [15, 'jual', 62000, '2026-05-20', 'aktif'],
            [16, 'beli', 50000, '2026-05-20', 'aktif'], [16, 'jual', 65000, '2026-05-20', 'aktif'],
            [17, 'beli', 12000, '2026-05-20', 'aktif'], [17, 'jual', 18000, '2026-05-20', 'aktif'],
            [18, 'beli', 15000, '2026-05-20', 'aktif'], [18, 'jual', 21000, '2026-05-20', 'aktif'],
        ];
        $stmt = $pdo->prepare('INSERT INTO harga_ikan (id_ikan, jenis_harga, harga_per_kg, tanggal_berlaku, status_aktif) VALUES (?, ?, ?, ?, ?)');
        foreach ($harga as $h) $stmt->execute($h);

        $pembeli = [
            ['BMJP', 'Pabrik', 'Maros, Sulawesi Selatan', '-', 'BMJP', 'Maros'],
            ['BMI', 'Pabrik', 'Maros, Sulawesi Selatan', '-', 'BMI', 'Maros'],
            ['Pelanggan Lelang Harian', 'Lelang', 'Datang langsung ke lokasi usaha', '-', '', ''],
            ['Pembeli Umum', 'Umum', 'Datang langsung, pilih ikan, timbang, lalu ambil sendiri', '-', '', ''],
        ];
        $stmt = $pdo->prepare('INSERT INTO pembeli (nama_pembeli, jenis_pembeli, alamat, nomor_hp, nama_pabrik, lokasi_pabrik) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($pembeli as $p) $stmt->execute($p);

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        insert_seed_pemasukan($pdo, 1, $yesterday, [[1, 45, 220000], [2, 12, 360000], [3, 160, 38000], [4, 120, 30000]], 1);
        insert_seed_pemasukan($pdo, 2, $today, [[5, 85, 38500], [6, 140, 24500], [8, 35, 52000], [11, 60, 55000], [12, 110, 24000]], 1);
        insert_seed_pemasukan($pdo, 3, $today, [[13, 130, 18000], [14, 42, 40000], [15, 38, 48000], [17, 180, 12000], [18, 75, 15000]], 1);
        insert_seed_penjualan($pdo, 1, $today, [[1, 20, 260000], [2, 6, 400000], [5, 30, 47000]], 1, 'sudah_diambil');
        insert_seed_penjualan($pdo, 2, $today, [[1, 10, 260000], [8, 15, 66000], [11, 20, 70000]], 1, 'sudah_diambil');
        insert_seed_penjualan($pdo, 3, $today, [[3, 55, 45000], [4, 40, 38000], [12, 45, 32000], [13, 60, 24000]], 1, 'belum_diambil');
        insert_seed_penjualan($pdo, 4, $today, [[14, 10, 52000], [15, 12, 62000], [17, 70, 18000]], 1, 'belum_diambil');

        $settings = [
            ['business_name', 'CV Ginaya Bahari'],
            ['business_tagline', 'Pendataan pemasukan, stok, dan penjualan ikan per kilogram'],
            ['business_address', 'Dekat Gusung, Kec. Ujung Tanah, Kota Makassar, Sulawesi Selatan'],
            ['business_phone', 'Belum tersedia'],
            ['business_owner', 'Haji Sumarni'],
            ['business_coordinate', '-5.110385, 119.420455'],
            ['operational_note', 'Pembeli datang langsung, memilih ikan, menimbang, lalu mengambil sendiri. Risiko setelah pengambilan menjadi tanggung jawab pembeli.'],
            ['low_stock_threshold', '30'],
        ];
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($settings as $s) $stmt->execute($s);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function insert_seed_pemasukan(PDO $pdo, int $idNelayan, string $date, array $items, int $userId): void {
    $totalBerat = 0;
    $totalHarga = 0;
    foreach ($items as $item) {
        $totalBerat += (float)$item[1];
        $totalHarga += (float)$item[1] * (float)$item[2];
    }
    $pdo->prepare('INSERT INTO pemasukan_ikan (id_nelayan, tanggal_masuk, total_berat_kg, total_harga, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$idNelayan, $date, $totalBerat, $totalHarga, 'Data contoh awal sistem', $userId]);
    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO detail_pemasukan (id_pemasuk, id_ikan, berat_kg, harga_beli_per_kg, subtotal) VALUES (?, ?, ?, ?, ?)');
    foreach ($items as $item) {
        $stmt->execute([$id, $item[0], $item[1], $item[2], $item[1] * $item[2]]);
    }
}

function insert_seed_penjualan(PDO $pdo, int $idPembeli, string $date, array $items, int $userId, string $status): void {
    $totalBerat = 0;
    $totalHarga = 0;
    foreach ($items as $item) {
        $totalBerat += (float)$item[1];
        $totalHarga += (float)$item[1] * (float)$item[2];
    }
    $pdo->prepare('INSERT INTO penjualan (id_pembeli, tanggal_jual, total_berat_kg, total_harga, status_pengambilan, created_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$idPembeli, $date, $totalBerat, $totalHarga, $status, $userId]);
    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO detail_penjualan (id_penjualan, id_ikan, berat_kg, harga_jual_per_kg, subtotal) VALUES (?, ?, ?, ?, ?)');
    foreach ($items as $item) {
        $stmt->execute([$id, $item[0], $item[1], $item[2], $item[1] * $item[2]]);
    }
}
