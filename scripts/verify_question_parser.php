<?php

declare(strict_types=1);

use App\Services\DocumentTextExtractorService;
use App\Services\QuizGenerationService;

$projectRoot = dirname(__DIR__);
$app = require $projectRoot . '/bootstrap.php';
$container = $app['container'];

/** @var DocumentTextExtractorService $extractor */
$extractor = $container->get(DocumentTextExtractorService::class);
/** @var QuizGenerationService $generator */
$generator = $container->get(QuizGenerationService::class);

$args = parseArgs($argv);
$targetDir = resolveTargetDir($projectRoot, $args['dir']);
$minQuestions = max(1, (int) $args['min']);
$withPdf = $args['with_pdf'];

if (!is_dir($targetDir)) {
    fwrite(STDERR, "Target directory not found: {$targetDir}" . PHP_EOL);
    exit(2);
}

$files = scandir($targetDir);
if (!is_array($files)) {
    fwrite(STDERR, 'Cannot scan target directory.' . PHP_EOL);
    exit(2);
}

$total = 0;
$ok = 0;
$warn = 0;
$fail = 0;

echo 'VERIFY_DIR: ' . $targetDir . PHP_EOL;
echo 'MIN_SOURCE_QUESTIONS: ' . $minQuestions . PHP_EOL;
echo 'WITH_PDF: ' . ($withPdf ? 'yes' : 'no') . PHP_EOL;
echo str_repeat('-', 90) . PHP_EOL;

foreach ($files as $name) {
    if ($name === '.' || $name === '..') {
        continue;
    }

    $path = $targetDir . DIRECTORY_SEPARATOR . $name;
    if (!is_file($path)) {
        continue;
    }

    if (str_starts_with($name, '~$')) {
        echo "[SKIP][temp] {$name}" . PHP_EOL;
        continue;
    }

    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['docx', 'txt', 'pdf'], true)) {
        echo "[SKIP][ext] {$name}" . PHP_EOL;
        continue;
    }

    if ($extension === 'pdf' && !$withPdf) {
        echo "[SKIP][pdf] {$name}" . PHP_EOL;
        continue;
    }

    $total++;
    $isSolution = isSolutionFileName($name);
    $kind = $isSolution ? 'solution' : 'source';

    try {
        $content = $extractor->extract($path, $extension);
        $generated = $generator->extractQuestionsFromDocument($name, $content);
        $questionCount = count($generated['questions']);

        if (!$isSolution && $questionCount < $minQuestions) {
            echo "[FAIL][{$kind}] {$name} => {$questionCount} questions" . PHP_EOL;
            $fail++;
            continue;
        }

        if ($isSolution && $questionCount < 1) {
            echo "[WARN][{$kind}] {$name} => {$questionCount} questions" . PHP_EOL;
            $warn++;
            continue;
        }

        echo "[OK][{$kind}] {$name} => {$questionCount} questions" . PHP_EOL;
        $ok++;
    } catch (Throwable $throwable) {
        $message = $throwable->getMessage();

        if ($isSolution) {
            echo "[WARN][{$kind}] {$name} => {$message}" . PHP_EOL;
            $warn++;
            continue;
        }

        echo "[FAIL][{$kind}] {$name} => {$message}" . PHP_EOL;
        $fail++;
    }
}

echo str_repeat('-', 90) . PHP_EOL;
echo "SUMMARY: total={$total} ok={$ok} warn={$warn} fail={$fail}" . PHP_EOL;

if ($fail > 0) {
    exit(1);
}

exit(0);

/** @return array{dir:string, min:int, with_pdf:bool} */
function parseArgs(array $argv): array
{
    $result = [
        'dir' => '',
        'min' => 1,
        'with_pdf' => false,
    ];

    foreach ($argv as $index => $arg) {
        if ($index === 0 || !is_string($arg)) {
            continue;
        }

        if (str_starts_with($arg, '--dir=')) {
            $result['dir'] = trim(substr($arg, 6));
            continue;
        }

        if (str_starts_with($arg, '--min=')) {
            $result['min'] = (int) trim(substr($arg, 6));
            continue;
        }

        if ($arg === '--with-pdf') {
            $result['with_pdf'] = true;
        }
    }

    return $result;
}

function resolveTargetDir(string $projectRoot, string $rawDir): string
{
    if (trim($rawDir) !== '') {
        return isAbsolutePath($rawDir)
            ? $rawDir
            : $projectRoot . DIRECTORY_SEPARATOR . $rawDir;
    }

    $entries = scandir($projectRoot);
    if (!is_array($entries)) {
        return $projectRoot . DIRECTORY_SEPARATOR . 'dữ liệu test';
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $candidate = $projectRoot . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($candidate)) {
            continue;
        }

        if (stripos($entry, 'test') !== false) {
            return $candidate;
        }
    }

    return $projectRoot . DIRECTORY_SEPARATOR . 'dữ liệu test';
}

function isAbsolutePath(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return true;
    }

    return str_starts_with($path, '/') || str_starts_with($path, '\\\\');
}

function isSolutionFileName(string $name): bool
{
    $upper = function_exists('mb_strtoupper')
        ? mb_strtoupper($name, 'UTF-8')
        : strtoupper($name);

    foreach (['LỜI GIẢI', 'LOI GIAI', 'SOLUTION', 'ANSWER KEY'] as $token) {
        if (str_contains($upper, $token)) {
            return true;
        }
    }

    return preg_match(
        '/(?:l\S{0,3}i\s*giai|loi\s*giai|solution|answer\s*key)/iu',
        $name
    ) === 1;
}
