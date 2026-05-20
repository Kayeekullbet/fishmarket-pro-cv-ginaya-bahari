<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/schema.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    /*
     * Local:
     * Database disimpan di folder storage.
     *
     * Vercel:
     * Filesystem deployment Vercel tidak cocok untuk SQLite permanen.
     * Untuk demo, SQLite disimpan di folder sementara /tmp.
     * Data bisa reset kalau serverless function restart.
     */
    $storage = getenv('VERCEL')
        ? sys_get_temp_dir()
        : dirname(__DIR__) . '/storage';

    if (!is_dir($storage)) {
        mkdir($storage, 0775, true);
    }

    $database = $storage . '/fishmarket.sqlite';

    $pdo = new PDO('sqlite:' . $database);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initialize_database($pdo);

    return $pdo;
}

function all_rows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function one_row(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function execute_sql(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}

function last_insert_id(): string
{
    return db()->lastInsertId();
}