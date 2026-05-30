<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AIResult
{
    public function __construct(
        public string $content,
        public string $model,
        public string $rawResponse
    ) {
    }
}
