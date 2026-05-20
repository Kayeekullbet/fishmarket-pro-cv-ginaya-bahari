<?php
session_start();
require_once __DIR__ . '/../src/pages.php';

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "SKIP: PDO SQLite driver tidak aktif pada PHP CLI ini. Jalankan via Docker atau aktifkan pdo_sqlite untuk smoke test penuh.\n";
    exit(0);
}

@unlink(__DIR__ . '/../storage/fishmarket.sqlite');
db();

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
    echo "OK: {$message}\n";
}

assert_true((int)one_row('SELECT COUNT(*) AS total FROM roles')['total'] === 6, 'role seed tersedia');
assert_true((int)one_row('SELECT COUNT(*) AS total FROM ikan')['total'] >= 6, 'data ikan tersedia');
assert_true(login_attempt('admin', 'admin123', 'admin') === true, 'login admin berhasil');
assert_true(can_access('users') === true, 'admin dapat membuka manajemen user');
assert_true(strpos(page_dashboard(), 'Operasional hari ini') !== false, 'dashboard dapat dirender');
assert_true(count(stock_rows()) >= 6, 'stok dapat dihitung');
$stockTuna = stock_for_fish(1);
assert_true($stockTuna >= 0, 'stok tuna bernilai valid');

try {
    $_POST = [
        'id_pembeli' => 1,
        'tanggal_jual' => date('Y-m-d'),
        'status_pengambilan' => 'belum_diambil',
        'id_ikan' => [1],
        'berat_kg' => [$stockTuna + 9999],
        'harga_jual_per_kg' => [47000],
    ];
    $_GET = ['page' => 'penjualan'];
    handle_penjualan_action('');
    assert_true(false, 'validasi stok wajib menolak penjualan berlebih');
} catch (Throwable $e) {
    assert_true(str_contains($e->getMessage(), 'tidak cukup'), 'validasi stok menolak penjualan berlebih');
}

echo "Smoke test selesai.\n";
