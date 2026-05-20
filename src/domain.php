<?php
require_once __DIR__ . '/auth.php';

function fishes(): array {
    return all_rows('SELECT * FROM ikan ORDER BY nama_ikan');
}

function fish_price(int $idIkan, string $jenis = 'jual'): float {
    $row = one_row('SELECT harga_per_kg FROM harga_ikan WHERE id_ikan = ? AND jenis_harga = ? AND status_aktif = ? ORDER BY tanggal_berlaku DESC, id_harga DESC LIMIT 1', [$idIkan, $jenis, 'aktif']);
    return (float)($row['harga_per_kg'] ?? 0);
}

function stock_for_fish(int $idIkan): float {
    $in = (float)(one_row('SELECT COALESCE(SUM(berat_kg), 0) AS total FROM detail_pemasukan WHERE id_ikan = ?', [$idIkan])['total'] ?? 0);
    $out = (float)(one_row("SELECT COALESCE(SUM(dp.berat_kg), 0) AS total FROM detail_penjualan dp JOIN penjualan p ON p.id_penjualan = dp.id_penjualan WHERE dp.id_ikan = ? AND p.status_pengambilan <> 'dibatalkan'", [$idIkan])['total'] ?? 0);
    return $in - $out;
}

function stock_rows(): array {
    return all_rows("SELECT
        i.id_ikan,
        i.nama_ikan,
        i.jenis_ikan,
        i.gambar,
        COALESCE(pm.total_masuk, 0) AS total_masuk_kg,
        COALESCE(pj.total_keluar, 0) AS total_keluar_kg,
        COALESCE(pm.total_masuk, 0) - COALESCE(pj.total_keluar, 0) AS stok_tersedia_kg,
        COALESCE(hj.harga_per_kg, 0) AS harga_jual_per_kg,
        COALESCE(hb.harga_per_kg, 0) AS harga_beli_per_kg
    FROM ikan i
    LEFT JOIN (
        SELECT id_ikan, SUM(berat_kg) AS total_masuk FROM detail_pemasukan GROUP BY id_ikan
    ) pm ON pm.id_ikan = i.id_ikan
    LEFT JOIN (
        SELECT dp.id_ikan, SUM(dp.berat_kg) AS total_keluar
        FROM detail_penjualan dp
        JOIN penjualan p ON p.id_penjualan = dp.id_penjualan
        WHERE p.status_pengambilan <> 'dibatalkan'
        GROUP BY dp.id_ikan
    ) pj ON pj.id_ikan = i.id_ikan
    LEFT JOIN harga_ikan hj ON hj.id_harga = (
        SELECT id_harga FROM harga_ikan h
        WHERE h.id_ikan = i.id_ikan AND h.jenis_harga = 'jual' AND h.status_aktif = 'aktif'
        ORDER BY h.tanggal_berlaku DESC, h.id_harga DESC LIMIT 1
    )
    LEFT JOIN harga_ikan hb ON hb.id_harga = (
        SELECT id_harga FROM harga_ikan h
        WHERE h.id_ikan = i.id_ikan AND h.jenis_harga = 'beli' AND h.status_aktif = 'aktif'
        ORDER BY h.tanggal_berlaku DESC, h.id_harga DESC LIMIT 1
    )
    ORDER BY i.nama_ikan");
}

function status_stok(float $value): array {
    $threshold = (float)setting_value('low_stock_threshold', '30');
    if ($value <= 0) return ['Habis', 'danger'];
    if ($value <= $threshold) return ['Rendah', 'warning'];
    return ['Aman', 'success'];
}

function transaction_items_from_post(string $priceField): array {
    $ids = $_POST['id_ikan'] ?? [];
    $weights = $_POST['berat_kg'] ?? [];
    $prices = $_POST[$priceField] ?? [];
    $items = [];
    for ($i = 0; $i < count($ids); $i++) {
        $idIkan = (int)($ids[$i] ?? 0);
        $weight = (float)str_replace(',', '.', (string)($weights[$i] ?? 0));
        $price = (float)str_replace(',', '.', (string)($prices[$i] ?? 0));
        if ($idIkan <= 0 || $weight <= 0) continue;
        if ($price <= 0) {
            throw new RuntimeException('Harga per kg wajib lebih dari 0.');
        }
        $items[] = [
            'id_ikan' => $idIkan,
            'berat_kg' => $weight,
            'harga' => $price,
            'subtotal' => $weight * $price,
        ];
    }
    return $items;
}

function options_html(array $rows, string $valueKey, string $labelKey, $selectedValue = null, string $extraLabelKey = ''): string {
    $html = '<option value="">Pilih data</option>';
    foreach ($rows as $row) {
        $label = $row[$labelKey] ?? '';
        if ($extraLabelKey && !empty($row[$extraLabelKey])) $label .= ' · ' . $row[$extraLabelKey];
        $html .= '<option value="' . e($row[$valueKey]) . '" ' . selected($row[$valueKey], $selectedValue) . '>' . e($label) . '</option>';
    }
    return $html;
}

function save_setting(string $key, string $value): void {
    $exists = one_row('SELECT setting_key FROM settings WHERE setting_key = ?', [$key]);
    if ($exists) {
        execute_sql('UPDATE settings SET setting_value = ? WHERE setting_key = ?', [$value, $key]);
    } else {
        execute_sql('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', [$key, $value]);
    }
}
