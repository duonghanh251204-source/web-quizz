<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Provider có thể chạy nhiều prompt trong một “vòng” HTTP song song (curl_multi).
 */
interface ConcurrentAIProviderInterface
{
    /**
     * @param array<int, string> $prompts
     * @return array<int, AIResult> Cùng thứ tự chỉ mục như $prompts
     */
    public function generateConcurrent(array $prompts): array;
}
