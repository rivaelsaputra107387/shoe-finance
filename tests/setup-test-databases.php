<?php

// Creates the databases used by the test suite. Only ever touches names starting with finlog_test.

require __DIR__.'/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';

if (! in_array($host, ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, "Dibatalkan: database tes hanya boleh dibuat di lokal, DB_HOST saat ini {$host}.\n");
    exit(1);
}

$pdo = new PDO("mysql:host={$host};port={$port}", $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '');

foreach (['finlog_test_central', 'finlog_test_unit_a', 'finlog_test_unit_b', 'finlog_test_unit_c'] as $database) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "{$database}: siap\n";
}
