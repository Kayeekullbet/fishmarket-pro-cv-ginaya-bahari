<?php
session_start();
require_once __DIR__ . '/../src/pages.php';

// Force database initialization early.
db();

if (isset($_GET['logout'])) {
    logout_user();
    redirect_to('index.php');
}

if (!is_logged_in()) {
    if (is_post()) {
        $role = trim(post_value('role'));
        if (login_attempt(trim(post_value('username')), trim(post_value('password')), $role)) {
            redirect_to('index.php?page=dashboard');
        }
        flash('danger', 'Login gagal. Periksa role, username, dan password.');
    }
    render_login_page();
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
if (!array_key_exists($page, page_roles())) $page = 'dashboard';
require_access($page);
handle_request_actions($page);
$content = render_page($page);
render_layout(page_title($page), $content, $page);

function render_login_page(): void {
    $business = setting_value('business_name', 'FishMarket Pro');
    $credentials = default_credentials();
    $roleLabels = role_labels();
    $flash = flash_html();
    echo '<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · ' . e($business) . '</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    ' . $flash . '
    <div class="login-ocean">
        <span></span><span></span><span></span><span></span>
    </div>
    <main class="login-wrap">
        <section class="login-hero">
            <a class="brand login-brand" href="index.php"><img src="assets/img/logo.svg" alt="Logo"><span><strong>' . e($business) . '</strong><small>Sistem informasi transaksi ikan</small></span></a>
            <h1>Kelola nelayan, ikan, stok, pembelian, penjualan, dan laporan dalam satu dashboard.</h1>
            <p>Login demo menggunakan role. Setiap role akan menampilkan menu dan aksi yang berbeda agar alur kerja lebih aman.</p>
            <div class="login-fish-showcase">
                <img src="' . e(fish_src('fish-sunu.jpg')) . '" alt="Ikan Sunu">
                <img src="' . e(fish_src('fish-terapung.jpg')) . '" alt="Ikan Terapung">
                <img src="' . e(fish_src('fish-tanete.jpg')) . '" alt="Ikan Tanete">
            </div>
        </section>
        <section class="login-card">
            <div class="login-card-head">
                <span class="eyebrow">Masuk sistem</span>
                <h2>Pilih Role Login</h2>
                <p>Klik kartu role untuk mengisi akun demo otomatis, atau ketik manual.</p>
            </div>
            <div class="role-picker">';
    foreach ($credentials as $role => $data) {
        echo '<button type="button" class="role-card" data-role="' . e($role) . '" data-username="' . e($data['username']) . '" data-password="' . e($data['password']) . '"><strong>' . e($roleLabels[$role]) . '</strong><small>' . e($data['hint']) . '</small></button>';
    }
    echo '</div>
            <form method="post" class="login-form" id="loginForm">
                <input type="hidden" name="role" id="roleField" value="admin">
                <label>Username<input id="usernameField" type="text" name="username" value="admin" required autocomplete="username"></label>
                <label>Password<input id="passwordField" type="password" name="password" value="admin123" required autocomplete="current-password"></label>
                <button class="btn btn-primary btn-wide" type="submit">Masuk Dashboard</button>
            </form>
            <div class="demo-note"><strong>Akun awal:</strong> admin/admin123, owner/owner123, beli/beli123, jual/jual123, gudang/gudang123, auditor/auditor123.</div>
        </section>
    </main>
<script src="assets/js/app.js"></script>
</body>
</html>';
}
