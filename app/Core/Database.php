<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private ?PDO $connection = null;

    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    public function getConnection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        try {
            $dsn = (string) ($this->config['dsn'] ?? '');
            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;
            $options = $this->config['options'] ?? [];

            $this->connection = new PDO(
                $dsn,
                is_string($username) ? $username : null,
                is_string($password) ? $password : null,
                is_array($options) ? $options : []
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Khong the ket noi database: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->connection;
    }
}
