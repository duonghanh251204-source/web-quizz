<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\Router;
use App\Repositories\MysqlPlatformRepository;
use App\Repositories\PlatformRepositoryInterface;
use App\Services\AI\AIProviderInterface;
use App\Services\AI\ChatbotAIServiceProvider;
use App\Services\AI\GeminiAIProvider;
use App\Services\AI\MockAIProvider;
use App\Services\AI\OpenAIProvider;
use App\Services\AuthService;
use App\Services\DocumentTextExtractorService;
use App\Services\QuizGenerationService;
use App\Services\Prompt\QuizFromDocumentPromptBuilder;
use App\Services\SubmissionEvaluationService;
use App\Support\Container;
use App\Support\Logger;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Autoload.php';

spl_autoload_register(['App\\Core\\Autoload', 'load']);

Env::load(__DIR__ . '/.env');

$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';

$container = new Container();

$container->set('config', static fn () => $config);
$container->set('db.config', static fn () => $dbConfig);
$container->set(Logger::class, static fn () => new Logger(__DIR__ . '/storage/logs/app.log'));

$container->set(Database::class, static fn (Container $c) => new Database($c->get('db.config')));
$container->set(PlatformRepositoryInterface::class, static fn (Container $c) => new MysqlPlatformRepository($c->get(Database::class)->getConnection()));
$container->set(QuizFromDocumentPromptBuilder::class, static fn (Container $c) => new QuizFromDocumentPromptBuilder(
    $c->get(PlatformRepositoryInterface::class)
));
$container->set(SubmissionEvaluationService::class, static fn () => new SubmissionEvaluationService());
$container->set(DocumentTextExtractorService::class, static fn () => new DocumentTextExtractorService());

$container->set(AIProviderInterface::class, static function (Container $c) use ($config) {
    $repo = $c->get(PlatformRepositoryInterface::class);

    $adminProvider = strtolower(trim($repo->getSetting('ai_runtime_provider', '')));
    $provider = in_array($adminProvider, ['openai', 'gemini', 'deepseek'], true)
        ? $adminProvider
        : (string) $config['ai']['provider'];

    $openaiDbKey = trim($repo->getSetting('openai_api_key', ''));
    $geminiDbKey = trim($repo->getSetting('gemini_api_key', ''));
    $deepseekDbKey = trim($repo->getSetting('deepseek_api_key', ''));
    $openaiKey = $openaiDbKey !== '' ? $openaiDbKey : (string) Env::get('OPENAI_API_KEY', '');
    $geminiKey = $geminiDbKey !== '' ? $geminiDbKey : (string) Env::get('GEMINI_API_KEY', '');
    $deepseekKey = $deepseekDbKey !== '' ? $deepseekDbKey : (string) Env::get('DEEPSEEK_API_KEY', '');

    $openaiModelDb = trim($repo->getSetting('openai_model', ''));
    $geminiModelDb = trim($repo->getSetting('gemini_model', ''));
    $deepseekModelDb = trim($repo->getSetting('deepseek_model', ''));
    $openaiModel = $openaiModelDb !== '' ? $openaiModelDb : (string) Env::get('OPENAI_MODEL', $config['ai']['openai']['model']);
    $geminiModel = $geminiModelDb !== '' ? $geminiModelDb : (string) Env::get('GEMINI_MODEL', 'gemini-1.5-flash');
    $deepseekCfg = $config['ai']['deepseek'] ?? ['model' => 'deepseek-v4-flash', 'timeout' => 120];
    $deepseekModel = $deepseekModelDb !== '' ? $deepseekModelDb : (string) Env::get('DEEPSEEK_MODEL', (string) ($deepseekCfg['model'] ?? 'deepseek-v4-flash'));
    $deepseekTimeout = (int) ($deepseekCfg['timeout'] ?? 120);
    $geminiTimeout = (int) Env::get('GEMINI_TIMEOUT', '60');

    return match ($provider) {
        'openai' => new OpenAIProvider(
            apiKey: $openaiKey,
            model: $openaiModel,
            timeoutSeconds: (int) $config['ai']['openai']['timeout'],
            logger: $c->get(Logger::class)
        ),
        'deepseek' => new OpenAIProvider(
            apiKey: $deepseekKey,
            model: $deepseekModel,
            timeoutSeconds: $deepseekTimeout,
            logger: $c->get(Logger::class),
            endpoint: OpenAIProvider::DEEPSEEK_ENDPOINT,
            vendorLabel: 'DeepSeek'
        ),
        'gemini' => new GeminiAIProvider(
            apiKey: $geminiKey,
            model: $geminiModel,
            timeoutSeconds: $geminiTimeout,
            logger: $c->get(Logger::class)
        ),
        'chatbot_ai' => new ChatbotAIServiceProvider(
            timeoutSeconds: 300,
            logger: $c->get(Logger::class),
            serviceUrl: (string) Env::get('AI_SERVICE_URL', 'http://localhost:8000')
        ),
        default => new MockAIProvider(),
    };
});

$container->set(QuizGenerationService::class, static fn (Container $c) => new QuizGenerationService(
    promptBuilder: $c->get(QuizFromDocumentPromptBuilder::class),
    aiProvider: $c->get(AIProviderInterface::class),
    logger: $c->get(Logger::class)
));
$container->set(AuthService::class, static fn (Container $c) => new AuthService(
    repository: $c->get(PlatformRepositoryInterface::class),
    session: $c->get(\App\Core\Session::class)
));

$router = new Router($container);
require __DIR__ . '/routes.php';

return [
    'router' => $router,
    'container' => $container,
];
