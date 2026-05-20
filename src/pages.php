<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/domain.php';

function handle_request_actions(string $page): void {
    $action = $_GET['action'] ?? '';
    if (!is_post() && $action === '') return;

    try {
        if ($page === 'nelayan') handle_nelayan_action($action);
        if ($page === 'ikan') handle_ikan_action($action);
        if ($page === 'harga') handle_harga_action($action);
        if ($page === 'pembeli') handle_pembeli_action($action);
        if ($page === 'pemasukan') handle_pemasukan_action($action);
        if ($page === 'penjualan') handle_penjualan_action($action);
        if ($page === 'users') handle_users_action($action);
        if ($page === 'settings') handle_settings_action($action);
        if ($page === 'laporan' && $action === 'export') export_laporan_csv();
    } catch (Throwable $e) {
        try { if (db()->inTransaction()) db()->rollBack(); } catch (Throwable $ignored) {}
        flash('danger', 'Gagal memproses data: ' . $e->getMessage());
        redirect_to('index.php?page=' . urlencode($page));
    }
}

function render_page(string $page): string {
    return match ($page) {
        'dashboard' => page_dashboard(),
        'nelayan' => page_nelayan(),
        'ikan' => page_ikan(),
        'harga' => page_harga(),
        'pembeli' => page_pembeli(),
        'pemasukan' => page_pemasukan(),
        'penjualan' => page_penjualan(),
        'stok' => page_stok(),
        'laporan' => page_laporan(),
        'users' => page_users(),
        'settings' => page_settings(),
        default => page_dashboard(),
    };
}

function page_title(string $page): string {
    return [
        'dashboard' => 'Dashboard',
        'nelayan' => 'Data Nelayan',
        'ikan' => 'Data Ikan',
        'harga' => 'Harga Ikan',
        'pembeli' => 'Data Pembeli',
        'pemasukan' => 'Transaksi Pemasukan',
        'penjualan' => 'Transaksi Penjualan',
        'stok' => 'Stok Ikan',
        'laporan' => 'Laporan',
        'users' => 'Manajemen User',
        'settings' => 'Pengaturan',
    ][$page] ?? 'Dashboard';
}

function page_dashboard(): string {
    $today = date('Y-m-d');
    $stats = [
        'pemasukan_hari_ini' => one_row('SELECT COALESCE(SUM(total_harga),0) AS total, COALESCE(SUM(total_berat_kg),0) AS berat FROM pemasukan_ikan WHERE tanggal_masuk = ?', [$today]),
        'penjualan_hari_ini' => one_row("SELECT COALESCE(SUM(total_harga),0) AS total, COALESCE(SUM(total_berat_kg),0) AS berat FROM penjualan WHERE tanggal_jual = ? AND status_pengambilan <> 'dibatalkan'", [$today]),
        'nelayan' => one_row('SELECT COUNT(*) AS total FROM nelayan'),
        'pembeli' => one_row('SELECT COUNT(*) AS total FROM pembeli'),
        'stok' => stock_rows(),
    ];
    $stokTotal = array_sum(array_map(fn($r) => (float)$r['stok_tersedia_kg'], $stats['stok']));
    $lowStock = array_filter($stats['stok'], fn($r) => (float)$r['stok_tersedia_kg'] <= (float)setting_value('low_stock_threshold', '30'));
    $topFish = all_rows("SELECT i.nama_ikan, i.gambar, SUM(dp.berat_kg) AS total_kg, SUM(dp.subtotal) AS total_rp
        FROM detail_penjualan dp
        JOIN penjualan p ON p.id_penjualan = dp.id_penjualan
        JOIN ikan i ON i.id_ikan = dp.id_ikan
        WHERE p.status_pengambilan <> 'dibatalkan'
        GROUP BY i.id_ikan
        ORDER BY total_kg DESC
        LIMIT 5");
    $trend = all_rows("SELECT d.tanggal,
            COALESCE(pm.total, 0) AS pemasukan,
            COALESCE(pj.total, 0) AS penjualan
        FROM (
            SELECT date('now','-6 day') AS tanggal UNION SELECT date('now','-5 day') UNION SELECT date('now','-4 day') UNION
            SELECT date('now','-3 day') UNION SELECT date('now','-2 day') UNION SELECT date('now','-1 day') UNION SELECT date('now')
        ) d
        LEFT JOIN (SELECT tanggal_masuk AS t, SUM(total_harga) AS total FROM pemasukan_ikan GROUP BY tanggal_masuk) pm ON pm.t = d.tanggal
        LEFT JOIN (SELECT tanggal_jual AS t, SUM(total_harga) AS total FROM penjualan WHERE status_pengambilan <> 'dibatalkan' GROUP BY tanggal_jual) pj ON pj.t = d.tanggal
        ORDER BY d.tanggal");
    $maxTrend = max(1, ...array_map(fn($r) => max((float)$r['pemasukan'], (float)$r['penjualan']), $trend));

    $html = role_notice();
    $html .= '<div class="hero-panel">\n<div><span class="eyebrow">CV Ginaya Bahari</span><h2>Kontrol pemasukan, penjualan, dan stok ikan dari satu dashboard.</h2><p>Data contoh disesuaikan dengan wawancara: ikan masuk dari nelayan pulau, ditimbang per kilogram, disimpan dengan es balok, lalu dijual ke pabrik, lelang, dan pembeli umum.</p><div class="hero-actions"><a class="btn btn-primary" href="index.php?page=penjualan">Input Penjualan</a><a class="btn btn-soft" href="index.php?page=pemasukan">Input Pemasukan</a></div></div><div class="floating-aquarium"><img src="' . e(fish_src('fish-sunu.jpg')) . '" alt="Ikan Sunu"><img src="' . e(fish_src('fish-terapung.jpg')) . '" alt="Ikan Terapung"><img src="' . e(fish_src('fish-tanete.jpg')) . '" alt="Ikan Tanete"></div></div>';

    $html .= '<div class="stat-grid">';
    $html .= stat_card('Pemasukan hari ini', rupiah($stats['pemasukan_hari_ini']['total']), kg($stats['pemasukan_hari_ini']['berat']), '⇣', 'success');
    $html .= stat_card('Penjualan hari ini', rupiah($stats['penjualan_hari_ini']['total']), kg($stats['penjualan_hari_ini']['berat']), '⇡', 'primary');
    $html .= stat_card('Total stok tersedia', kg($stokTotal), count($lowStock) . ' item stok rendah/habis', '▦', 'info');
    $html .= stat_card('Mitra aktif', (int)$stats['nelayan']['total'] . ' nelayan', (int)$stats['pembeli']['total'] . ' pembeli', '◎', 'warning');
    $html .= '</div>';

    $html .= '<div class="grid-2">';
    $bars = '<div class="chart-card"><h3>Tren transaksi 7 hari</h3><div class="bar-chart">';
    foreach ($trend as $row) {
        $h1 = max(4, ((float)$row['pemasukan'] / $maxTrend) * 100);
        $h2 = max(4, ((float)$row['penjualan'] / $maxTrend) * 100);
        $bars .= '<div class="bar-day"><div class="bar-stack"><span class="bar bar-in" style="height:' . e($h1) . '%" title="Pemasukan ' . e(rupiah($row['pemasukan'])) . '"></span><span class="bar bar-out" style="height:' . e($h2) . '%" title="Penjualan ' . e(rupiah($row['penjualan'])) . '"></span></div><small>' . e(date('d/m', strtotime($row['tanggal']))) . '</small></div>';
    }
    $bars .= '</div><div class="legend"><span><i class="legend-in"></i>Pemasukan</span><span><i class="legend-out"></i>Penjualan</span></div></div>';
    $html .= card($bars);

    $top = '<div class="table-card"><h3>Ikan paling laku</h3>';
    if (!$topFish) {
        $top .= empty_state('Belum ada data penjualan', 'Mulai input transaksi penjualan untuk menampilkan ranking ikan.');
    } else {
        $top .= '<div class="mini-list">';
        foreach ($topFish as $fish) {
            $top .= '<div class="mini-item"><img src="' . e(fish_src($fish['gambar'] ?? '')) . '" alt=""><div><strong>' . e($fish['nama_ikan']) . '</strong><small>' . kg($fish['total_kg']) . ' · ' . rupiah($fish['total_rp']) . '</small></div></div>';
        }
        $top .= '</div>';
    }
    $top .= '</div>';
    $html .= card($top);
    $html .= '</div>';

    if ($lowStock) {
        $html .= card('<h3>Peringatan stok</h3><div class="stock-warning-list">' . implode('', array_map(function($r) {
            [$s, $tone] = status_stok((float)$r['stok_tersedia_kg']);
            return '<a href="index.php?page=stok" class="stock-warning"><img src="' . e(fish_src($r['gambar'] ?? '')) . '" alt=""><span><strong>' . e($r['nama_ikan']) . '</strong><small>' . kg($r['stok_tersedia_kg']) . '</small></span>' . badge($s, $tone) . '</a>';
        }, $lowStock)) . '</div>');
    }
    return $html;
}

function stat_card(string $title, string $value, string $note, string $icon, string $tone): string {
    return '<div class="stat-card tone-' . e($tone) . '"><span class="stat-icon">' . e($icon) . '</span><div><p>' . e($title) . '</p><h3>' . e($value) . '</h3><small>' . e($note) . '</small></div></div>';
}

function handle_nelayan_action(string $action): void {
    if (!can_write('nelayan')) throw new RuntimeException('Role Anda tidak diizinkan mengubah data nelayan.');
    if (is_post()) {
        $id = (int)post_value('id_nelayan', 0);
        $data = [trim(post_value('nama_nelayan')), trim(post_value('nomor_telepon')), trim(post_value('alamat'))];
        if ($data[0] === '') throw new RuntimeException('Nama nelayan wajib diisi.');
        if ($id > 0) {
            execute_sql('UPDATE nelayan SET nama_nelayan=?, nomor_telepon=?, alamat=?, updated_at=CURRENT_TIMESTAMP WHERE id_nelayan=?', [...$data, $id]);
            log_activity('Update nelayan', $data[0]);
        } else {
            execute_sql('INSERT INTO nelayan (nama_nelayan, nomor_telepon, alamat) VALUES (?, ?, ?)', $data);
            log_activity('Tambah nelayan', $data[0]);
        }
        flash('success', 'Data nelayan berhasil disimpan.');
    } elseif ($action === 'delete') {
        execute_sql('DELETE FROM nelayan WHERE id_nelayan=?', [(int)$_GET['id']]);
        flash('success', 'Data nelayan berhasil dihapus.');
    }
    redirect_to('index.php?page=nelayan');
}

function page_nelayan(): string {
    $edit = !empty($_GET['edit']) ? one_row('SELECT * FROM nelayan WHERE id_nelayan=?', [(int)$_GET['edit']]) : null;
    $rows = all_rows('SELECT n.*, COUNT(p.id_pemasuk) AS total_transaksi FROM nelayan n LEFT JOIN pemasukan_ikan p ON p.id_nelayan=n.id_nelayan GROUP BY n.id_nelayan ORDER BY n.nama_nelayan');
    $form = '';
    if (can_write('nelayan')) {
        $form = '<form method="post" class="form-grid compact-form" action="index.php?page=nelayan"><input type="hidden" name="id_nelayan" value="' . e($edit['id_nelayan'] ?? '') . '">' .
            form_input('Nama Nelayan', 'nama_nelayan', $edit['nama_nelayan'] ?? '', 'text', true) .
            form_input('Nomor Telepon', 'nomor_telepon', $edit['nomor_telepon'] ?? '', 'text') .
            form_textarea('Alamat', 'alamat', $edit['alamat'] ?? '') .
            '<div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Data</button>' . ($edit ? '<a class="btn btn-soft" href="index.php?page=nelayan">Batal</a>' : '') . '</div></form>';
    }
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Transaksi</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $actions = can_write('nelayan') ? '<a class="btn btn-xs" href="index.php?page=nelayan&edit=' . e($r['id_nelayan']) . '">Edit</a> <a class="btn btn-xs btn-danger" data-confirm="Hapus data nelayan ini?" href="index.php?page=nelayan&action=delete&id=' . e($r['id_nelayan']) . '">Hapus</a>' : badge('Lihat', 'neutral');
        $table .= '<tr><td><strong>' . e($r['nama_nelayan']) . '</strong></td><td>' . e($r['nomor_telepon']) . '</td><td>' . e($r['alamat']) . '</td><td>' . (int)$r['total_transaksi'] . '</td><td>' . $actions . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return section_header('Master Nelayan', 'Kelola mitra pemasok ikan dari dermaga.', '') . '<div class="grid-2 unequal">' . card('<h3>' . ($edit ? 'Edit Nelayan' : 'Tambah Nelayan') . '</h3>' . ($form ?: '<p>Role ini hanya dapat melihat data.</p>')) . card('<h3>Daftar Nelayan</h3>' . $table) . '</div>';
}

function handle_ikan_action(string $action): void {
    if (!can_write('ikan')) throw new RuntimeException('Role Anda tidak diizinkan mengubah data ikan.');
    if (is_post()) {
        $id = (int)post_value('id_ikan', 0);
        $gambar = post_value('gambar', 'fish-tuna.jpg');
        $data = [trim(post_value('nama_ikan')), trim(post_value('jenis_ikan')), trim(post_value('keterangan')), $gambar];
        if ($data[0] === '') throw new RuntimeException('Nama ikan wajib diisi.');
        if ($id > 0) {
            execute_sql('UPDATE ikan SET nama_ikan=?, jenis_ikan=?, keterangan=?, gambar=?, updated_at=CURRENT_TIMESTAMP WHERE id_ikan=?', [...$data, $id]);
            log_activity('Update ikan', $data[0]);
        } else {
            execute_sql('INSERT INTO ikan (nama_ikan, jenis_ikan, keterangan, gambar) VALUES (?, ?, ?, ?)', $data);
            log_activity('Tambah ikan', $data[0]);
        }
        flash('success', 'Data ikan berhasil disimpan.');
    } elseif ($action === 'delete') {
        execute_sql('DELETE FROM ikan WHERE id_ikan=?', [(int)$_GET['id']]);
        flash('success', 'Data ikan berhasil dihapus.');
    }
    redirect_to('index.php?page=ikan');
}

function page_ikan(): string {
    $edit = !empty($_GET['edit']) ? one_row('SELECT * FROM ikan WHERE id_ikan=?', [(int)$_GET['edit']]) : null;
    $rows = all_rows('SELECT * FROM ikan ORDER BY nama_ikan');
    $fishImages = fish_image_options();
    $select = '<label>Gambar Ikan<select name="gambar">';
    foreach ($fishImages as $img) $select .= '<option value="' . e($img) . '" ' . selected($img, $edit['gambar'] ?? '') . '>' . e($img) . '</option>';
    $select .= '</select></label>';
    $form = can_write('ikan') ? '<form method="post" class="form-grid compact-form" action="index.php?page=ikan"><input type="hidden" name="id_ikan" value="' . e($edit['id_ikan'] ?? '') . '">' .
        form_input('Nama Ikan', 'nama_ikan', $edit['nama_ikan'] ?? '', 'text', true) .
        form_input('Jenis Ikan', 'jenis_ikan', $edit['jenis_ikan'] ?? '', 'text') .
        $select .
        form_textarea('Keterangan', 'keterangan', $edit['keterangan'] ?? '') .
        '<div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Data</button>' . ($edit ? '<a class="btn btn-soft" href="index.php?page=ikan">Batal</a>' : '') . '</div></form>' : '<p>Role ini hanya dapat melihat data.</p>';
    $cards = '<div class="fish-grid">';
    foreach ($rows as $r) {
        $actions = can_write('ikan') ? '<div class="card-actions"><a class="btn btn-xs" href="index.php?page=ikan&edit=' . e($r['id_ikan']) . '">Edit</a><a class="btn btn-xs btn-danger" data-confirm="Hapus data ikan ini?" href="index.php?page=ikan&action=delete&id=' . e($r['id_ikan']) . '">Hapus</a></div>' : '';
        $cards .= '<article class="fish-card"><img src="' . e(fish_src($r['gambar'] ?? '')) . '" alt="' . e($r['nama_ikan']) . '"><h3>' . e($r['nama_ikan']) . '</h3><p>' . e($r['jenis_ikan']) . '</p><small>' . e($r['keterangan']) . '</small>' . $actions . '</article>';
    }
    $cards .= '</div>';
    return section_header('Master Ikan', 'Data produk ikan beserta visual contoh untuk UI yang lebih hidup.') . '<div class="grid-2 unequal">' . card('<h3>' . ($edit ? 'Edit Ikan' : 'Tambah Ikan') . '</h3>' . $form) . card('<h3>Katalog Ikan</h3>' . $cards) . '</div>';
}

function handle_harga_action(string $action): void {
    if (!can_write('harga')) throw new RuntimeException('Role Anda tidak diizinkan mengubah harga.');
    if (is_post()) {
        $id = (int)post_value('id_harga', 0);
        $data = [(int)post_value('id_ikan'), post_value('jenis_harga'), (float)str_replace(',', '.', post_value('harga_per_kg')), post_value('tanggal_berlaku'), post_value('status_aktif')];
        if ($data[0] <= 0 || $data[2] <= 0 || !$data[3]) throw new RuntimeException('Data harga belum lengkap.');
        if ($id > 0) {
            execute_sql('UPDATE harga_ikan SET id_ikan=?, jenis_harga=?, harga_per_kg=?, tanggal_berlaku=?, status_aktif=?, updated_at=CURRENT_TIMESTAMP WHERE id_harga=?', [...$data, $id]);
        } else {
            execute_sql('INSERT INTO harga_ikan (id_ikan, jenis_harga, harga_per_kg, tanggal_berlaku, status_aktif) VALUES (?, ?, ?, ?, ?)', $data);
        }
        flash('success', 'Harga ikan berhasil disimpan.');
    } elseif ($action === 'delete') {
        execute_sql('DELETE FROM harga_ikan WHERE id_harga=?', [(int)$_GET['id']]);
        flash('success', 'Harga ikan berhasil dihapus.');
    }
    redirect_to('index.php?page=harga');
}

function page_harga(): string {
    $edit = !empty($_GET['edit']) ? one_row('SELECT * FROM harga_ikan WHERE id_harga=?', [(int)$_GET['edit']]) : null;
    $rows = all_rows('SELECT h.*, i.nama_ikan FROM harga_ikan h JOIN ikan i ON i.id_ikan=h.id_ikan ORDER BY h.tanggal_berlaku DESC, h.id_harga DESC');
    $fish = fishes();
    $form = can_write('harga') ? '<form method="post" class="form-grid compact-form" action="index.php?page=harga"><input type="hidden" name="id_harga" value="' . e($edit['id_harga'] ?? '') . '"><label>Ikan<select name="id_ikan" required>' . options_html($fish, 'id_ikan', 'nama_ikan', $edit['id_ikan'] ?? null, 'jenis_ikan') . '</select></label><label>Jenis Harga<select name="jenis_harga"><option value="beli" ' . selected('beli', $edit['jenis_harga'] ?? '') . '>Beli</option><option value="jual" ' . selected('jual', $edit['jenis_harga'] ?? 'jual') . '>Jual</option></select></label>' . form_input('Harga per Kg', 'harga_per_kg', $edit['harga_per_kg'] ?? '', 'number', true) . form_input('Tanggal Berlaku', 'tanggal_berlaku', $edit['tanggal_berlaku'] ?? date('Y-m-d'), 'date', true) . '<label>Status<select name="status_aktif"><option value="aktif" ' . selected('aktif', $edit['status_aktif'] ?? 'aktif') . '>Aktif</option><option value="nonaktif" ' . selected('nonaktif', $edit['status_aktif'] ?? '') . '>Nonaktif</option></select></label><div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Harga</button>' . ($edit ? '<a class="btn btn-soft" href="index.php?page=harga">Batal</a>' : '') . '</div></form>' : '<p>Role ini hanya dapat melihat data.</p>';
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Ikan</th><th>Jenis</th><th>Harga/Kg</th><th>Berlaku</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $actions = can_write('harga') ? '<a class="btn btn-xs" href="index.php?page=harga&edit=' . e($r['id_harga']) . '">Edit</a> <a class="btn btn-xs btn-danger" data-confirm="Hapus harga ini?" href="index.php?page=harga&action=delete&id=' . e($r['id_harga']) . '">Hapus</a>' : '';
        $table .= '<tr><td>' . e($r['nama_ikan']) . '</td><td>' . badge(strtoupper($r['jenis_harga']), $r['jenis_harga'] === 'jual' ? 'primary' : 'success') . '</td><td>' . rupiah($r['harga_per_kg']) . '</td><td>' . tanggal_id($r['tanggal_berlaku']) . '</td><td>' . badge($r['status_aktif'], $r['status_aktif'] === 'aktif' ? 'success' : 'neutral') . '</td><td>' . $actions . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return section_header('Harga Ikan', 'Riwayat harga beli dan jual per kilogram.') . '<div class="grid-2 unequal">' . card('<h3>' . ($edit ? 'Edit Harga' : 'Tambah Harga') . '</h3>' . $form) . card('<h3>Daftar Harga</h3>' . $table) . '</div>';
}

function handle_pembeli_action(string $action): void {
    if (!can_write('pembeli')) throw new RuntimeException('Role Anda tidak diizinkan mengubah data pembeli.');
    if (is_post()) {
        $id = (int)post_value('id_pembeli', 0);
        $data = [trim(post_value('nama_pembeli')), trim(post_value('jenis_pembeli')), trim(post_value('alamat')), trim(post_value('nomor_hp')), trim(post_value('nama_pabrik')), trim(post_value('lokasi_pabrik'))];
        if ($data[0] === '') throw new RuntimeException('Nama pembeli wajib diisi.');
        if ($id > 0) execute_sql('UPDATE pembeli SET nama_pembeli=?, jenis_pembeli=?, alamat=?, nomor_hp=?, nama_pabrik=?, lokasi_pabrik=?, updated_at=CURRENT_TIMESTAMP WHERE id_pembeli=?', [...$data, $id]);
        else execute_sql('INSERT INTO pembeli (nama_pembeli, jenis_pembeli, alamat, nomor_hp, nama_pabrik, lokasi_pabrik) VALUES (?, ?, ?, ?, ?, ?)', $data);
        flash('success', 'Data pembeli berhasil disimpan.');
    } elseif ($action === 'delete') {
        execute_sql('DELETE FROM pembeli WHERE id_pembeli=?', [(int)$_GET['id']]);
        flash('success', 'Data pembeli berhasil dihapus.');
    }
    redirect_to('index.php?page=pembeli');
}

function page_pembeli(): string {
    $edit = !empty($_GET['edit']) ? one_row('SELECT * FROM pembeli WHERE id_pembeli=?', [(int)$_GET['edit']]) : null;
    $rows = all_rows('SELECT p.*, COUNT(j.id_penjualan) AS total_transaksi FROM pembeli p LEFT JOIN penjualan j ON j.id_pembeli=p.id_pembeli GROUP BY p.id_pembeli ORDER BY p.nama_pembeli');
    $form = can_write('pembeli') ? '<form method="post" class="form-grid compact-form" action="index.php?page=pembeli"><input type="hidden" name="id_pembeli" value="' . e($edit['id_pembeli'] ?? '') . '">' . form_input('Nama Pembeli', 'nama_pembeli', $edit['nama_pembeli'] ?? '', 'text', true) . form_input('Jenis Pembeli', 'jenis_pembeli', $edit['jenis_pembeli'] ?? '', 'text') . form_input('Nomor HP', 'nomor_hp', $edit['nomor_hp'] ?? '', 'text') . form_textarea('Alamat', 'alamat', $edit['alamat'] ?? '') . form_input('Nama Pabrik', 'nama_pabrik', $edit['nama_pabrik'] ?? '', 'text') . form_input('Lokasi Pabrik', 'lokasi_pabrik', $edit['lokasi_pabrik'] ?? '', 'text') . '<div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Data</button>' . ($edit ? '<a class="btn btn-soft" href="index.php?page=pembeli">Batal</a>' : '') . '</div></form>' : '<p>Role ini hanya dapat melihat data.</p>';
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Nama</th><th>Jenis</th><th>Kontak</th><th>Pabrik</th><th>Transaksi</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $actions = can_write('pembeli') ? '<a class="btn btn-xs" href="index.php?page=pembeli&edit=' . e($r['id_pembeli']) . '">Edit</a> <a class="btn btn-xs btn-danger" data-confirm="Hapus data pembeli ini?" href="index.php?page=pembeli&action=delete&id=' . e($r['id_pembeli']) . '">Hapus</a>' : '';
        $table .= '<tr><td><strong>' . e($r['nama_pembeli']) . '</strong><small>' . e($r['alamat']) . '</small></td><td>' . e($r['jenis_pembeli']) . '</td><td>' . e($r['nomor_hp']) . '</td><td>' . e(trim(($r['nama_pabrik'] ?? '') . ' ' . ($r['lokasi_pabrik'] ?? ''))) . '</td><td>' . (int)$r['total_transaksi'] . '</td><td>' . $actions . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return section_header('Master Pembeli', 'Data pelanggan, pabrik, restoran, hotel, dan pedagang.') . '<div class="grid-2 unequal">' . card('<h3>' . ($edit ? 'Edit Pembeli' : 'Tambah Pembeli') . '</h3>' . $form) . card('<h3>Daftar Pembeli</h3>' . $table) . '</div>';
}

function handle_pemasukan_action(string $action): void {
    if (!can_write('pemasukan')) throw new RuntimeException('Role Anda tidak diizinkan mengubah pemasukan.');
    if (is_post()) {
        $idNelayan = (int)post_value('id_nelayan');
        $date = post_value('tanggal_masuk');
        $items = transaction_items_from_post('harga_beli_per_kg');
        if ($idNelayan <= 0 || !$date || !$items) throw new RuntimeException('Transaksi pemasukan belum lengkap.');
        $totalBerat = array_sum(array_column($items, 'berat_kg'));
        $totalHarga = array_sum(array_column($items, 'subtotal'));
        db()->beginTransaction();
        execute_sql('INSERT INTO pemasukan_ikan (id_nelayan, tanggal_masuk, total_berat_kg, total_harga, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?)', [$idNelayan, $date, $totalBerat, $totalHarga, post_value('keterangan'), current_user()['id_user']]);
        $id = (int)last_insert_id();
        foreach ($items as $item) {
            execute_sql('INSERT INTO detail_pemasukan (id_pemasuk, id_ikan, berat_kg, harga_beli_per_kg, subtotal) VALUES (?, ?, ?, ?, ?)', [$id, $item['id_ikan'], $item['berat_kg'], $item['harga'], $item['subtotal']]);
        }
        db()->commit();
        log_activity('Tambah pemasukan', 'ID ' . $id);
        flash('success', 'Transaksi pemasukan berhasil disimpan.');
    } elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        foreach (all_rows('SELECT id_ikan, SUM(berat_kg) AS berat FROM detail_pemasukan WHERE id_pemasuk=? GROUP BY id_ikan', [$id]) as $d) {
            if (stock_for_fish((int)$d['id_ikan']) - (float)$d['berat'] < -0.0001) {
                throw new RuntimeException('Pemasukan tidak dapat dihapus karena stok ikan sudah terpakai pada transaksi penjualan.');
            }
        }
        execute_sql('DELETE FROM pemasukan_ikan WHERE id_pemasuk=?', [$id]);
        flash('success', 'Transaksi pemasukan berhasil dihapus.');
    }
    redirect_to('index.php?page=pemasukan');
}

function page_pemasukan(): string {
    if (!empty($_GET['view'])) return detail_pemasukan_html((int)$_GET['view']);
    $rows = all_rows('SELECT p.*, n.nama_nelayan, u.nama_user FROM pemasukan_ikan p JOIN nelayan n ON n.id_nelayan=p.id_nelayan LEFT JOIN users u ON u.id_user=p.created_by ORDER BY p.tanggal_masuk DESC, p.id_pemasuk DESC');
    $html = section_header('Pemasukan Ikan', 'Input ikan masuk dari nelayan dengan banyak item dalam satu transaksi.', can_write('pemasukan') ? '<a class="btn btn-primary" href="index.php?page=pemasukan&new=1">Tambah Pemasukan</a>' : '');
    if (!empty($_GET['new']) && can_write('pemasukan')) {
        $html .= card(transaction_form('pemasukan'));
    }
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>ID</th><th>Tanggal</th><th>Nelayan</th><th>Total Berat</th><th>Total Harga</th><th>Petugas</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $actions = '<a class="btn btn-xs" href="index.php?page=pemasukan&view=' . e($r['id_pemasuk']) . '">Detail</a> ';
        if (can_write('pemasukan')) $actions .= '<a class="btn btn-xs btn-danger" data-confirm="Hapus transaksi ini?" href="index.php?page=pemasukan&action=delete&id=' . e($r['id_pemasuk']) . '">Hapus</a>';
        $table .= '<tr><td>#PM-' . str_pad((string)$r['id_pemasuk'], 4, '0', STR_PAD_LEFT) . '</td><td>' . tanggal_id($r['tanggal_masuk']) . '</td><td>' . e($r['nama_nelayan']) . '</td><td>' . kg($r['total_berat_kg']) . '</td><td>' . rupiah($r['total_harga']) . '</td><td>' . e($r['nama_user']) . '</td><td>' . $actions . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return $html . card('<h3>Riwayat Pemasukan</h3>' . $table);
}

function handle_penjualan_action(string $action): void {
    if (!can_write('penjualan')) throw new RuntimeException('Role Anda tidak diizinkan mengubah penjualan.');
    if (is_post()) {
        if (post_value('form_type') === 'status') {
            execute_sql('UPDATE penjualan SET status_pengambilan=?, updated_at=CURRENT_TIMESTAMP WHERE id_penjualan=?', [post_value('status_pengambilan'), (int)post_value('id_penjualan')]);
            flash('success', 'Status pengambilan berhasil diperbarui.');
            redirect_to('index.php?page=penjualan');
        }
        $idPembeli = (int)post_value('id_pembeli');
        $date = post_value('tanggal_jual');
        $items = transaction_items_from_post('harga_jual_per_kg');
        if ($idPembeli <= 0 || !$date || !$items) throw new RuntimeException('Transaksi penjualan belum lengkap.');
        $requestedByFish = [];
        foreach ($items as $item) {
            $requestedByFish[$item['id_ikan']] = ($requestedByFish[$item['id_ikan']] ?? 0) + (float)$item['berat_kg'];
        }
        foreach ($requestedByFish as $idIkan => $requestedWeight) {
            $stok = stock_for_fish((int)$idIkan);
            if ($requestedWeight > $stok + 0.0001) {
                $fish = one_row('SELECT nama_ikan FROM ikan WHERE id_ikan=?', [$idIkan]);
                throw new RuntimeException('Stok ' . ($fish['nama_ikan'] ?? 'ikan') . ' tidak cukup. Stok tersedia hanya ' . kg($stok) . '.');
            }
        }
        $totalBerat = array_sum(array_column($items, 'berat_kg'));
        $totalHarga = array_sum(array_column($items, 'subtotal'));
        db()->beginTransaction();
        execute_sql('INSERT INTO penjualan (id_pembeli, tanggal_jual, total_berat_kg, total_harga, status_pengambilan, created_by) VALUES (?, ?, ?, ?, ?, ?)', [$idPembeli, $date, $totalBerat, $totalHarga, post_value('status_pengambilan', 'belum_diambil'), current_user()['id_user']]);
        $id = (int)last_insert_id();
        foreach ($items as $item) execute_sql('INSERT INTO detail_penjualan (id_penjualan, id_ikan, berat_kg, harga_jual_per_kg, subtotal) VALUES (?, ?, ?, ?, ?)', [$id, $item['id_ikan'], $item['berat_kg'], $item['harga'], $item['subtotal']]);
        db()->commit();
        log_activity('Tambah penjualan', 'ID ' . $id);
        flash('success', 'Transaksi penjualan berhasil disimpan.');
    } elseif ($action === 'delete') {
        execute_sql('DELETE FROM penjualan WHERE id_penjualan=?', [(int)$_GET['id']]);
        flash('success', 'Transaksi penjualan berhasil dihapus.');
    }
    redirect_to('index.php?page=penjualan');
}

function page_penjualan(): string {
    if (!empty($_GET['view'])) return detail_penjualan_html((int)$_GET['view']);
    $rows = all_rows('SELECT p.*, b.nama_pembeli, u.nama_user FROM penjualan p JOIN pembeli b ON b.id_pembeli=p.id_pembeli LEFT JOIN users u ON u.id_user=p.created_by ORDER BY p.tanggal_jual DESC, p.id_penjualan DESC');
    $html = section_header('Penjualan Ikan', 'Transaksi keluar otomatis memeriksa stok tersedia.', can_write('penjualan') ? '<a class="btn btn-primary" href="index.php?page=penjualan&new=1">Tambah Penjualan</a>' : '');
    if (!empty($_GET['new']) && can_write('penjualan')) $html .= card(transaction_form('penjualan'));
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>ID</th><th>Tanggal</th><th>Pembeli</th><th>Total Berat</th><th>Total Harga</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $tone = $r['status_pengambilan'] === 'sudah_diambil' ? 'success' : ($r['status_pengambilan'] === 'dibatalkan' ? 'danger' : 'warning');
        $actions = '<a class="btn btn-xs" href="index.php?page=penjualan&view=' . e($r['id_penjualan']) . '">Detail</a> ';
        if (can_write('penjualan')) $actions .= '<a class="btn btn-xs btn-danger" data-confirm="Hapus transaksi ini?" href="index.php?page=penjualan&action=delete&id=' . e($r['id_penjualan']) . '">Hapus</a>';
        $table .= '<tr><td>#PJ-' . str_pad((string)$r['id_penjualan'], 4, '0', STR_PAD_LEFT) . '</td><td>' . tanggal_id($r['tanggal_jual']) . '</td><td>' . e($r['nama_pembeli']) . '</td><td>' . kg($r['total_berat_kg']) . '</td><td>' . rupiah($r['total_harga']) . '</td><td>' . badge(str_replace('_', ' ', $r['status_pengambilan']), $tone) . '</td><td>' . $actions . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return $html . card('<h3>Riwayat Penjualan</h3>' . $table);
}

function transaction_form(string $type): string {
    $isSale = $type === 'penjualan';
    $fish = fishes();
    $jsonPrices = [];
    foreach ($fish as $f) $jsonPrices[$f['id_ikan']] = $isSale ? fish_price((int)$f['id_ikan'], 'jual') : fish_price((int)$f['id_ikan'], 'beli');
    $fishOptions = options_html($fish, 'id_ikan', 'nama_ikan', null, 'jenis_ikan');
    $targetRows = $isSale ? all_rows('SELECT * FROM pembeli ORDER BY nama_pembeli') : all_rows('SELECT * FROM nelayan ORDER BY nama_nelayan');
    $targetOptions = $isSale ? options_html($targetRows, 'id_pembeli', 'nama_pembeli') : options_html($targetRows, 'id_nelayan', 'nama_nelayan');
    $targetName = $isSale ? 'id_pembeli' : 'id_nelayan';
    $dateName = $isSale ? 'tanggal_jual' : 'tanggal_masuk';
    $priceName = $isSale ? 'harga_jual_per_kg' : 'harga_beli_per_kg';
    $labelTarget = $isSale ? 'Pembeli' : 'Nelayan';
    $status = $isSale ? '<label>Status Pengambilan<select name="status_pengambilan"><option value="belum_diambil">Belum diambil</option><option value="sudah_diambil">Sudah diambil</option></select></label>' : form_textarea('Keterangan', 'keterangan', '');
    $html = '<form method="post" class="transaction-form" data-prices=\'' . e(json_encode($jsonPrices)) . '\' action="index.php?page=' . e($type) . '"><div class="form-grid two"><label>' . e($labelTarget) . '<select name="' . e($targetName) . '" required>' . $targetOptions . '</select></label>' . form_input('Tanggal', $dateName, date('Y-m-d'), 'date', true) . $status . '</div><div class="transaction-toolbar"><h3>Detail Item</h3><button type="button" class="btn btn-soft" data-add-row>Tambah Baris</button></div><div class="table-responsive"><table class="data-table item-table"><thead><tr><th>Ikan</th><th>Berat Kg</th><th>Harga/Kg</th><th>Subtotal</th><th></th></tr></thead><tbody data-item-body>' . transaction_row($fishOptions, $priceName) . '</tbody><tfoot><tr><td>Total</td><td data-total-weight>0 kg</td><td></td><td data-total-price>Rp 0</td><td></td></tr></tfoot></table></div><template data-row-template>' . transaction_row($fishOptions, $priceName) . '</template><div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Transaksi</button><a class="btn btn-soft" href="index.php?page=' . e($type) . '">Batal</a></div></form>';
    return $html;
}

function transaction_row(string $fishOptions, string $priceName): string {
    return '<tr><td><select name="id_ikan[]" required>' . $fishOptions . '</select></td><td><input type="number" name="berat_kg[]" step="0.01" min="0.01" value="1" required></td><td><input type="number" name="' . e($priceName) . '[]" step="0.01" min="0" value="0" required></td><td><strong data-row-subtotal>Rp 0</strong></td><td><button type="button" class="btn btn-xs btn-danger" data-remove-row>×</button></td></tr>';
}

function detail_pemasukan_html(int $id): string {
    $trx = one_row('SELECT p.*, n.nama_nelayan, n.nomor_telepon, n.alamat, u.nama_user FROM pemasukan_ikan p JOIN nelayan n ON n.id_nelayan=p.id_nelayan LEFT JOIN users u ON u.id_user=p.created_by WHERE p.id_pemasuk=?', [$id]);
    if (!$trx) return empty_state('Transaksi tidak ditemukan', 'Data pemasukan tidak tersedia.');
    $items = all_rows('SELECT d.*, i.nama_ikan, i.jenis_ikan, i.gambar FROM detail_pemasukan d JOIN ikan i ON i.id_ikan=d.id_ikan WHERE d.id_pemasuk=?', [$id]);
    $rows = '';
    foreach ($items as $r) $rows .= '<tr><td><div class="fish-cell"><img src="' . e(fish_src($r['gambar'] ?? '')) . '" alt=""><span>' . e($r['nama_ikan']) . '<small>' . e($r['jenis_ikan']) . '</small></span></div></td><td>' . kg($r['berat_kg']) . '</td><td>' . rupiah($r['harga_beli_per_kg']) . '</td><td>' . rupiah($r['subtotal']) . '</td></tr>';
    return detail_header('Bukti Pemasukan', '#PM-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), 'index.php?page=pemasukan') . '<div class="invoice"><div class="invoice-head"><div><h2>' . e(setting_value('business_name')) . '</h2><p>' . e(setting_value('business_address')) . '<br>' . e(setting_value('business_phone')) . '</p></div><img src="assets/img/logo.svg" alt="Logo"></div><div class="info-grid"><p><strong>Tanggal</strong><br>' . tanggal_id($trx['tanggal_masuk']) . '</p><p><strong>Nelayan</strong><br>' . e($trx['nama_nelayan']) . '<br><small>' . e($trx['nomor_telepon']) . '</small></p><p><strong>Petugas</strong><br>' . e($trx['nama_user']) . '</p></div><table class="data-table"><thead><tr><th>Ikan</th><th>Berat</th><th>Harga Beli/Kg</th><th>Subtotal</th></tr></thead><tbody>' . $rows . '</tbody><tfoot><tr><td>Total</td><td>' . kg($trx['total_berat_kg']) . '</td><td></td><td>' . rupiah($trx['total_harga']) . '</td></tr></tfoot></table><p class="note">Keterangan: ' . e($trx['keterangan']) . '</p></div>';
}

function detail_penjualan_html(int $id): string {
    $trx = one_row('SELECT p.*, b.nama_pembeli, b.jenis_pembeli, b.alamat, b.nomor_hp, u.nama_user FROM penjualan p JOIN pembeli b ON b.id_pembeli=p.id_pembeli LEFT JOIN users u ON u.id_user=p.created_by WHERE p.id_penjualan=?', [$id]);
    if (!$trx) return empty_state('Transaksi tidak ditemukan', 'Data penjualan tidak tersedia.');
    $items = all_rows('SELECT d.*, i.nama_ikan, i.jenis_ikan, i.gambar FROM detail_penjualan d JOIN ikan i ON i.id_ikan=d.id_ikan WHERE d.id_penjualan=?', [$id]);
    $rows = '';
    foreach ($items as $r) $rows .= '<tr><td><div class="fish-cell"><img src="' . e(fish_src($r['gambar'] ?? '')) . '" alt=""><span>' . e($r['nama_ikan']) . '<small>' . e($r['jenis_ikan']) . '</small></span></div></td><td>' . kg($r['berat_kg']) . '</td><td>' . rupiah($r['harga_jual_per_kg']) . '</td><td>' . rupiah($r['subtotal']) . '</td></tr>';
    $statusForm = can_write('penjualan') ? '<form method="post" class="inline-status" action="index.php?page=penjualan"><input type="hidden" name="form_type" value="status"><input type="hidden" name="id_penjualan" value="' . e($id) . '"><select name="status_pengambilan"><option value="belum_diambil" ' . selected('belum_diambil', $trx['status_pengambilan']) . '>Belum diambil</option><option value="sudah_diambil" ' . selected('sudah_diambil', $trx['status_pengambilan']) . '>Sudah diambil</option><option value="dibatalkan" ' . selected('dibatalkan', $trx['status_pengambilan']) . '>Dibatalkan</option></select><button class="btn btn-xs" type="submit">Update</button></form>' : badge(str_replace('_', ' ', $trx['status_pengambilan']), 'neutral');
    return detail_header('Nota Penjualan', '#PJ-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), 'index.php?page=penjualan') . '<div class="invoice"><div class="invoice-head"><div><h2>' . e(setting_value('business_name')) . '</h2><p>' . e(setting_value('business_address')) . '<br>' . e(setting_value('business_phone')) . '</p></div><img src="assets/img/logo.svg" alt="Logo"></div><div class="info-grid"><p><strong>Tanggal</strong><br>' . tanggal_id($trx['tanggal_jual']) . '</p><p><strong>Pembeli</strong><br>' . e($trx['nama_pembeli']) . '<br><small>' . e($trx['jenis_pembeli']) . ' · ' . e($trx['nomor_hp']) . '</small></p><p><strong>Status</strong><br>' . $statusForm . '</p></div><table class="data-table"><thead><tr><th>Ikan</th><th>Berat</th><th>Harga Jual/Kg</th><th>Subtotal</th></tr></thead><tbody>' . $rows . '</tbody><tfoot><tr><td>Total</td><td>' . kg($trx['total_berat_kg']) . '</td><td></td><td>' . rupiah($trx['total_harga']) . '</td></tr></tfoot></table><div class="signature"><span>Petugas</span><span>Pembeli</span></div></div>';
}

function detail_header(string $title, string $code, string $back): string {
    return '<div class="detail-toolbar"><div><h2>' . e($title) . '</h2><p>' . e($code) . '</p></div><div><a class="btn btn-soft" href="' . e($back) . '">Kembali</a><button class="btn btn-primary" type="button" onclick="window.print()">Cetak</button></div></div>';
}

function page_stok(): string {
    $rows = stock_rows();
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Ikan</th><th>Masuk</th><th>Keluar</th><th>Stok</th><th>Harga Jual</th><th>Status</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        [$status, $tone] = status_stok((float)$r['stok_tersedia_kg']);
        $table .= '<tr><td><div class="fish-cell"><img src="' . e(fish_src($r['gambar'] ?? '')) . '" alt=""><span>' . e($r['nama_ikan']) . '<small>' . e($r['jenis_ikan']) . '</small></span></div></td><td>' . kg($r['total_masuk_kg']) . '</td><td>' . kg($r['total_keluar_kg']) . '</td><td><strong>' . kg($r['stok_tersedia_kg']) . '</strong></td><td>' . rupiah($r['harga_jual_per_kg']) . '</td><td>' . badge($status, $tone) . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    return section_header('Stok Ikan', 'Stok dihitung otomatis dari total pemasukan dikurangi penjualan aktif.', '<button class="btn btn-soft" onclick="window.print()">Cetak</button>') . card($table);
}

function page_laporan(): string {
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-d');
    $pemasukan = one_row('SELECT COALESCE(SUM(total_berat_kg),0) berat, COALESCE(SUM(total_harga),0) total, COUNT(*) trx FROM pemasukan_ikan WHERE tanggal_masuk BETWEEN ? AND ?', [$start, $end]);
    $penjualan = one_row("SELECT COALESCE(SUM(total_berat_kg),0) berat, COALESCE(SUM(total_harga),0) total, COUNT(*) trx FROM penjualan WHERE tanggal_jual BETWEEN ? AND ? AND status_pengambilan <> 'dibatalkan'", [$start, $end]);
    $profit = all_rows("SELECT i.nama_ikan, SUM(dj.berat_kg) AS terjual, AVG(dj.harga_jual_per_kg) AS jual, COALESCE((SELECT AVG(dp.harga_beli_per_kg) FROM detail_pemasukan dp WHERE dp.id_ikan=i.id_ikan),0) AS beli
        FROM detail_penjualan dj JOIN penjualan p ON p.id_penjualan=dj.id_penjualan JOIN ikan i ON i.id_ikan=dj.id_ikan
        WHERE p.tanggal_jual BETWEEN ? AND ? AND p.status_pengambilan <> 'dibatalkan'
        GROUP BY i.id_ikan ORDER BY terjual DESC", [$start, $end]);
    $profitRows = '';
    $profitTotal = 0;
    foreach ($profit as $r) {
        $laba = ((float)$r['jual'] - (float)$r['beli']) * (float)$r['terjual'];
        $profitTotal += $laba;
        $profitRows .= '<tr><td>' . e($r['nama_ikan']) . '</td><td>' . kg($r['terjual']) . '</td><td>' . rupiah($r['beli']) . '</td><td>' . rupiah($r['jual']) . '</td><td>' . rupiah($laba) . '</td></tr>';
    }
    $filter = '<form class="filter-form" method="get"><input type="hidden" name="page" value="laporan"><label>Dari<input type="date" name="start" value="' . e($start) . '"></label><label>Sampai<input type="date" name="end" value="' . e($end) . '"></label><button class="btn btn-primary">Tampilkan</button><a class="btn btn-soft" href="index.php?page=laporan&action=export&start=' . e($start) . '&end=' . e($end) . '">Export CSV</a><button class="btn btn-soft" type="button" onclick="window.print()">Cetak</button></form>';
    $html = section_header('Laporan Periode', 'Filter transaksi, laba kotor, dan ringkasan operasional.', $filter);
    $html .= '<div class="stat-grid"><div class="stat-card tone-success"><span class="stat-icon">⇣</span><div><p>Pemasukan</p><h3>' . rupiah($pemasukan['total']) . '</h3><small>' . kg($pemasukan['berat']) . ' · ' . (int)$pemasukan['trx'] . ' transaksi</small></div></div><div class="stat-card tone-primary"><span class="stat-icon">⇡</span><div><p>Penjualan</p><h3>' . rupiah($penjualan['total']) . '</h3><small>' . kg($penjualan['berat']) . ' · ' . (int)$penjualan['trx'] . ' transaksi</small></div></div><div class="stat-card tone-warning"><span class="stat-icon">◎</span><div><p>Estimasi laba kotor</p><h3>' . rupiah($profitTotal) . '</h3><small>Berbasis rata-rata harga beli dan harga jual.</small></div></div></div>';
    $html .= card('<h3>Estimasi Laba Kotor per Ikan</h3><div class="table-responsive"><table class="data-table"><thead><tr><th>Ikan</th><th>Terjual</th><th>Rata Harga Beli</th><th>Rata Harga Jual</th><th>Estimasi Laba</th></tr></thead><tbody>' . ($profitRows ?: '<tr><td colspan="5">Tidak ada data pada periode ini.</td></tr>') . '</tbody></table></div>');
    return $html;
}

function export_laporan_csv(): void {
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-d');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-fishmarket-' . $start . '-' . $end . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Jenis', 'Tanggal', 'Kode', 'Mitra', 'Berat Kg', 'Total Harga']);
    foreach (all_rows('SELECT p.tanggal_masuk tanggal, p.id_pemasuk id, n.nama_nelayan mitra, p.total_berat_kg berat, p.total_harga total FROM pemasukan_ikan p JOIN nelayan n ON n.id_nelayan=p.id_nelayan WHERE p.tanggal_masuk BETWEEN ? AND ?', [$start, $end]) as $r) {
        fputcsv($out, ['Pemasukan', $r['tanggal'], 'PM-' . $r['id'], $r['mitra'], $r['berat'], $r['total']]);
    }
    foreach (all_rows("SELECT p.tanggal_jual tanggal, p.id_penjualan id, b.nama_pembeli mitra, p.total_berat_kg berat, p.total_harga total FROM penjualan p JOIN pembeli b ON b.id_pembeli=p.id_pembeli WHERE p.tanggal_jual BETWEEN ? AND ? AND p.status_pengambilan <> 'dibatalkan'", [$start, $end]) as $r) {
        fputcsv($out, ['Penjualan', $r['tanggal'], 'PJ-' . $r['id'], $r['mitra'], $r['berat'], $r['total']]);
    }
    fclose($out);
    exit;
}

function handle_users_action(string $action): void {
    if (!can_write('users')) throw new RuntimeException('Hanya admin yang boleh mengubah user.');
    if (is_post()) {
        $id = (int)post_value('id_user', 0);
        $password = trim(post_value('password'));
        $data = [(int)post_value('id_role'), trim(post_value('nama_user')), trim(post_value('username')), trim(post_value('email')), post_value('status')];
        if ($data[0] <= 0 || $data[1] === '' || $data[2] === '') throw new RuntimeException('Data user belum lengkap.');
        if ($id > 0) {
            if ($password !== '') execute_sql('UPDATE users SET id_role=?, nama_user=?, username=?, email=?, status=?, password=?, updated_at=CURRENT_TIMESTAMP WHERE id_user=?', [...$data, password_hash($password, PASSWORD_DEFAULT), $id]);
            else execute_sql('UPDATE users SET id_role=?, nama_user=?, username=?, email=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id_user=?', [...$data, $id]);
        } else {
            if ($password === '') throw new RuntimeException('Password wajib diisi untuk user baru.');
            execute_sql('INSERT INTO users (id_role, nama_user, username, email, status, password) VALUES (?, ?, ?, ?, ?, ?)', [...$data, password_hash($password, PASSWORD_DEFAULT)]);
        }
        flash('success', 'Data user berhasil disimpan.');
    } elseif ($action === 'delete') {
        if ((int)$_GET['id'] === (int)current_user()['id_user']) throw new RuntimeException('User yang sedang login tidak boleh dihapus.');
        execute_sql('DELETE FROM users WHERE id_user=?', [(int)$_GET['id']]);
        flash('success', 'User berhasil dihapus.');
    }
    redirect_to('index.php?page=users');
}

function page_users(): string {
    $edit = !empty($_GET['edit']) ? one_row('SELECT * FROM users WHERE id_user=?', [(int)$_GET['edit']]) : null;
    $roles = all_rows('SELECT * FROM roles ORDER BY id_role');
    $rows = all_rows('SELECT u.*, r.nama_role FROM users u JOIN roles r ON r.id_role=u.id_role ORDER BY r.id_role, u.nama_user');
    $roleSelect = '<label>Role<select name="id_role" required>' . options_html($roles, 'id_role', 'nama_role', $edit['id_role'] ?? null) . '</select></label>';
    $form = '<form method="post" class="form-grid compact-form" action="index.php?page=users"><input type="hidden" name="id_user" value="' . e($edit['id_user'] ?? '') . '">' . $roleSelect . form_input('Nama User', 'nama_user', $edit['nama_user'] ?? '', 'text', true) . form_input('Username', 'username', $edit['username'] ?? '', 'text', true) . form_input('Email', 'email', $edit['email'] ?? '', 'email') . form_input($edit ? 'Password Baru (opsional)' : 'Password', 'password', '', 'password', !$edit) . '<label>Status<select name="status"><option value="aktif" ' . selected('aktif', $edit['status'] ?? 'aktif') . '>Aktif</option><option value="nonaktif" ' . selected('nonaktif', $edit['status'] ?? '') . '>Nonaktif</option></select></label><div class="form-actions"><button class="btn btn-primary" type="submit">Simpan User</button>' . ($edit ? '<a class="btn btn-soft" href="index.php?page=users">Batal</a>' : '') . '</div></form>';
    $table = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($rows as $r) $table .= '<tr><td>' . e($r['nama_user']) . '</td><td>' . e($r['username']) . '</td><td>' . e($r['email']) . '</td><td>' . user_role_badge($r['nama_role']) . '</td><td>' . badge($r['status'], $r['status'] === 'aktif' ? 'success' : 'neutral') . '</td><td><a class="btn btn-xs" href="index.php?page=users&edit=' . e($r['id_user']) . '">Edit</a> <a class="btn btn-xs btn-danger" data-confirm="Hapus user ini?" href="index.php?page=users&action=delete&id=' . e($r['id_user']) . '">Hapus</a></td></tr>';
    $table .= '</tbody></table></div>';
    return section_header('Manajemen User', 'Kelola akun dan role login.') . '<div class="grid-2 unequal">' . card('<h3>' . ($edit ? 'Edit User' : 'Tambah User') . '</h3>' . $form) . card('<h3>Daftar User</h3>' . $table) . '</div>';
}

function handle_settings_action(string $action): void {
    if (!can_write('settings')) throw new RuntimeException('Role Anda tidak diizinkan mengubah pengaturan.');
    if (is_post()) {
        foreach (['business_name','business_tagline','business_address','business_phone','business_owner','business_coordinate','low_stock_threshold'] as $key) save_setting($key, trim(post_value($key)));
        flash('success', 'Pengaturan berhasil disimpan.');
    }
    redirect_to('index.php?page=settings');
}

function page_settings(): string {
    $form = '<form method="post" class="form-grid compact-form" action="index.php?page=settings">' . form_input('Nama Usaha', 'business_name', setting_value('business_name'), 'text', true) . form_input('Tagline', 'business_tagline', setting_value('business_tagline'), 'text') . form_textarea('Alamat Usaha', 'business_address', setting_value('business_address')) . form_input('Telepon', 'business_phone', setting_value('business_phone'), 'text') . form_input('Pemilik Usaha', 'business_owner', setting_value('business_owner'), 'text') . form_input('Koordinat Lokasi', 'business_coordinate', setting_value('business_coordinate'), 'text') . form_input('Batas Stok Rendah Kg', 'low_stock_threshold', setting_value('low_stock_threshold', '30'), 'number') . '<div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Pengaturan</button></div></form>';
    $logs = all_rows('SELECT l.*, u.nama_user FROM activity_logs l LEFT JOIN users u ON u.id_user=l.id_user ORDER BY l.created_at DESC LIMIT 15');
    $logRows = '';
    foreach ($logs as $r) $logRows .= '<tr><td>' . e($r['created_at']) . '</td><td>' . e($r['nama_user']) . '</td><td>' . e($r['aktivitas']) . '</td><td>' . e($r['detail']) . '</td></tr>';
    return section_header('Pengaturan Sistem', 'Profil usaha, konfigurasi stok, dan log aktivitas.') . '<div class="grid-2 unequal">' . card('<h3>Profil Usaha</h3>' . $form) . card('<h3>Aktivitas Terbaru</h3><div class="table-responsive"><table class="data-table"><thead><tr><th>Waktu</th><th>User</th><th>Aktivitas</th><th>Detail</th></tr></thead><tbody>' . $logRows . '</tbody></table></div>') . '</div>';
}

function form_input(string $label, string $name, $value = '', string $type = 'text', bool $required = false): string {
    return '<label>' . e($label) . '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" ' . ($required ? 'required' : '') . '></label>';
}

function form_textarea(string $label, string $name, $value = '', bool $required = false): string {
    return '<label class="full-field">' . e($label) . '<textarea name="' . e($name) . '" rows="3" ' . ($required ? 'required' : '') . '>' . e($value) . '</textarea></label>';
}
