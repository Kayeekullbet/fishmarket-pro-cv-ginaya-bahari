<?php
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user !== null && (int)$user['id_user'] === (int)$_SESSION['user_id']) return $user;
    $user = one_row('SELECT u.*, r.nama_role, r.keterangan AS role_keterangan FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.id_user = ?', [$_SESSION['user_id']]);
    return $user ?: null;
}

function role_name(): string {
    $u = current_user();
    return $u['nama_role'] ?? 'guest';
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function login_attempt(string $username, string $password, string $role = ''): bool {
    $sql = 'SELECT u.*, r.nama_role FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.username = ? AND u.status = ?';
    $user = one_row($sql, [$username, 'aktif']);
    if (!$user) return false;
    if ($role !== '' && $user['nama_role'] !== $role) return false;
    if (!password_verify($password, $user['password'])) return false;
    $_SESSION['user_id'] = (int)$user['id_user'];
    log_activity('Login', 'Pengguna masuk sebagai ' . $user['nama_role']);
    return true;
}

function logout_user(): void {
    if (is_logged_in()) log_activity('Logout', 'Pengguna keluar dari sistem');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function role_labels(): array {
    return [
        'admin' => 'Admin',
        'owner' => 'Owner',
        'pembelian' => 'Petugas Pembelian',
        'penjualan' => 'Petugas Penjualan',
        'gudang' => 'Petugas Gudang',
        'auditor' => 'Auditor',
    ];
}

function default_credentials(): array {
    return [
        'admin' => ['username' => 'admin', 'password' => 'admin123', 'hint' => 'Akses seluruh sistem'],
        'owner' => ['username' => 'owner', 'password' => 'owner123', 'hint' => 'Dashboard, stok, dan laporan'],
        'pembelian' => ['username' => 'beli', 'password' => 'beli123', 'hint' => 'Nelayan dan pemasukan ikan'],
        'penjualan' => ['username' => 'jual', 'password' => 'jual123', 'hint' => 'Pembeli, penjualan, dan nota'],
        'gudang' => ['username' => 'gudang', 'password' => 'gudang123', 'hint' => 'Stok dan mutasi'],
        'auditor' => ['username' => 'auditor', 'password' => 'auditor123', 'hint' => 'Mode lihat saja'],
    ];
}

function page_roles(): array {
    return [
        'dashboard' => ['admin','owner','pembelian','penjualan','gudang','auditor'],
        'nelayan' => ['admin','pembelian'],
        'ikan' => ['admin','pembelian','gudang'],
        'harga' => ['admin','pembelian'],
        'pembeli' => ['admin','penjualan'],
        'pemasukan' => ['admin','pembelian','owner','auditor'],
        'penjualan' => ['admin','penjualan','owner','auditor'],
        'stok' => ['admin','owner','pembelian','penjualan','gudang','auditor'],
        'laporan' => ['admin','owner','auditor'],
        'users' => ['admin'],
        'settings' => ['admin','owner'],
    ];
}

function can_access(string $page): bool {
    $roles = page_roles();
    if (!isset($roles[$page])) return false;
    return in_array(role_name(), $roles[$page], true);
}

function can_write(string $page): bool {
    $role = role_name();
    if ($role === 'admin') return true;
    if ($page === 'nelayan' && $role === 'pembelian') return true;
    if ($page === 'ikan' && $role === 'pembelian') return true;
    if ($page === 'harga' && $role === 'pembelian') return true;
    if ($page === 'pembeli' && $role === 'penjualan') return true;
    if ($page === 'pemasukan' && $role === 'pembelian') return true;
    if ($page === 'penjualan' && $role === 'penjualan') return true;
    if ($page === 'settings' && in_array($role, ['admin','owner'], true)) return true;
    return false;
}

function require_access(string $page): void {
    if (!can_access($page)) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>Akses Ditolak</title><style>body{font-family:Arial;background:#eef7fb;display:grid;place-items:center;height:100vh;color:#102a43}.box{background:white;padding:32px;border-radius:22px;box-shadow:0 20px 60px #1233}.btn{background:#0f766e;color:white;padding:12px 18px;border-radius:12px;text-decoration:none}</style><div class="box"><h1>Akses ditolak</h1><p>Role Anda tidak memiliki izin untuk membuka halaman ini.</p><a class="btn" href="index.php?page=dashboard">Kembali ke dashboard</a></div>';
        exit;
    }
}

function log_activity(string $activity, string $detail = ''): void {
    try {
        $uid = $_SESSION['user_id'] ?? null;
        execute_sql('INSERT INTO activity_logs (id_user, aktivitas, detail) VALUES (?, ?, ?)', [$uid, $activity, $detail]);
    } catch (Throwable $e) {
        // Logging must never break the main request.
    }
}

function user_role_badge(string $role): string {
    $map = [
        'admin' => 'danger',
        'owner' => 'primary',
        'pembelian' => 'success',
        'penjualan' => 'warning',
        'gudang' => 'info',
        'auditor' => 'neutral',
    ];
    return badge(role_labels()[$role] ?? $role, $map[$role] ?? 'neutral');
}
