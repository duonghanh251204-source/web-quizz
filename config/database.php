<?php

declare(strict_types=1);

use App\Core\Env;

$host = (string) Env::get('DB_HOST', '127.0.0.1');
$port = (string) Env::get('DB_PORT', '3306');
$database = (string) Env::get('DB_DATABASE', 'prx');
$username = (string) Env::get('DB_USERNAME', 'root');
$password = (string) Env::get('DB_PASSWORD', '');
$charset = (string) Env::get('DB_CHARSET', 'utf8mb4');

return [
    'driver' => 'mysql',
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'charset' => $charset,
    'username' => $username,
    'password' => $password,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $database,
        $charset
    ),
    'options' => [
        \PDO::ATTR_EMULATE_PREPARES => false,
    ],
];