<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Env;
use App\Repositories\PlatformRepositoryInterface;

final class AdminController extends Controller
{
    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public function index(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $this->redirect('/admin/dashboard');
    }

    /** @deprecated Dùng /admin/members */
    public function users(Request $request): void
    {
        $this->redirect('/admin/members');
    }

    public function dashboard(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $days = (int) $request->input('days', 14);
        if (!in_array($days, [7, 14, 30], true)) {
            $days = 14;
        }

        $stats = $repo->getAdminDashboardStats();
        $docSeries = $repo->getDocumentUploadCountsByDay($days);
        $qSeries = $repo->getQuestionActivityByDay($days);

        $labels = [];
        $startD = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        for ($i = 0; $i < $days; $i++) {
            $labels[] = $startD->modify('+' . $i . ' days')->format('Y-m-d');
        }

        $docMap = [];
        foreach ($docSeries as $row) {
            $docMap[(string) ($row['day'] ?? '')] = (int) ($row['count'] ?? 0);
        }
        $qAiMap = [];
        $qExtractMap = [];
        foreach ($qSeries as $row) {
            $d = (string) ($row['day'] ?? '');
            $qAiMap[$d] = (int) ($row['ai'] ?? 0);
            $qExtractMap[$d] = (int) ($row['extract'] ?? 0);
        }

        $chartDoc = [];
        $chartAi = [];
        $chartExtract = [];
        foreach ($labels as $d) {
            $chartDoc[] = $docMap[$d] ?? 0;
            $chartAi[] = $qAiMap[$d] ?? 0;
            $chartExtract[] = $qExtractMap[$d] ?? 0;
        }

        $this->render('admin/dashboard', [
            'adminActive' => 'dashboard',
            'stats' => $stats,
            'chartDays' => $days,
            'chartLabels' => $labels,
            'chartDoc' => $chartDoc,
            'chartAi' => $chartAi,
            'chartExtract' => $chartExtract,
        ]);
    }

    public function aiSettings(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        /** @var array<string, mixed> $appConfig */
        $appConfig = $this->container->get('config');
        $cfgProvider = (string) ($appConfig['ai']['provider'] ?? 'mock');
        $defaultOpenaiModel = (string) Env::get(
            'OPENAI_MODEL',
            (string) (($appConfig['ai']['openai']['model'] ?? 'gpt-4o-mini'))
        );

        $savedProvider = strtolower(trim($repo->getSetting('ai_runtime_provider', '')));
        $allowedProviders = ['openai', 'gemini', 'deepseek'];
        $formProvider = in_array($savedProvider, $allowedProviders, true)
            ? $savedProvider
            : (in_array($cfgProvider, $allowedProviders, true) ? $cfgProvider : 'openai');
        $effectiveProvider = in_array($savedProvider, $allowedProviders, true)
            ? $savedProvider
            : $cfgProvider;

        $openaiModelStored = trim($repo->getSetting('openai_model', ''));
        $geminiModelStored = trim($repo->getSetting('gemini_model', ''));
        $deepseekModelStored = trim($repo->getSetting('deepseek_model', ''));
        $defaultDeepseekModel = (string) (($appConfig['ai']['deepseek']['model'] ?? null) ?: 'deepseek-v4-flash');

        if ($formProvider === 'gemini') {
            $aiModelValue = $geminiModelStored !== '' ? $geminiModelStored : (string) Env::get('GEMINI_MODEL', 'gemini-1.5-flash');
        } elseif ($formProvider === 'deepseek') {
            $aiModelValue = $deepseekModelStored !== '' ? $deepseekModelStored : (string) Env::get('DEEPSEEK_MODEL', $defaultDeepseekModel);
        } else {
            $aiModelValue = $openaiModelStored !== '' ? $openaiModelStored : $defaultOpenaiModel;
        }

        $this->render('admin/ai', [
            'adminActive' => 'ai',
            'formProvider' => $formProvider,
            'effectiveProvider' => $effectiveProvider,
            'aiModelValue' => $aiModelValue,
            'openaiKeySet' => trim($repo->getSetting('openai_api_key', '')) !== '',
            'geminiKeySet' => trim($repo->getSetting('gemini_api_key', '')) !== '',
            'deepseekKeySet' => trim($repo->getSetting('deepseek_api_key', '')) !== '',
        ]);
    }

    public function saveAiSettings(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/admin/ai');

            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);

        $provider = strtolower(trim((string) $request->input('ai_provider', 'openai')));
        $allowedProviders = ['openai', 'gemini', 'deepseek'];
        if (!in_array($provider, $allowedProviders, true)) {
            $provider = 'openai';
        }
        $repo->setSetting('ai_runtime_provider', $provider);

        $modelInput = trim((string) $request->input('ai_model', ''));
        if ($modelInput === '') {
            $this->flash('error', 'Vui lòng nhập tên model.');
            $this->redirect('/admin/ai');

            return;
        }
        if ($provider === 'openai') {
            $repo->setSetting('openai_model', $modelInput);
        } elseif ($provider === 'gemini') {
            $repo->setSetting('gemini_model', $modelInput);
        } else {
            $repo->setSetting('deepseek_model', $modelInput);
        }

        if ($request->input('clear_openai_key', '') === '1') {
            $repo->setSetting('openai_api_key', '');
        }
        if ($request->input('clear_gemini_key', '') === '1') {
            $repo->setSetting('gemini_api_key', '');
        }
        if ($request->input('clear_deepseek_key', '') === '1') {
            $repo->setSetting('deepseek_api_key', '');
        }

        $keyInput = trim((string) $request->input('api_key', ''));
        if ($keyInput !== '') {
            if ($provider === 'openai') {
                $repo->setSetting('openai_api_key', $keyInput);
            } elseif ($provider === 'gemini') {
                $repo->setSetting('gemini_api_key', $keyInput);
            } else {
                $repo->setSetting('deepseek_api_key', $keyInput);
            }
        }

        $repo->setSetting('ai_quiz_prompt_template', '');

        $dbOpenaiKey = trim($repo->getSetting('openai_api_key', ''));
        $dbGeminiKey = trim($repo->getSetting('gemini_api_key', ''));
        $dbDeepseekKey = trim($repo->getSetting('deepseek_api_key', ''));
        $effOpenaiKey = $dbOpenaiKey !== '' ? $dbOpenaiKey : (string) Env::get('OPENAI_API_KEY', '');
        $effGeminiKey = $dbGeminiKey !== '' ? $dbGeminiKey : (string) Env::get('GEMINI_API_KEY', '');
        $effDeepseekKey = $dbDeepseekKey !== '' ? $dbDeepseekKey : (string) Env::get('DEEPSEEK_API_KEY', '');
        $keyReady = match ($provider) {
            'openai' => $effOpenaiKey !== '',
            'gemini' => $effGeminiKey !== '',
            'deepseek' => $effDeepseekKey !== '',
            default => false,
        };

        $providerLabel = match ($provider) {
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'deepseek' => 'DeepSeek',
            default => 'OpenAI',
        };
        $msg = 'Đã lưu: ' . $providerLabel . ', model ' . $modelInput . '.';
        if ($keyInput !== '') {
            $msg .= ' Key đã cập nhật.';
        }
        $msg .= $keyReady ? ' Đã có key — dùng tạo đề AI được.' : ' Chưa có key — nhập key và Lưu hoặc thêm vào .env.';

        $this->flash('success', $msg);
        $this->redirect('/admin/ai');
    }

    public function questions(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $quizId = (int) $request->input('quiz_id', 0);
        $sourceRaw = strtolower(trim((string) $request->input('source', '')));
        $sourceFilter = in_array($sourceRaw, ['ai', 'extract'], true) ? $sourceRaw : null;
        $repo = $this->container->get(PlatformRepositoryInterface::class);

        $this->render('questions/index', [
            'adminActive' => 'questions',
            'adminContext' => true,
            'filterAction' => '/admin/questions',
            'questions' => $repo->listQuestions($quizId > 0 ? $quizId : null, $sourceFilter),
            'quizzes' => $repo->listQuizzes(),
            'selectedQuizId' => $quizId,
            'selectedSource' => $sourceFilter ?? '',
        ]);
    }

    public function reports(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $status = trim((string) $request->input('status', ''));
        $statusFilter = in_array($status, ['open', 'resolved', 'dismissed'], true) ? $status : null;
        $repo = $this->container->get(PlatformRepositoryInterface::class);

        $this->render('admin/reports', [
            'adminActive' => 'reports',
            'reports' => $repo->listQuestionReports($statusFilter),
            'statusFilter' => $statusFilter ?? '',
        ]);
    }

    public function updateReportStatus(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/admin/reports');

            return;
        }

        $id = (int) $request->route('id');
        $status = strtolower(trim((string) $request->input('status', '')));
        $note = trim((string) $request->input('admin_note', ''));

        if (!in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $this->flash('error', 'Trạng thái không hợp lệ.');
            $this->redirect('/admin/reports');

            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $row = $repo->findQuestionReportById($id);
        if ($row === null) {
            $this->flash('error', 'Không tìm thấy báo cáo.');
            $this->redirect('/admin/reports');

            return;
        }

        $repo->updateQuestionReportStatus($id, $status, $note);
        $this->flash('success', 'Đã cập nhật trạng thái báo cáo.');
        $this->redirect('/admin/reports');
    }

    public function documents(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $docs = $repo->listDocuments(null);
        $enriched = [];
        $root = $this->projectRoot();
        foreach ($docs as $d) {
            $path = $root . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) ($d['stored_file_path'] ?? ''));
            $size = is_file($path) ? (int) filesize($path) : 0;
            $d['file_size'] = $size;
            $enriched[] = $d;
        }

        $this->render('admin/documents', [
            'adminActive' => 'documents',
            'documents' => $enriched,
        ]);
    }

    public function deleteDocument(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/admin/documents');

            return;
        }

        $id = (int) $request->route('id');
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $doc = $repo->findDocumentById($id);
        if ($doc === null) {
            $this->flash('error', 'Không tìm thấy tài liệu.');
            $this->redirect('/admin/documents');

            return;
        }

        $root = $this->projectRoot();
        $path = $root . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) ($doc['stored_file_path'] ?? ''));
        if (is_file($path)) {
            @unlink($path);
        }

        $repo->deleteDocument($id);
        $this->flash('success', 'Đã xóa tài liệu và tệp đính kèm (nếu có).');
        $this->redirect('/admin/documents');
    }

    public function members(Request $request): void
    {
        $user = $this->requireAuth(['admin']);
        if ($user === null) {
            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $users = $repo->listUsers();

        $this->render('admin/members', [
            'adminActive' => 'members',
            'users' => $users,
            'totalUsers' => count($users),
            'adminCount' => $repo->countUsersByRole('admin'),
            'learnerCount' => $repo->countUsersByRole('user'),
        ]);
    }

    public function updateUserRole(Request $request): void
    {
        $actor = $this->requireAuth(['admin']);
        if ($actor === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/admin/members');

            return;
        }

        $userId = (int) $request->route('id');
        $newRole = strtolower(trim((string) $request->input('role', '')));

        if (!in_array($newRole, ['user', 'admin'], true)) {
            $this->flash('error', 'Vai trò không hợp lệ.');
            $this->redirect('/admin/members');

            return;
        }

        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $target = $repo->findUserById($userId);

        if ($target === null) {
            $this->flash('error', 'Không tìm thấy người dùng.');
            $this->redirect('/admin/members');

            return;
        }

        if (($target['role'] ?? '') === 'admin' && $newRole === 'user') {
            if ($repo->countUsersByRole('admin') <= 1) {
                $this->flash('error', 'Không thể gỡ quyền quản trị viên cuối cùng.');
                $this->redirect('/admin/members');

                return;
            }
        }

        $repo->updateUserRole($userId, $newRole);
        $this->flash('success', 'Đã cập nhật vai trò người dùng.');
        $this->redirect('/admin/members');
    }

    public function updateUserLock(Request $request): void
    {
        $actor = $this->requireAuth(['admin']);
        if ($actor === null) {
            return;
        }

        if (!$this->verifyCsrfToken($request)) {
            $this->flash('error', 'Phiên làm việc không hợp lệ.');
            $this->redirect('/admin/members');

            return;
        }

        $userId = (int) $request->route('id');
        if ($userId === (int) ($actor['id'] ?? 0)) {
            $this->flash('error', 'Bạn không thể khóa chính mình.');
            $this->redirect('/admin/members');

            return;
        }

        $locked = $request->input('locked', '0') === '1' || $request->input('locked', '') === '1';
        $repo = $this->container->get(PlatformRepositoryInterface::class);
        $target = $repo->findUserById($userId);
        if ($target === null) {
            $this->flash('error', 'Không tìm thấy người dùng.');
            $this->redirect('/admin/members');

            return;
        }

        if (($target['role'] ?? '') === 'admin' && $locked) {
            $this->flash('error', 'Không khóa tài khoản quản trị viên. Hãy hạ quyền trước nếu cần.');
            $this->redirect('/admin/members');

            return;
        }

        $repo->updateUserLocked($userId, $locked);
        $this->flash('success', $locked ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.');
        $this->redirect('/admin/members');
    }
}
