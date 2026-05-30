<?php

declare(strict_types=1);

use App\Core\Env;

require_once __DIR__ . '/../app/Core/Autoload.php';
spl_autoload_register(['App\\Core\\Autoload', 'load']);

Env::load(__DIR__ . '/../.env');
$dbConfig = require __DIR__ . '/../config/database.php';

$database = (string) ($dbConfig['database'] ?? 'prx');
$host = (string) ($dbConfig['host'] ?? '127.0.0.1');
$port = (string) ($dbConfig['port'] ?? '3306');
$charset = (string) ($dbConfig['charset'] ?? 'utf8mb4');
$username = (string) ($dbConfig['username'] ?? 'root');
$password = (string) ($dbConfig['password'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    throw new \RuntimeException('DB_DATABASE chi duoc chua chu, so va dau gach duoi.');
}

$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
if ($schema === false) {
    throw new \RuntimeException('Khong the doc file database/schema.sql');
}

try {
    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=%s', $host, $port, $charset);
    $pdo = new \PDO($serverDsn, $username, $password, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci',
        $database,
        $charset,
        $charset
    ));
    $pdo->exec(sprintf('USE `%s`', $database));
    $pdo->exec($schema);

    echo 'MySQL initialized at: ' . $host . ':' . $port . '/' . $database . PHP_EOL;
} catch (\PDOException $exception) {
    throw new \RuntimeException('Khong the khoi tao MySQL: ' . $exception->getMessage(), 0, $exception);
}