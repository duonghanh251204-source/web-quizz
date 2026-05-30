<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function required(string $field, mixed $value, string $message): self
    {
        if ($value === null || $value === '') {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function betweenInt(string $field, mixed $value, int $min, int $max, string $message): self
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($intValue === false || $intValue < $min || $intValue > $max) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed, string $message): self
    {
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }
}
