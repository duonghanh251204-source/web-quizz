<?php

declare(strict_types=1);

use App\Core\Env;

$provider = (string) Env::get('AI_PROVIDER', '');
if ($provider === '') {
    if (Env::get('GEMINI_API_KEY', '') !== '') {
        $provider = 'gemini';
    } elseif (Env::get('OPENAI_API_KEY', '') !== '') {
        $provider = 'openai';
    } elseif (Env::get('DEEPSEEK_API_KEY', '') !== '') {
        $provider = 'deepseek';
    } else {
        $provider = 'mock';
    }
}

return [
    'app_name' => Env::get('APP_NAME', 'LivQuiz Learning'),
    'base_url' => Env::get('APP_URL', 'http://localhost:8080'),
    'ai' => [
        'provider' => $provider,
        'openai' => [
            'model' => Env::get('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) Env::get('OPENAI_TIMEOUT', '60'),
        ],
        'deepseek' => [
            'model' => Env::get('DEEPSEEK_MODEL', 'deepseek-v4-flash'),
            'timeout' => (int) Env::get('DEEPSEEK_TIMEOUT', '120'),
        ],
    ],
];
