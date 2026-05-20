<?php
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string {
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function kg($value): string {
    return number_format((float)$value, 2, ',', '.') . ' kg';
}

function tanggal_id($value): string {
    if (!$value) return '-';
    $ts = strtotime((string)$value);
    if (!$ts) return e($value);
    return date('d/m/Y', $ts);
}

function post_value(string $key, $default = '') {
    return $_POST[$key] ?? $default;
}

function request_value(string $key, $default = '') {
    return $_REQUEST[$key] ?? $default;
}

function redirect_to(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_html(): string {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    if (!$items) return '';
    $html = '<div class="flash-stack">';
    foreach ($items as $item) {
        $type = e($item['type']);
        $message = e($item['message']);
        $html .= "<div class=\"flash flash-{$type}\"><span class=\"flash-dot\"></span><span>{$message}</span><button type=\"button\" class=\"flash-close\" aria-label=\"Tutup\">×</button></div>";
    }
    return $html . '</div>';
}

function active_class(string $page, string $target): string {
    return $page === $target ? 'active' : '';
}

function is_post(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function selected($a, $b): string {
    return (string)$a === (string)$b ? 'selected' : '';
}

function checked($a, $b): string {
    return (string)$a === (string)$b ? 'checked' : '';
}

function badge(string $text, string $tone = 'neutral'): string {
    return '<span class="badge badge-' . e($tone) . '">' . e($text) . '</span>';
}

function current_year(): string {
    return date('Y');
}

function app_url(array $params = []): string {
    $query = http_build_query(array_merge($_GET, $params));
    return 'index.php' . ($query ? '?' . $query : '');
}


function fish_image(?string $filename): string {
    $filename = trim((string)$filename);
    if ($filename === '') return 'fish-tuna.jpg';
    $map = [
        'fish-tuna.svg' => 'fish-tuna.jpg',
        'fish-cakalang.svg' => 'fish-cakalang.jpg',
        'fish-tongkol.svg' => 'fish-tongkol.jpg',
        'fish-kakap.svg' => 'fish-kakap.jpg',
        'fish-kerapu.svg' => 'fish-kerapu.jpg',
        'fish-bandeng.svg' => 'fish-bandeng.jpg',
    ];
    return $map[$filename] ?? $filename;
}

function remote_fish_images(): array {
    return [
        'fish-sunu.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Plectropomus_leopardus.jpg?width=760',
        'fish-terapung.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Terapon_jarbua.jpg?width=760',
        'fish-tidar.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fish_Market_%28Unsplash%29.jpg?width=760',
        'fish-tanete.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fish-market.jpg?width=760',
        'fish-tenggiri.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Scomberomorus_commerson.jpg?width=760',
        'fish-kembung.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Rastrelliger_kanagurta_JNC2855.JPG?width=760',
        'fish-layang.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Decapterus_macrosoma.png?width=760',
        'fish-baronang.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Siganus_canaliculatus.jpg?width=760',
        'fish-kuwe.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Caranx_ignobilis.jpg?width=760',
        'fish-bawal.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Pampus_argenteus_20020400.jpg?width=760',
        'fish-lemuru.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Lemuru_090604-0013_manke.JPG?width=760',
        'fish-teri.jpg' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Stolephorus_indicus.jpg?width=760',
    ];
}

function fish_src(?string $filename): string {
    $name = fish_image($filename);
    if (preg_match('/^https?:\/\//i', $name)) return $name;
    $remote = remote_fish_images();
    if (isset($remote[$name])) return $remote[$name];
    return 'assets/img/' . $name;
}

function fish_image_options(): array {
    return [
        'fish-sunu.jpg',
        'fish-terapung.jpg',
        'fish-tidar.jpg',
        'fish-tanete.jpg',
        'fish-tuna.jpg',
        'fish-cakalang.jpg',
        'fish-tongkol.jpg',
        'fish-kakap.jpg',
        'fish-kerapu.jpg',
        'fish-bandeng.jpg',
        'fish-tenggiri.jpg',
        'fish-kembung.jpg',
        'fish-layang.jpg',
        'fish-baronang.jpg',
        'fish-kuwe.jpg',
        'fish-bawal.jpg',
        'fish-lemuru.jpg',
        'fish-teri.jpg',
    ];
}

function base_url(string $path = ''): string {
    return $path;
}
