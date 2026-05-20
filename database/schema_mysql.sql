CREATE DATABASE IF NOT EXISTS fishmarket_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fishmarket_pro;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_stok_ikan;
DROP TABLE IF EXISTS activity_logs, detail_penjualan, penjualan, detail_pemasukan, pemasukan_ikan, pembeli, harga_ikan, ikan, nelayan, users, roles, settings;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id_role BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL UNIQUE,
    keterangan TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id_user BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_role BIGINT UNSIGNED NOT NULL,
    nama_user VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_roles FOREIGN KEY (id_role) REFERENCES roles(id_role) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE nelayan (
    id_nelayan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_nelayan VARCHAR(100) NOT NULL,
    nomor_telepon VARCHAR(20) NULL,
    alamat TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ikan (
    id_ikan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_ikan VARCHAR(100) NOT NULL,
    jenis_ikan VARCHAR(100) NULL,
    keterangan VARCHAR(255) NULL,
    gambar VARCHAR(100) DEFAULT 'fish-tuna.jpg',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE harga_ikan (
    id_harga BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ikan BIGINT UNSIGNED NOT NULL,
    jenis_harga ENUM('beli','jual') NOT NULL DEFAULT 'jual',
    harga_per_kg DECIMAL(15,2) NOT NULL DEFAULT 0,
    tanggal_berlaku DATE NOT NULL,
    status_aktif ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_harga_ikan FOREIGN KEY (id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_harga_ikan_aktif (id_ikan, jenis_harga, status_aktif, tanggal_berlaku)
) ENGINE=InnoDB;

CREATE TABLE pembeli (
    id_pembeli BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_pembeli VARCHAR(100) NOT NULL,
    jenis_pembeli VARCHAR(50) NULL,
    alamat TEXT NULL,
    nomor_hp VARCHAR(20) NULL,
    nama_pabrik VARCHAR(100) NULL,
    lokasi_pabrik VARCHAR(150) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pemasukan_ikan (
    id_pemasuk BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_nelayan BIGINT UNSIGNED NOT NULL,
    tanggal_masuk DATE NOT NULL,
    total_berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_harga DECIMAL(15,2) NOT NULL DEFAULT 0,
    keterangan VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pemasukan_nelayan FOREIGN KEY (id_nelayan) REFERENCES nelayan(id_nelayan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pemasukan_user FOREIGN KEY (created_by) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_pemasukan_tanggal (tanggal_masuk)
) ENGINE=InnoDB;

CREATE TABLE detail_pemasukan (
    id_detail_pemasukan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pemasuk BIGINT UNSIGNED NOT NULL,
    id_ikan BIGINT UNSIGNED NOT NULL,
    berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    harga_beli_per_kg DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_detail_pemasukan_pemasuk FOREIGN KEY (id_pemasuk) REFERENCES pemasukan_ikan(id_pemasuk) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_pemasukan_ikan FOREIGN KEY (id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_detail_pemasukan_ikan (id_ikan)
) ENGINE=InnoDB;

CREATE TABLE penjualan (
    id_penjualan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pembeli BIGINT UNSIGNED NOT NULL,
    tanggal_jual DATE NOT NULL,
    total_berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_harga DECIMAL(15,2) NOT NULL DEFAULT 0,
    status_pengambilan ENUM('belum_diambil','sudah_diambil','dibatalkan') NOT NULL DEFAULT 'belum_diambil',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_penjualan_pembeli FOREIGN KEY (id_pembeli) REFERENCES pembeli(id_pembeli) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_penjualan_user FOREIGN KEY (created_by) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_penjualan_tanggal_status (tanggal_jual, status_pengambilan)
) ENGINE=InnoDB;

CREATE TABLE detail_penjualan (
    id_detail_jual BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penjualan BIGINT UNSIGNED NOT NULL,
    id_ikan BIGINT UNSIGNED NOT NULL,
    berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    harga_jual_per_kg DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_detail_penjualan_penjualan FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_penjualan_ikan FOREIGN KEY (id_ikan) REFERENCES ikan(id_ikan) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_detail_penjualan_ikan (id_ikan)
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user BIGINT UNSIGNED NULL,
    aktivitas VARCHAR(150) NOT NULL,
    detail TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW v_stok_ikan AS
SELECT
    i.id_ikan,
    i.nama_ikan,
    i.jenis_ikan,
    i.gambar,
    COALESCE(pm.total_masuk, 0) AS total_masuk_kg,
    COALESCE(pj.total_keluar, 0) AS total_keluar_kg,
    COALESCE(pm.total_masuk, 0) - COALESCE(pj.total_keluar, 0) AS stok_tersedia_kg
FROM ikan i
LEFT JOIN (
    SELECT id_ikan, SUM(berat_kg) AS total_masuk
    FROM detail_pemasukan
    GROUP BY id_ikan
) pm ON i.id_ikan = pm.id_ikan
LEFT JOIN (
    SELECT dp.id_ikan, SUM(dp.berat_kg) AS total_keluar
    FROM detail_penjualan dp
    JOIN penjualan p ON p.id_penjualan = dp.id_penjualan
    WHERE p.status_pengambilan <> 'dibatalkan'
    GROUP BY dp.id_ikan
) pj ON i.id_ikan = pj.id_ikan;
