<?php
require_once __DIR__ . '/auth.php';

function setting_value(string $key, string $default = ''): string {
    try {
        $row = one_row('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return $row['setting_value'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function main_menu(): array {
    return [
        ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => '⌁', 'group' => 'Utama'],
        ['page' => 'nelayan', 'label' => 'Data Nelayan', 'icon' => '⚓', 'group' => 'Master Data'],
        ['page' => 'ikan', 'label' => 'Data Ikan', 'icon' => '🐟', 'group' => 'Master Data'],
        ['page' => 'harga', 'label' => 'Harga Ikan', 'icon' => '🏷️', 'group' => 'Master Data'],
        ['page' => 'pembeli', 'label' => 'Data Pembeli', 'icon' => '🏪', 'group' => 'Master Data'],
        ['page' => 'pemasukan', 'label' => 'Pemasukan', 'icon' => '⇣', 'group' => 'Transaksi'],
        ['page' => 'penjualan', 'label' => 'Penjualan', 'icon' => '⇡', 'group' => 'Transaksi'],
        ['page' => 'stok', 'label' => 'Stok Ikan', 'icon' => '▦', 'group' => 'Operasional'],
        ['page' => 'laporan', 'label' => 'Laporan', 'icon' => '▣', 'group' => 'Operasional'],
        ['page' => 'users', 'label' => 'Manajemen User', 'icon' => '👥', 'group' => 'Sistem'],
        ['page' => 'settings', 'label' => 'Pengaturan', 'icon' => '⚙', 'group' => 'Sistem'],
    ];
}

function render_layout(string $title, string $content, string $page = 'dashboard'): void {
    $user = current_user();
    $business = setting_value('business_name', 'FishMarket Pro');
    $tagline = setting_value('business_tagline', 'Sistem transaksi ikan modern');
    $menuByGroup = [];
    foreach (main_menu() as $item) {
        if (can_access($item['page'])) $menuByGroup[$item['group']][] = $item;
    }
    $flash = flash_html();
    $roleBadge = $user ? user_role_badge($user['nama_role']) : '';
    $bodyClass = isset($_GET['print']) ? 'print-mode' : '';
    echo '<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . e($title) . ' · ' . e($business) . '</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="' . e($bodyClass) . '">
<div class="mobile-overlay" data-close-sidebar></div>
<aside class="sidebar" id="sidebar">
    <div class="brand-block">
        <a class="brand" href="index.php?page=dashboard" aria-label="Beranda">
            <img src="assets/img/logo.svg" alt="Logo">
            <span><strong>' . e($business) . '</strong><small>' . e($tagline) . '</small></span>
        </a>
        <button class="sidebar-close" data-close-sidebar type="button" aria-label="Tutup menu">×</button>
    </div>
    <nav class="nav-menu" aria-label="Menu utama">';
    foreach ($menuByGroup as $group => $items) {
        echo '<div class="nav-group"><p>' . e($group) . '</p>';
        foreach ($items as $item) {
            echo '<a class="nav-link ' . active_class($page, $item['page']) . '" href="index.php?page=' . e($item['page']) . '"><span class="nav-icon">' . e($item['icon']) . '</span><span>' . e($item['label']) . '</span></a>';
        }
        echo '</div>';
    }
    echo '</nav>
    <div class="sidebar-footer">
        <div class="mini-fish"><span></span><span></span><span></span></div>
        <small>Mode kerja: ' . e($user['role_keterangan'] ?? '-') . '</small>
    </div>
</aside>
<main class="app-shell">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" data-open-sidebar type="button" aria-label="Buka menu"><span></span><span></span><span></span></button>
            <div><h1>' . e($title) . '</h1><p>Panel operasional transaksi ikan berbasis ERD.</p></div>
        </div>
        <div class="topbar-actions">
            <button class="icon-button" type="button" data-theme-toggle aria-label="Ubah tema">◐</button>
            <a class="icon-button" href="index.php?page=stok" aria-label="Lihat stok">▦</a>
            <div class="user-pill">
                <span class="avatar">' . e(strtoupper(substr($user['nama_user'] ?? 'U', 0, 1))) . '</span>
                <span><strong>' . e($user['nama_user'] ?? '-') . '</strong><small>' . $roleBadge . '</small></span>
            </div>
            <a class="btn btn-outline" href="index.php?logout=1">Logout</a>
        </div>
    </header>
    ' . $flash . '
    <section class="page-content page-transition">
        ' . $content . '
    </section>
    <footer class="footer">© ' . current_year() . ' ' . e($business) . '. Dibuat untuk pengelolaan nelayan, stok, pemasukan, dan penjualan ikan.</footer>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>';
}

function empty_state(string $title, string $message, string $actionHtml = ''): string {
    return '<div class="empty-state"><img src="assets/img/fish-empty.svg" alt=""><h3>' . e($title) . '</h3><p>' . e($message) . '</p>' . $actionHtml . '</div>';
}

function card(string $content, string $extraClass = ''): string {
    return '<div class="card ' . e($extraClass) . '">' . $content . '</div>';
}

function section_header(string $title, string $subtitle = '', string $action = ''): string {
    return '<div class="section-header"><div><h2>' . e($title) . '</h2><p>' . e($subtitle) . '</p></div><div class="section-actions">' . $action . '</div></div>';
}

function role_notice(): string {
    return '<div class="notice"><strong>Mode ' . e(role_labels()[role_name()] ?? role_name()) . '.</strong> Hak akses menu menyesuaikan role login aktif. Tombol aksi otomatis disembunyikan bila role hanya boleh melihat data.</div>';
}
