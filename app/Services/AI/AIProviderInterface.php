<?php

declare(strict_types=1);

namespace App\Services\AI;

interface AIProviderInterface
{
    public function generate(string $prompt): AIResult;
}
