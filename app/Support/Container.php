<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use RuntimeException;

final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->factories)) {
            throw new RuntimeException("Service not found: {$id}");
        }

        $instance = ($this->factories[$id])($this);
        $this->instances[$id] = $instance;

        return $instance;
    }
}
