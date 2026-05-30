# Nền tảng Bài Kiểm Tra PRX (PHP MVC)

Hệ thống web tạo và vận hành bài kiểm tra trắc nghiệm bằng AI, có xác thực và phân quyền theo vai trò.

## Chức năng chính (đã triển khai)
1. Tải tài liệu (PDF, DOCX, TXT) cho quản trị viên.
2. AI sinh câu hỏi từ nội dung tài liệu cho quản trị viên.
3. Quản lý câu hỏi đầy đủ thêm/sửa/xóa cho quản trị viên.
4. Tạo bài kiểm tra từ ngân hàng dữ liệu.
5. Làm bài cho học viên và quản trị viên.
6. Xuất đề thi dạng văn bản cho quản trị viên.

## Quyền theo vai trò
- Quản trị viên:
  - Toàn quyền hệ thống.
  - Tải tài liệu.
  - Sinh câu hỏi bằng AI.
  - Quản lý toàn bộ câu hỏi.
  - Tạo bài kiểm tra.
  - Xuất đề thi.
- Học viên:
  - Làm bài.
  - Xem kết quả của mình.

## Mô-đun cốt lõi
- Người dùng: `id`, `name`, `email`, `password`, `role`
- Tài liệu: tệp tải lên và nội dung trích xuất.
- Câu hỏi: `question_content`, 4 lựa chọn và `correct_answer`.
- Bài kiểm tra: tiêu đề và danh sách câu hỏi.
- Bài nộp: đáp án người làm.
- Kết quả: điểm số cùng thống kê đúng/sai.

## Kiến trúc
- PHP MVC thuần (không dùng framework ngoài).
- OOAD + Service Layer + Repository Pattern.
- MySQL (phpMyAdmin/XAMPP) + PDO.
- Lớp trừu tượng AI:
  - `OpenAIProvider`
  - `MockAIProvider`

## Thiết kế mô-đun AI
- Tài liệu tham chiếu: `docs/ai_module.md`
- Đầu vào: nội dung tài liệu.
- Đầu ra: danh sách câu hỏi trắc nghiệm dạng JSON (`question`, `options[4]`, `correct`).
- Bao gồm thiết kế prompt và luồng xử lý.

## Chạy dự án
1. Tạo tệp môi trường:
```bash
cp .env.example .env
```

2. Cấu hình cơ sở dữ liệu trong `.env`:
```ini
APP_URL="http://localhost/PRX"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prx
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

3. Khởi tạo cơ sở dữ liệu (tự tạo database và bảng):
```bash
php scripts/init_db.php
```

Hoặc nhập thủ công trong phpMyAdmin:
- Tạo database `prx`.
- Chạy tệp `database/schema.sql` trong tab SQL.

4. Chạy bằng Apache của XAMPP:
- Đặt dự án tại `E:\xampp\htdocs\PRX`.
- Mở địa chỉ `http://localhost/PRX`.

5. Hoặc chạy bằng máy chủ phát triển của PHP:
```bash
php -S localhost:8080 -t .
```

## Tài khoản quản trị viên mặc định
- Email: `admin@prx.local`
- Mật khẩu: `Admin@123`

## Cấu hình AI
```ini
AI_PROVIDER=mock
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_TIMEOUT=60
```

- `mock`: chế độ mô phỏng, không cần khóa API.
- `openai`: cần khai báo `OPENAI_API_KEY`.

## Ghi chú tải tệp
- Hỗ trợ: `.txt`, `.docx`, `.pdf`
- PDF cần công cụ `pdftotext` trên máy chủ để trích xuất văn bản.
- Giới hạn dung lượng tệp: 10MB.# Nền Tảng Bài Kiểm Tra PRX (PHP MVC)

PRX là hệ thống web quản lý toàn bộ vòng đời bài kiểm tra trắc nghiệm: tải tài liệu (TXT/DOCX/PDF), trích xuất nội dung, nhận diện cấu trúc câu hỏi trực tiếp từ file import, biên tập câu hỏi ở màn xem trước, gợi ý thêm câu hỏi bằng AI theo thao tác chủ động của tác giả, phát hành đề qua liên kết, thu bài, chấm điểm tự động và tổng hợp bảng xếp hạng. Dự án được tổ chức theo kiến trúc PHP MVC thuần, tách lớp `Controller -> Service -> Repository`, dùng `MySQL + PDO`, xác thực phiên làm việc bằng `Session`, bảo vệ biểu mẫu bằng `CSRF`, và phân quyền rõ ràng theo vai trò `admin/user`.

README này là bản mô tả kỹ thuật chi tiết của trạng thái repo hiện tại: gồm bản đồ route và quyền truy cập, bản đồ class/phương thức theo từng tầng, schema dữ liệu và cấu hình runtime, liên kết View/Assets, luồng nghiệp vụ end-to-end, cây thư mục đầy đủ và danh mục file chi tiết.

## Chỉ Mục Nhanh

- Tổng quan kiến trúc
- Route và phân quyền truy cập
- Bản đồ Controller
- Bản đồ Service
- Bản đồ Core/Support/Exception
- Bản đồ Repository
- Schema và cấu hình
- Bản đồ View
- Public assets và layout
- Runtime/storage/scripts/backup
- Luồng nghiệp vụ end-to-end
- Cây thư mục đầy đủ
- Danh mục file đầy đủ

## Mô Đun AI Hiện Tại (Cập Nhật Theo Code)

Mô đun AI hiện tại vận hành theo nguyên tắc: `import = parser cấu trúc từ file`, `AI = gợi ý bổ sung theo yêu cầu tác giả`.

### 1. AI chỉ dùng để gợi ý, không dùng để thay thế import

- Khi tạo đề từ tài liệu (`POST /quizzes`), hệ thống dùng parser nội bộ:
  - `QuizGenerationService::extractQuestionsFromDocument()`
  - nguồn là nội dung đã trích xuất từ file upload.
- Không gọi AI để tự sinh ngẫu nhiên tại bước import.
- Nếu parser không nhận diện được câu hỏi hợp lệ, trả lỗi để người dùng kiểm tra lại định dạng file.

### 2. Điểm kích hoạt AI

- AI chỉ được gọi tại bước xem trước/sửa:
  - endpoint: `POST /quizzes/preview/suggest-ai`
  - method xử lý: `QuizController::suggestAiPreview()`
- Có CSRF bắt buộc.
- Số câu gợi ý AI do người dùng chọn: `suggestion_count` trong khoảng `1..10`.

### 3. Luồng dữ liệu gợi ý AI

- AI tạo danh sách `suggested_questions` lưu trong draft session (`quiz_generation_draft`).
- Hệ thống tự loại câu trùng bằng fingerprint trước khi đưa vào danh sách gợi ý.
- Tác giả phải tick `include_suggestions[]` để xác nhận câu nào được thêm.
- Khi lưu đề (`POST /quizzes/preview/save`), chỉ các câu được tick mới được merge vào bộ câu chính.

### 4. Cấu hình provider AI

- Cấu hình tại `config/app.php`:
  - ưu tiên `AI_PROVIDER` từ `.env`
  - nếu chưa đặt `AI_PROVIDER`, tự fallback:
    - có `OPENAI_API_KEY` -> `openai`
    - không có key -> `mock`
- Provider hiện có:
  - `OpenAIProvider` (gọi `https://api.openai.com/v1/chat/completions`)
  - `MockAIProvider` (mô phỏng nội bộ để dev/test)

### 5. Cách gọi OpenAI hiện tại

- File: `app/Services/AI/OpenAIProvider.php`
- Dùng `response_format = json_object`.
- Prompt system yêu cầu trả JSON đúng schema.
- Có log lỗi và ném `AIProviderException` khi lỗi kết nối/phản hồi.

### 6. Prompt và chuẩn đầu ra

- File: `app/Services/Prompt/QuizFromDocumentPromptBuilder.php`
- Prompt gồm:
  - `DOC_TITLE`
  - `DIFFICULTY`
  - `QUESTION_COUNT`
  - nội dung tài liệu (clip tối đa ~9000 ký tự)
- Schema đầu ra yêu cầu JSON có `title`, `questions[]`, mỗi câu có 4 đáp án và 1 đáp án đúng `A|B|C|D`.

### 7. Validation AI output

- File: `app/Services/QuizGenerationService.php`
- Sau khi AI trả về:
  - decode JSON
  - normalize format câu hỏi/đáp án
  - loại đáp án trùng, câu trùng
  - chỉ nhận câu có 4 đáp án hợp lệ + đáp án đúng hợp lệ
- Với flow gợi ý AI, cho phép số câu hợp lệ ít hơn số yêu cầu, nhưng phải có ít nhất 1 câu hợp lệ.

### 8. Ghi chú quan trọng về UX và lưu dữ liệu

- Màn preview dùng `questions_payload` (JSON) để gửi toàn bộ danh sách câu hỏi, tránh lỗi `max_input_vars`.
- Sau import, tác giả có thể:
  - thêm câu thủ công
  - xóa câu
  - gọi AI để gợi ý thêm
- Độ khó hiện không cho người dùng chọn trên UI; backend đang dùng mặc định nội bộ (`medium`) để tương thích schema DB hiện tại.

---

<!-- BEGIN: 03_ARCHITECTURE_OVERVIEW.md -->

# 03 - Architecture Overview

## Stack

- PHP MVC thuong (khong framework ngoai).
- PDO MySQL.
- Session-based auth + CSRF token.
- AI provider abstraction (OpenAI/Mock).

## Entry points

- `index.php` (root) va `public/index.php` deu bootstrap container + router + request dispatch.
- `bootstrap.php` dang ky service trong container, load env, config, repository, services.

## Layering

- Core: `Request`, `Response`, `Router`, `Session`, `View`, `Controller` base.
- Controllers: xu ly HTTP + auth/check + render/redirect.
- Services: auth, parser/AI generation, text extraction, scoring.
- Repositories: truy cap DB SQL duy nhat qua `PlatformRepositoryInterface`.
- Views: PHP templates theo module.
- Public assets: CSS + JS behavior cho UX.

## Role model

- Roles: `admin`, `user` (table `users.role`).
- Enforce boi `requireAuth([roles])` + owner checks o 1 so controller.

## Important implementation details

- Parser import cau hoi va AI suggestion da tach flow ro rang.
- Draft preview luu session (`quiz_generation_draft`).
- Preview submit dung JSON payload de tranh gioi han `max_input_vars`.
- Layout chung co menu role-aware cho admin/user.

<!-- END: 03_ARCHITECTURE_OVERVIEW.md -->

---

<!-- BEGIN: 04_ROUTE_MAP.md -->

# 04 - Route Map And Access

| Method | Path | Controller::Action | Access Rule |
|---|---|---|---|
| GET | / | LandingController::index | guest only (redirect if logged in) |
| GET | /login | AuthController::showLogin | guest only |
| POST | /login | AuthController::login | guest/form |
| GET | /register | AuthController::showRegister | guest only |
| POST | /register | AuthController::register | guest/form |
| POST | /logout | AuthController::logout | auth + csrf |
| GET | /workspace | WorkspaceController::index | auth |
| GET | /dashboard | WorkspaceController::index | auth |
| GET | /documents | DocumentController::index | auth (owner scoped for user role) |
| GET | /documents/create | DocumentController::create | auth |
| POST | /documents | DocumentController::store | auth + csrf + upload validation |
| GET | /documents/{id} | DocumentController::show | auth (owner scoped for user role) |
| GET | /quizzes | QuizController::index | auth |
| GET | /quizzes/create | QuizController::create | auth |
| POST | /quizzes | QuizController::store | auth + csrf + owner check + parser import |
| GET | /quizzes/preview | QuizController::preview | auth + requires draft in session |
| POST | /quizzes/preview/save | QuizController::savePreview | auth + csrf |
| POST | /quizzes/preview/suggest-ai | QuizController::suggestAiPreview | auth + csrf (AI suggestion only) |
| POST | /quizzes/preview/discard | QuizController::discardPreview | auth + csrf |
| GET | /quizzes/{id} | QuizController::show | auth (admin or quiz creator) |
| GET | /quizzes/{id}/take | QuizController::take | auth |
| POST | /quizzes/{id}/submit | QuizController::submit | auth + csrf |
| GET | /quizzes/{id}/export | QuizController::export | admin only |
| GET | /leaderboard | LeaderboardController::index | auth |
| GET | /questions | QuestionController::index | admin only |
| GET | /questions/create | QuestionController::create | admin only |
| POST | /questions | QuestionController::store | admin only + csrf |
| GET | /questions/{id}/edit | QuestionController::edit | admin only |
| POST | /questions/{id}/update | QuestionController::update | admin only + csrf |
| POST | /questions/{id}/correct | QuestionController::updateCorrectAnswer | admin only + csrf |
| POST | /questions/{id}/delete | QuestionController::delete | admin only + csrf |
| GET | /submissions | SubmissionController::index | auth (user sees own submissions) |
| GET | /submissions/{id} | SubmissionController::show | auth (user sees own submissions) |
| GET | /users | UserController::index | admin only |

Ghi chu:
- `/dashboard` dang alias ve `WorkspaceController::index`.
- Kiem soat quyen thuc thi trong `App\Core\Controller::requireAuth()` va cac check theo owner/creator trong tung controller.

<!-- END: 04_ROUTE_MAP.md -->

---

<!-- BEGIN: 05_CONTROLLER_MAP.md -->

# 05 - Controller Map

## app/Controllers/AuthController.php

- Type: final class AuthController extends Controller

| Visibility | Method |
|---|---|
| public | showLogin |
| public | login |
| public | showRegister |
| public | register |
| public | logout |

## app/Controllers/DocumentController.php

- Type: final class DocumentController extends Controller

| Visibility | Method |
|---|---|
| public | index |
| public | create |
| public | store |
| public | show |

## app/Controllers/LandingController.php

- Type: final class LandingController extends Controller

| Visibility | Method |
|---|---|
| public | index |

## app/Controllers/LeaderboardController.php

- Type: final class LeaderboardController extends Controller

| Visibility | Method |
|---|---|
| public | index |

## app/Controllers/QuestionController.php

- Type: final class QuestionController extends Controller

| Visibility | Method |
|---|---|
| public | index |
| public | create |
| public | store |
| public | edit |
| public | update |
| public | updateCorrectAnswer |
| public | delete |

## app/Controllers/QuizController.php

- Type: final class QuizController extends Controller

| Visibility | Method |
|---|---|
| public | index |
| public | create |
| public | store |
| public | preview |
| public | suggestAiPreview |
| public | savePreview |
| public | discardPreview |
| public | show |
| public | take |
| public | submit |
| public | export |
| private | normalizePreviewQuestions |
| private | resolveSubmittedQuestions |
| private | normalizeSuggestionIndexes |
| private | filterSuggestionIndexes |
| private | pickSuggestedQuestions |
| private | mergePreviewQuestions |
| private | mergeSuggestionQuestions |
| private | normalizePreviewQuestionForMerge |
| private | fingerprintPreviewQuestion |
| private | saveDraft |
| private | getDraft |
| private | clearDraft |
| private | resolveDocumentContentForGeneration |

## app/Controllers/SubmissionController.php

- Type: final class SubmissionController extends Controller

| Visibility | Method |
|---|---|
| public | index |
| public | show |

## app/Controllers/UserController.php

- Type: final class UserController extends Controller

| Visibility | Method |
|---|---|
| public | index |

## app/Controllers/WorkspaceController.php

- Type: final class WorkspaceController extends Controller

| Visibility | Method |
|---|---|
| public | index |

<!-- END: 05_CONTROLLER_MAP.md -->

---

<!-- BEGIN: 06_SERVICE_MAP.md -->

# 06 - Service Map

## app/Services/AI/AIProviderInterface.php

- Type: interface AIProviderInterface

| Visibility | Method |
|---|---|
| public | generate |

## app/Services/AI/AIResult.php

- Type: final class AIResult

| Visibility | Method |
|---|---|
| public | __construct |

## app/Services/AI/MockAIProvider.php

- Type: final class MockAIProvider implements AIProviderInterface

| Visibility | Method |
|---|---|
| public | generate |
| private | extractField |

## app/Services/AI/OpenAIProvider.php

- Type: final class OpenAIProvider implements AIProviderInterface

| Visibility | Method |
|---|---|
| public | __construct |
| public | generate |

## app/Services/AuthService.php

- Type: final class AuthService

| Visibility | Method |
|---|---|
| public | __construct |
| public | register |
| public | login |
| public | logout |
| public | user |
| public | check |

## app/Services/DocumentTextExtractorService.php

- Type: final class DocumentTextExtractorService

| Visibility | Method |
|---|---|
| public | extract |
| private | extractTxt |
| private | extractDocx |
| private | extractPdf |
| private | extractDocxWithParagraphs |
| private | extractDocxByTagStripping |
| private | normalizeExtractedText |

## app/Services/Prompt/QuizFromDocumentPromptBuilder.php

- Type: final class QuizFromDocumentPromptBuilder

| Visibility | Method |
|---|---|
| public | build |
| private | normalizeDifficulty |

## app/Services/QuizGenerationService.php

- Type: final class QuizGenerationService

| Visibility | Method |
|---|---|
| public | __construct |
| public | generateFromDocument |
| public | extractQuestionsFromDocument |
| public | generateAiSuggestions |
| private | resolveAiQuestionCount |
| private | generateQuestionsWithAi |
| private | decodeJson |
| private | normalizeQuestions |
| private | extractQuestionText |
| private | normalizeOptions |
| private | hasDuplicateOptions |
| private | fingerprintQuestion |
| private | prepareDocumentContent |
| private | buildContextWindow |
| private | normalizeDifficulty |
| private | extractCorrectFromOptionsMarker |
| private | stripCorrectMarker |
| private | isListArray |
| private | parseQuestionsFromDocument |
| private | parseQuestionsByExplicitMarkers |
| private | isExplicitQuestionMarkerLine |
| private | extractExplicitQuestionNumber |
| private | buildQuestionFromExplicitBlock |
| private | ensureDistinctOptions |
| private | scoreParsedQuestionQuality |
| private | mergeQuestionCollections |
| private | normalizeRawDocumentContent |
| private | splitLinesWithInlineOptions |
| private | explodeInlineOptions |
| private | parseOptionLine |
| private | parseCorrectAnswerLine |
| private | cleanupQuestionLine |
| private | newParseState |
| private | pushParsedQuestion |
| private | finalizeParsedQuestion |
| private | hasAllOptions |
| private | lastOptionKey |
| private | normalizeOptionValue |
| private | fillMissingOptionPlaceholders |
| private | isQuestionStartLine |
| private | extractOptionPayload |
| private | normalizeAnswerTokenToLetter |
| private | isLikelyQuestionContent |
| private | shouldUseOptionPlaceholders |
| private | isSectionHeadingLine |

## app/Services/SubmissionEvaluationService.php

- Type: final class SubmissionEvaluationService

| Visibility | Method |
|---|---|
| public | evaluate |

<!-- END: 06_SERVICE_MAP.md -->

---

<!-- BEGIN: 07_CORE_SUPPORT_EXCEPTION_MAP.md -->

# 07 - Core Support Exception Map

## app/Core/Autoload.php

- Type: final class Autoload

- Methods: (none)

## app/Core/Controller.php

| Visibility | Method |
|---|---|
| public | __construct |
| protected | render |
| protected | redirect |
| protected | flash |
| protected | verifyCsrfToken |
| protected | currentUser |
| protected | requireAuth |
| protected | redirectIfAuthenticated |
| protected | roleHomePath |

## app/Core/Database.php

- Type: final class Database

| Visibility | Method |
|---|---|
| public | __construct |
| public | getConnection |

## app/Core/Env.php

- Type: final class Env

- Methods: (none)

## app/Core/Request.php

- Type: final class Request

| Visibility | Method |
|---|---|
| public | __construct |
| public | method |
| public | uri |
| public | input |
| public | all |
| public | route |
| private | basePath |

## app/Core/Response.php

- Type: final class Response

| Visibility | Method |
|---|---|
| public | html |
| public | json |
| public | redirect |
| public | download |
| private | setSecurityHeaders |
| private | basePath |

## app/Core/Router.php

- Type: final class Router

| Visibility | Method |
|---|---|
| public | __construct |
| public | get |
| public | post |
| private | register |
| public | dispatch |

## app/Core/Session.php

- Type: final class Session

| Visibility | Method |
|---|---|
| public | __construct |
| public | start |
| public | flash |
| public | getFlash |
| public | put |
| public | get |
| public | remove |
| public | invalidate |
| public | csrfToken |
| public | isValidCsrf |

## app/Core/Validator.php

- Type: final class Validator

| Visibility | Method |
|---|---|
| public | required |
| public | betweenInt |
| public | inArray |
| public | errors |
| public | fails |

## app/Core/View.php

- Type: final class View

| Visibility | Method |
|---|---|
| public | render |
| private | prefixRootPaths |
| private | basePath |

## app/Support/Container.php

- Type: final class Container

| Visibility | Method |
|---|---|
| public | set |
| public | get |

## app/Support/Logger.php

- Type: final class Logger

| Visibility | Method |
|---|---|
| public | __construct |
| public | info |
| public | error |
| private | write |

## app/Exceptions/AIProviderException.php

- Type: final class AIProviderException extends RuntimeException

- Methods: (none)

## app/Exceptions/ValidationException.php

- Type: final class ValidationException extends RuntimeException

| Visibility | Method |
|---|---|
| public | __construct |
| public | errors |

<!-- END: 07_CORE_SUPPORT_EXCEPTION_MAP.md -->

---

<!-- BEGIN: 08_REPOSITORY_MAP.md -->

# 08 - Repository Map

## app/Repositories/MysqlPlatformRepository.php

- Type: final class MysqlPlatformRepository implements PlatformRepositoryInterface

| Visibility | Method |
|---|---|
| public | __construct |
| public | createUser |
| public | findUserByEmail |
| public | findUserById |
| public | listUsers |
| public | createDocument |
| public | listDocuments |
| public | findDocumentById |
| public | createQuiz |
| public | listQuizzes |
| public | findQuizById |
| public | findQuestionsByQuizId |
| public | listQuestions |
| public | findQuestionById |
| public | createQuestion |
| public | updateQuestion |
| public | updateQuestionCorrectAnswer |
| public | deleteQuestion |
| public | createSubmission |
| public | listSubmissions |
| public | findSubmissionById |
| public | findSubmissionAnswers |

## app/Repositories/PlatformRepositoryInterface.php

- Type: interface PlatformRepositoryInterface

| Visibility | Method |
|---|---|
| public | createUser |
| public | findUserByEmail |
| public | findUserById |
| public | listUsers |
| public | createDocument |
| public | listDocuments |
| public | findDocumentById |
| public | createQuiz |
| public | listQuizzes |
| public | findQuizById |
| public | findQuestionsByQuizId |
| public | listQuestions |
| public | findQuestionById |
| public | createQuestion |
| public | updateQuestion |
| public | updateQuestionCorrectAnswer |
| public | deleteQuestion |
| public | createSubmission |
| public | listSubmissions |
| public | findSubmissionById |
| public | findSubmissionAnswers |

<!-- END: 08_REPOSITORY_MAP.md -->

---

<!-- BEGIN: 09_SCHEMA_AND_CONFIG.md -->

# 09 - Schema And Config

## Database Schema (`database/schema.sql`)

```sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    stored_file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(191) NOT NULL,
    extracted_content LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_documents_user (user_id),
    CONSTRAINT fk_documents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_quizzes_document (document_id),
    KEY idx_quizzes_created_by (created_by),
    CONSTRAINT fk_quizzes_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    question_content TEXT NOT NULL,
    answer_a TEXT NOT NULL,
    answer_b TEXT NOT NULL,
    answer_c TEXT NOT NULL,
    answer_d TEXT NOT NULL,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_questions_quiz_position (quiz_id, position),
    CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    total_correct INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_submissions_quiz (quiz_id),
    KEY idx_submissions_user (user_id),
    CONSTRAINT fk_submissions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submission_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_answer ENUM('A', 'B', 'C', 'D', '') NOT NULL DEFAULT '',
    is_correct TINYINT(1) NOT NULL,
    KEY idx_submission_answers_submission (submission_id),
    CONSTRAINT fk_submission_answers_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email, password_hash, role, created_at)
VALUES (
    1,
    'Administrator',
    'admin@prx.local',
    '$2y$10$BIBTnSiGMFEFwGiwYAABh.0umS/ZH1y5VVe1FKxVUSwHLMs0MXxWK',
    'admin',
    NOW()
)
ON DUPLICATE KEY UPDATE email = VALUES(email);

SET FOREIGN_KEY_CHECKS = 1;
```

## App Config (`config/app.php`)

```php
<?php

declare(strict_types=1);

use App\Core\Env;

$provider = Env::get('AI_PROVIDER');
if ($provider === null || $provider === '') {
    $provider = Env::get('OPENAI_API_KEY', '') !== '' ? 'openai' : 'mock';
}

return [
    'app_name' => Env::get('APP_NAME', 'PRX AI Quiz Platform'),
    'base_url' => Env::get('APP_URL', 'http://localhost:8080'),
    'ai' => [
        'provider' => $provider,
        'openai' => [
            'model' => Env::get('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) Env::get('OPENAI_TIMEOUT', '60'),
        ],
    ],
];
```

## DB Config (`config/database.php`)

```php
<?php

declare(strict_types=1);

use App\Core\Env;

$host = (string) Env::get('DB_HOST', '127.0.0.1');
$port = (string) Env::get('DB_PORT', '3306');
$database = (string) Env::get('DB_DATABASE', 'prx');
$username = (string) Env::get('DB_USERNAME', 'root');
$password = (string) Env::get('DB_PASSWORD', '');
$charset = (string) Env::get('DB_CHARSET', 'utf8mb4');

return [
    'driver' => 'mysql',
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'charset' => $charset,
    'username' => $username,
    'password' => $password,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $database,
        $charset
    ),
    'options' => [
        \PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

<!-- END: 09_SCHEMA_AND_CONFIG.md -->

---

<!-- BEGIN: 10_VIEW_MAP.md -->

# 10 - View Map

| View File | Used By Controller(s) |
|---|---|
| app/Views/auth/login.php | AuthController |
| app/Views/auth/register.php | AuthController |
| app/Views/documents/create.php | DocumentController |
| app/Views/documents/index.php | DocumentController |
| app/Views/documents/show.php | DocumentController |
| app/Views/landing/index.php | LandingController |
| app/Views/layout/main.php | (layout/partial or indirect) |
| app/Views/leaderboard/index.php | LeaderboardController |
| app/Views/questions/create.php | QuestionController |
| app/Views/questions/edit.php | QuestionController |
| app/Views/questions/index.php | QuestionController |
| app/Views/quizzes/create.php | QuizController |
| app/Views/quizzes/index.php | QuizController |
| app/Views/quizzes/preview.php | QuizController |
| app/Views/quizzes/show.php | QuizController |
| app/Views/quizzes/take.php | QuizController |
| app/Views/submissions/index.php | SubmissionController |
| app/Views/submissions/show.php | SubmissionController |
| app/Views/users/index.php | UserController |
| app/Views/workspace/index.php | WorkspaceController |

<!-- END: 10_VIEW_MAP.md -->

---

<!-- BEGIN: 11_PUBLIC_ASSETS_AND_LAYOUT.md -->

# 11 - Public Assets And Layout

## JS Modules (`public/assets/js/app.js`)

Khoi tao module front-end hien co:

- setupFormSubmitLock
- setupPasswordToggles
- setupFileInputFeedback
- setupTableExpandRows
- setupExamExperience
- setupPreviewQuestionPayload
- setupLandingJoinQuiz

## CSS

- Main stylesheet: `public/assets/css/app.css`
- Chua style cho auth, workspace, landing, create/preview quiz, exam, leaderboard va component chung.

## Main Layout (`app/Views/layout/main.php`)

- Co sidebar menu chung cho user da dang nhap.
- Neu role = admin, menu bo sung: `/users`, `/questions`.
- Header/topbar + flash message + csrf hidden input cho logout form.

<!-- END: 11_PUBLIC_ASSETS_AND_LAYOUT.md -->

---

<!-- BEGIN: 12_RUNTIME_STORAGE_SCRIPTS.md -->

# 12 - Runtime Storage Scripts Backups

## Runtime/State directories

- `storage/database.sqlite` (hien co trong repo, co the la artifact local).
- `storage/logs/` (app log + snapshot html).
- `storage/sessions/` (PHP session file, save_path custom).
- `storage/uploads/` (file nguon upload: txt/docx/pdf).

## Script folder (`scripts/`)

- `init_db.php`: tao DB + chay schema.sql.
- `verify_question_parser.php`: test parser tren folder du lieu test.
- `init_local_xampp.ps1`: khoi tao env local + init DB.
- `start_local_xampp.ps1`: start built-in php server (`-t public`).

## Backup/Test data in repo root

- `backup/` va `project_backups/`: cac file zip backup local.
- `du lieu test/`: bo file test parser nhan dien cau hoi.

## Important notes

- Repo dang gom ca source code + artifact runtime + backup zip.
- Neu can clean source-only branch, nen tach artifact runtime ra khoi VCS.

<!-- END: 12_RUNTIME_STORAGE_SCRIPTS.md -->

---

<!-- BEGIN: 13_WORKFLOWS.md -->

# 13 - End To End Workflows

## Auth flow

1. Guest vao `/login` hoac `/register`.
2. Controller validate + AuthService thao tac session key `auth_user_id`.
3. Sau login/register redirect ve `/workspace`.

## Upload document flow

1. POST `/documents` (csrf + file validate + ext + size).
2. Luu file vao `storage/uploads`.
3. `DocumentTextExtractorService` trich text (txt/docx/pdf).
4. Repository tao row `documents`.
5. Redirect sang `/quizzes/create`.

## Import parser -> preview draft flow

1. POST `/quizzes` voi `title + document_id`.
2. `QuizGenerationService::extractQuestionsFromDocument()` parse bo cau hoi tu noi dung tai lieu (khong AI random).
3. Draft luu trong session key `quiz_generation_draft`.
4. GET `/quizzes/preview` de sua danh sach cau hoi.

## AI suggestion flow (explicit only)

1. Tu preview, POST `/quizzes/preview/suggest-ai`.
2. AI tao `suggested_questions` de tac gia tick chon them vao de.
3. Khong thay the danh sach import chinh; chi bo sung neu tac gia xac nhan.

## Save quiz flow

1. POST `/quizzes/preview/save`.
2. JS serializes question list vao `questions_payload` JSON (de tranh max_input_vars).
3. Controller validate cau hoi + merge voi suggestion duoc tick.
4. Repository transaction create `quizzes` + `questions`.

## Take + submit flow

1. GET `/quizzes/{id}/take` hien thi de + progress + timer.
2. POST `/quizzes/{id}/submit`.
3. `SubmissionEvaluationService` tinh score/total_correct.
4. Repository transaction create `submissions` + `submission_answers`.
5. Redirect `/submissions/{id}`.

## Leaderboard flow

1. GET `/leaderboard` (optional `quiz_id`).
2. Sap xep: score desc -> total_correct desc -> created_at asc -> id asc.

<!-- END: 13_WORKFLOWS.md -->

---

<!-- BEGIN: 01_REPO_TREE.md -->

# 01 - Repo Tree

Nguon du lieu: quet toan bo thu muc goc hien tai.

```text
PRX
|-- app
|   |-- Controllers
|   |   |-- AuthController.php
|   |   |-- DocumentController.php
|   |   |-- LandingController.php
|   |   |-- LeaderboardController.php
|   |   |-- QuestionController.php
|   |   |-- QuizController.php
|   |   |-- SubmissionController.php
|   |   |-- UserController.php
|   |   +-- WorkspaceController.php
|   |-- Core
|   |   |-- Autoload.php
|   |   |-- Controller.php
|   |   |-- Database.php
|   |   |-- Env.php
|   |   |-- Request.php
|   |   |-- Response.php
|   |   |-- Router.php
|   |   |-- Session.php
|   |   |-- Validator.php
|   |   +-- View.php
|   |-- Exceptions
|   |   |-- AIProviderException.php
|   |   +-- ValidationException.php
|   |-- Repositories
|   |   |-- MysqlPlatformRepository.php
|   |   +-- PlatformRepositoryInterface.php
|   |-- Services
|   |   |-- AI
|   |   |   |-- AIProviderInterface.php
|   |   |   |-- AIResult.php
|   |   |   |-- MockAIProvider.php
|   |   |   +-- OpenAIProvider.php
|   |   |-- Prompt
|   |   |   +-- QuizFromDocumentPromptBuilder.php
|   |   |-- AuthService.php
|   |   |-- DocumentTextExtractorService.php
|   |   |-- QuizGenerationService.php
|   |   +-- SubmissionEvaluationService.php
|   |-- Support
|   |   |-- Container.php
|   |   +-- Logger.php
|   +-- Views
|       |-- auth
|       |   |-- login.php
|       |   +-- register.php
|       |-- dashboard
|       |-- documents
|       |   |-- create.php
|       |   |-- index.php
|       |   +-- show.php
|       |-- landing
|       |   +-- index.php
|       |-- layout
|       |   +-- main.php
|       |-- leaderboard
|       |   +-- index.php
|       |-- questions
|       |   |-- create.php
|       |   |-- edit.php
|       |   +-- index.php
|       |-- quizzes
|       |   |-- create.php
|       |   |-- index.php
|       |   |-- preview.php
|       |   |-- show.php
|       |   +-- take.php
|       |-- submissions
|       |   |-- index.php
|       |   +-- show.php
|       |-- users
|       |   +-- index.php
|       +-- workspace
|           +-- index.php
|-- backup
|   +-- PRX_20260410_202559.zip
|-- config
|   |-- app.php
|   +-- database.php
|-- database
|   +-- schema.sql
|-- docs
|   |-- repo-structure
|   |   |-- 00_INDEX.md
|   |   |-- 01_REPO_TREE.md
|   |   |-- 02_FILE_MANIFEST.md
|   |   |-- 03_ARCHITECTURE_OVERVIEW.md
|   |   |-- 04_ROUTE_MAP.md
|   |   |-- 05_CONTROLLER_MAP.md
|   |   |-- 06_SERVICE_MAP.md
|   |   |-- 07_CORE_SUPPORT_EXCEPTION_MAP.md
|   |   |-- 08_REPOSITORY_MAP.md
|   |   |-- 09_SCHEMA_AND_CONFIG.md
|   |   |-- 10_VIEW_MAP.md
|   |   |-- 11_PUBLIC_ASSETS_AND_LAYOUT.md
|   |   |-- 12_RUNTIME_STORAGE_SCRIPTS.md
|   |   +-- 13_WORKFLOWS.md
|   |-- ai_module.md
|   |-- ooad.md
|   |-- uml_class.puml
|   |-- uml_component.puml
|   |-- uml_sequence_generate.puml
|   |-- uml_use_case.puml
|   +-- xampp-vhost.conf
|-- dữ liệu test
|   |-- ~$TTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-LỜI GIẢI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 2-Ứng dụng thực tiễn-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 2-Ứng dụng thực tiễn-LỜI GIẢI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 3-Tính đơn điệu và cực trị chứa tham số-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 3-Tính đơn điệu và cực trị chứa tham số-LỜI GIẢI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 4-Tính đơn điệu và cực trị của hàm hợp-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 4-Tính đơn điệu và cực trị của hàm hợp-LỜI GIẢI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 5-Tính đơn điệu hàm hợp liên quan f_(x)-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 5-Tính đơn điệu hàm hợp liên quan f_(x)-LỜI GIẢI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 6-Cực trị hàm hợp liên quan f_(x)-ĐỀ BÀI.docx
|   |-- KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 6-Cực trị hàm hợp liên quan f_(x)-LỜI GIẢI.docx
|   |-- ÔN GK2. LỚP 12.docx
|   +-- tổng hợp ester - lần 1_1.pdf
|-- project_backups
|   +-- PRX_backup_20260412_221749.zip
|-- public
|   |-- assets
|   |   |-- css
|   |   |   +-- app.css
|   |   +-- js
|   |       +-- app.js
|   |-- .htaccess
|   +-- index.php
|-- scripts
|   |-- generate_repo_structure_docs.ps1
|   |-- init_db.php
|   |-- init_local_xampp.ps1
|   |-- start_local_xampp.ps1
|   +-- verify_question_parser.php
|-- storage
|   |-- logs
|   |   |-- app.log
|   |   +-- landing_snapshot.html
|   |-- sessions
|   |   |-- sess_gmoc4veqqafs3q8rphn44krn46
|   |   +-- sess_tbi6q27p5k30d479fir1n2p4hm
|   |-- uploads
|   |   |-- 20260410092207_b597fa0e3542.docx
|   |   |-- 20260410092737_a040dfc1f041.docx
|   |   |-- 20260410100657_73432cab25ce.docx
|   |   |-- 20260410100756_518488221429.docx
|   |   |-- 20260410102820_7d8fcca6ea02.docx
|   |   |-- 20260410140715_6ca8db14de61.docx
|   |   |-- 20260410140928_cac80d2c1430.docx
|   |   |-- 20260410144915_9c8d2ace6183.docx
|   |   |-- 20260410145441_19160a6f2c6d.docx
|   |   |-- 20260410150209_e6f0b74dc243.docx
|   |   |-- 20260412141903_1cb90c5badbd.docx
|   |   |-- 20260412142842_62e0b181e335.docx
|   |   |-- 20260412143906_fe1702384de0.docx
|   |   |-- 20260412152953_57eb21f3f2af.docx
|   |   |-- 20260412155052_5483103815a9.docx
|   |   |-- 20260412155625_2d4e3b60e85c.docx
|   |   |-- 20260412160405_862747daee52.docx
|   |   |-- 20260412163843_45a9f467269b.docx
|   |   |-- 20260412164151_743eecf162df.txt
|   |   |-- 20260412164423_2b8073e1276c.txt
|   |   |-- 20260412165026_ae430645019b.txt
|   |   +-- 20260412171607_78b5abfc5564.docx
|   +-- database.sqlite
|-- .env
|-- .env.example
|-- .gitignore
|-- .htaccess
|-- bootstrap.php
|-- index.php
|-- README.md
+-- routes.php
```

<!-- END: 01_REPO_TREE.md -->

---

<!-- BEGIN: 02_FILE_MANIFEST.md -->

# 02 - File Manifest

Tong so file: 139

| Path | Ext | Size (bytes) | Lines | Last Modified |
|---|---:|---:|---:|---|
| .env | .env | 305 | 14 | 2026-04-10 14:17:38 |
| .env.example | .example | 305 | 14 | 2026-04-10 14:17:38 |
| .gitignore | .gitignore | 85 |  | 2026-04-10 01:00:50 |
| .htaccess | .htaccess | 567 | 20 | 2026-04-12 21:37:04 |
| app/Controllers/AuthController.php | .php | 3565 | 92 | 2026-04-12 21:06:29 |
| app/Controllers/DocumentController.php | .php | 5792 | 139 | 2026-04-12 18:27:26 |
| app/Controllers/LandingController.php | .php | 403 | 16 | 2026-04-10 16:42:51 |
| app/Controllers/LeaderboardController.php | .php | 1796 | 47 | 2026-04-10 17:37:46 |
| app/Controllers/QuestionController.php | .php | 8426 | 194 | 2026-04-10 13:18:27 |
| app/Controllers/QuizController.php | .php | 29163 | 695 | 2026-04-12 21:57:21 |
| app/Controllers/SubmissionController.php | .php | 1704 | 47 | 2026-04-12 18:28:26 |
| app/Controllers/UserController.php | .php | 551 | 20 | 2026-04-10 00:50:27 |
| app/Controllers/WorkspaceController.php | .php | 1333 | 33 | 2026-04-12 18:28:35 |
| app/Core/Autoload.php | .php | 514 | 18 | 2026-04-10 00:14:22 |
| app/Core/Controller.php | .php | 2697 | 75 | 2026-04-12 19:07:42 |
| app/Core/Database.php | .php | 1305 | 37 | 2026-04-10 14:06:24 |
| app/Core/Env.php | .php | 1053 | 35 | 2026-04-10 00:14:22 |
| app/Core/Request.php | .php | 1464 | 49 | 2026-04-10 14:15:53 |
| app/Core/Response.php | .php | 2011 | 57 | 2026-04-10 14:16:09 |
| app/Core/Router.php | .php | 2018 | 64 | 2026-04-10 01:00:34 |
| app/Core/Session.php | .php | 1966 | 68 | 2026-04-10 00:40:03 |
| app/Core/Validator.php | .php | 1143 | 39 | 2026-04-10 00:14:22 |
| app/Core/View.php | .php | 2085 | 60 | 2026-04-10 14:16:34 |
| app/Exceptions/AIProviderException.php | .php | 144 | 7 | 2026-04-10 00:14:22 |
| app/Exceptions/ValidationException.php | .php | 441 | 17 | 2026-04-10 00:14:22 |
| app/Repositories/MysqlPlatformRepository.php | .php | 17564 | 426 | 2026-04-10 17:36:47 |
| app/Repositories/PlatformRepositoryInterface.php | .php | 2978 | 82 | 2026-04-10 13:17:36 |
| app/Services/AI/AIProviderInterface.php | .php | 151 | 7 | 2026-04-10 00:14:22 |
| app/Services/AI/AIResult.php | .php | 230 | 12 | 2026-04-10 00:14:22 |
| app/Services/AI/MockAIProvider.php | .php | 1945 | 48 | 2026-04-12 18:33:32 |
| app/Services/AI/OpenAIProvider.php | .php | 2879 | 71 | 2026-04-12 18:33:06 |
| app/Services/AuthService.php | .php | 2461 | 77 | 2026-04-12 19:06:08 |
| app/Services/DocumentTextExtractorService.php | .php | 5028 | 126 | 2026-04-10 14:51:46 |
| app/Services/Prompt/QuizFromDocumentPromptBuilder.php | .php | 1755 | 50 | 2026-04-12 18:32:20 |
| app/Services/QuizGenerationService.php | .php | 40767 | 1015 | 2026-04-12 21:47:13 |
| app/Services/SubmissionEvaluationService.php | .php | 1447 | 38 | 2026-04-10 00:45:23 |
| app/Support/Container.php | .php | 815 | 28 | 2026-04-10 00:14:22 |
| app/Support/Logger.php | .php | 958 | 27 | 2026-04-10 00:14:22 |
| app/Views/auth/login.php | .php | 8079 | 142 | 2026-04-12 18:55:37 |
| app/Views/auth/register.php | .php | 9288 | 163 | 2026-04-12 18:58:01 |
| app/Views/documents/create.php | .php | 2744 | 52 | 2026-04-12 18:38:13 |
| app/Views/documents/index.php | .php | 1701 | 39 | 2026-04-12 18:38:37 |
| app/Views/documents/show.php | .php | 990 | 17 | 2026-04-12 18:39:01 |
| app/Views/landing/index.php | .php | 21222 | 334 | 2026-04-12 20:13:21 |
| app/Views/layout/main.php | .php | 11289 | 284 | 2026-04-12 18:34:59 |
| app/Views/leaderboard/index.php | .php | 3029 | 65 | 2026-04-12 18:41:27 |
| app/Views/questions/create.php | .php | 1877 | 38 | 2026-04-12 18:43:36 |
| app/Views/questions/edit.php | .php | 2230 | 29 | 2026-04-12 18:44:17 |
| app/Views/questions/index.php | .php | 7768 | 128 | 2026-04-12 18:43:03 |
| app/Views/quizzes/create.php | .php | 5187 | 99 | 2026-04-12 21:57:37 |
| app/Views/quizzes/index.php | .php | 11989 | 286 | 2026-04-12 22:12:24 |
| app/Views/quizzes/preview.php | .php | 12179 | 226 | 2026-04-12 21:58:18 |
| app/Views/quizzes/show.php | .php | 4268 | 84 | 2026-04-12 21:58:38 |
| app/Views/quizzes/take.php | .php | 4246 | 74 | 2026-04-12 22:04:30 |
| app/Views/submissions/index.php | .php | 1907 | 42 | 2026-04-12 18:39:39 |
| app/Views/submissions/show.php | .php | 4250 | 86 | 2026-04-12 18:40:36 |
| app/Views/users/index.php | .php | 1077 | 28 | 2026-04-12 19:04:40 |
| app/Views/workspace/index.php | .php | 7134 | 160 | 2026-04-12 22:12:24 |
| backup/PRX_20260410_202559.zip | .zip | 9520231 |  | 2026-04-10 20:26:02 |
| bootstrap.php | .php | 2648 | 55 | 2026-04-10 14:06:00 |
| config/app.php | .php | 569 | 18 | 2026-04-10 01:02:04 |
| config/database.php | .php | 775 | 28 | 2026-04-10 14:00:27 |
| database/schema.sql | .sql | 3908 | 82 | 2026-04-10 14:04:00 |
| docs/ai_module.md | .md | 2878 | 94 | 2026-04-10 13:09:01 |
| docs/ooad.md | .md | 860 | 30 | 2026-04-10 01:32:52 |
| docs/repo-structure/00_INDEX.md | .md | 909 | 19 | 2026-04-12 22:40:22 |
| docs/repo-structure/01_REPO_TREE.md | .md | 6994 | 181 | 2026-04-12 22:42:02 |
| docs/repo-structure/02_FILE_MANIFEST.md | .md | 11975 | 130 | 2026-04-12 22:40:22 |
| docs/repo-structure/03_ARCHITECTURE_OVERVIEW.md | .md | 1159 | 24 | 2026-04-12 22:40:22 |
| docs/repo-structure/04_ROUTE_MAP.md | .md | 2763 | 40 | 2026-04-12 22:40:22 |
| docs/repo-structure/05_CONTROLLER_MAP.md | .md | 2436 | 92 | 2026-04-12 22:40:22 |
| docs/repo-structure/06_SERVICE_MAP.md | .md | 3360 | 113 | 2026-04-12 22:40:22 |
| docs/repo-structure/07_CORE_SUPPORT_EXCEPTION_MAP.md | .md | 2594 | 121 | 2026-04-12 22:40:22 |
| docs/repo-structure/08_REPOSITORY_MAP.md | .md | 1567 | 54 | 2026-04-12 22:40:22 |
| docs/repo-structure/09_SCHEMA_AND_CONFIG.md | .md | 5431 | 138 | 2026-04-12 22:40:22 |
| docs/repo-structure/10_VIEW_MAP.md | .md | 1129 | 23 | 2026-04-12 22:40:22 |
| docs/repo-structure/11_PUBLIC_ASSETS_AND_LAYOUT.md | .md | 659 | 17 | 2026-04-12 22:40:22 |
| docs/repo-structure/12_RUNTIME_STORAGE_SCRIPTS.md | .md | 891 | 17 | 2026-04-12 22:40:22 |
| docs/repo-structure/13_WORKFLOWS.md | .md | 1682 | 34 | 2026-04-12 22:40:22 |
| docs/uml_class.puml | .puml | 1394 | 51 | 2026-04-10 14:06:09 |
| docs/uml_component.puml | .puml | 472 | 21 | 2026-04-10 01:33:57 |
| docs/uml_sequence_generate.puml | .puml | 960 | 32 | 2026-04-10 01:34:18 |
| docs/uml_use_case.puml | .puml | 557 | 27 | 2026-04-10 01:33:11 |
| docs/xampp-vhost.conf | .conf | 300 | 10 | 2026-04-10 14:12:09 |
| dữ liệu test/~$TTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-ĐỀ BÀI.docx | .docx | 162 |  | 2026-04-12 19:42:16 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-ĐỀ BÀI.docx | .docx | 1097834 |  | 2026-04-12 19:42:13 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 1-Tính đơn điệu và cực trị của hàm số-LỜI GIẢI.docx | .docx | 3095088 |  | 2024-05-11 08:32:10 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 2-Ứng dụng thực tiễn-ĐỀ BÀI.docx | .docx | 132253 |  | 2024-05-11 08:34:20 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 2-Ứng dụng thực tiễn-LỜI GIẢI.docx | .docx | 309038 |  | 2024-05-11 08:34:58 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 3-Tính đơn điệu và cực trị chứa tham số-ĐỀ BÀI.docx | .docx | 958012 |  | 2024-05-11 08:40:44 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 3-Tính đơn điệu và cực trị chứa tham số-LỜI GIẢI.docx | .docx | 2478678 |  | 2024-05-11 08:41:20 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 4-Tính đơn điệu và cực trị của hàm hợp-ĐỀ BÀI.docx | .docx | 544767 |  | 2024-05-11 08:41:50 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 4-Tính đơn điệu và cực trị của hàm hợp-LỜI GIẢI.docx | .docx | 1244067 |  | 2024-05-11 08:42:22 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 5-Tính đơn điệu hàm hợp liên quan f_(x)-ĐỀ BÀI.docx | .docx | 1461875 |  | 2024-05-11 08:43:08 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 5-Tính đơn điệu hàm hợp liên quan f_(x)-LỜI GIẢI.docx | .docx | 2929110 |  | 2024-05-11 08:43:58 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 6-Cực trị hàm hợp liên quan f_(x)-ĐỀ BÀI.docx | .docx | 1407332 |  | 2024-05-11 08:44:26 |
| dữ liệu test/KNTTVCS-Đại số 12-Chương 1-Bài 1-Tính đơn điệu và cực trị của hàm số-Chủ đề 6-Cực trị hàm hợp liên quan f_(x)-LỜI GIẢI.docx | .docx | 3748174 |  | 2024-05-11 08:45:02 |
| dữ liệu test/ÔN GK2. LỚP 12.docx | .docx | 897949 |  | 2026-04-05 07:08:46 |
| dữ liệu test/tổng hợp ester - lần 1_1.pdf | .pdf | 1094888 |  | 2026-04-05 06:55:00 |
| index.php | .php | 551 | 18 | 2026-04-10 14:16:59 |
| project_backups/PRX_backup_20260412_221749.zip | .zip | 43688723 |  | 2026-04-12 22:17:51 |
| public/.htaccess | .htaccess | 193 | 7 | 2026-04-10 14:12:00 |
| public/assets/css/app.css | .css | 43157 | 2037 | 2026-04-12 22:07:55 |
| public/assets/js/app.js | .js | 17002 | 333 | 2026-04-12 22:07:43 |
| public/index.php | .php | 554 | 18 | 2026-04-10 00:14:22 |
| README.md | .md | 3418 | 93 | 2026-04-12 21:26:32 |
| routes.php | .php | 2781 | 45 | 2026-04-12 20:40:50 |
| scripts/generate_repo_structure_docs.ps1 | .ps1 | 19111 | 427 | 2026-04-12 22:41:57 |
| scripts/init_db.php | .php | 1676 | 39 | 2026-04-10 14:01:16 |
| scripts/init_local_xampp.ps1 | .ps1 | 418 | 14 | 2026-04-10 13:42:18 |
| scripts/start_local_xampp.ps1 | .ps1 | 327 | 12 | 2026-04-10 13:42:07 |
| scripts/verify_question_parser.php | .php | 5242 | 165 | 2026-04-12 21:27:14 |
| storage/database.sqlite | .sqlite | 65536 |  | 2026-04-10 01:39:45 |
| storage/logs/app.log | .log | 327 |  | 2026-04-12 21:42:15 |
| storage/logs/landing_snapshot.html | .html | 14987 |  | 2026-04-10 15:36:52 |
| storage/sessions/sess_gmoc4veqqafs3q8rphn44krn46 | (none) | 91 |  | 2026-04-12 22:19:01 |
| storage/sessions/sess_tbi6q27p5k30d479fir1n2p4hm | (none) | 108 |  | 2026-04-12 22:28:28 |
| storage/uploads/20260410092207_b597fa0e3542.docx | .docx | 1684832 |  | 2026-04-10 14:22:06 |
| storage/uploads/20260410092737_a040dfc1f041.docx | .docx | 544767 |  | 2026-04-10 14:27:37 |
| storage/uploads/20260410100657_73432cab25ce.docx | .docx | 1407332 |  | 2026-04-10 15:06:57 |
| storage/uploads/20260410100756_518488221429.docx | .docx | 1407332 |  | 2026-04-10 15:07:56 |
| storage/uploads/20260410102820_7d8fcca6ea02.docx | .docx | 2929110 |  | 2026-04-10 15:28:20 |
| storage/uploads/20260410140715_6ca8db14de61.docx | .docx | 36878 |  | 2026-04-10 19:07:15 |
| storage/uploads/20260410140928_cac80d2c1430.docx | .docx | 36878 |  | 2026-04-10 19:09:28 |
| storage/uploads/20260410144915_9c8d2ace6183.docx | .docx | 36878 |  | 2026-04-10 19:49:15 |
| storage/uploads/20260410145441_19160a6f2c6d.docx | .docx | 36878 |  | 2026-04-10 19:54:41 |
| storage/uploads/20260410150209_e6f0b74dc243.docx | .docx | 3095088 |  | 2026-04-10 20:02:09 |
| storage/uploads/20260412141903_1cb90c5badbd.docx | .docx | 2478678 |  | 2026-04-12 19:19:03 |
| storage/uploads/20260412142842_62e0b181e335.docx | .docx | 1684832 |  | 2026-04-12 19:28:42 |
| storage/uploads/20260412143906_fe1702384de0.docx | .docx | 1684832 |  | 2026-04-12 19:39:06 |
| storage/uploads/20260412152953_57eb21f3f2af.docx | .docx | 958012 |  | 2026-04-12 20:29:53 |
| storage/uploads/20260412155052_5483103815a9.docx | .docx | 1097834 |  | 2026-04-12 20:50:52 |
| storage/uploads/20260412155625_2d4e3b60e85c.docx | .docx | 27710 |  | 2026-04-12 20:56:25 |
| storage/uploads/20260412160405_862747daee52.docx | .docx | 27710 |  | 2026-04-12 21:04:05 |
| storage/uploads/20260412163843_45a9f467269b.docx | .docx | 27710 |  | 2026-04-12 21:38:43 |
| storage/uploads/20260412164151_743eecf162df.txt | .txt | 1468 | 64 | 2026-04-12 21:41:51 |
| storage/uploads/20260412164423_2b8073e1276c.txt | .txt | 7111 | 155 | 2026-04-12 21:44:23 |
| storage/uploads/20260412165026_ae430645019b.txt | .txt | 7111 | 155 | 2026-04-12 21:50:26 |
| storage/uploads/20260412171607_78b5abfc5564.docx | .docx | 36878 |  | 2026-04-12 22:16:07 |

<!-- END: 02_FILE_MANIFEST.md -->

---

Generated from: `docs/repo-structure/*`
Generated at: 2026-04-12 22:54:01

---

## 00 - CODE VERIFIED ROLE + MODULE + AI DESIGN SPEC (2026-04-12)

Section nay la ban dac ta bo sung, doi chieu truc tiep tu code hien tai de tra loi cau hoi:
- Vai tro va quyen da du chua?
- Kien truc module da mo ta du chua?
- AI module da mo ta dung ranh gioi "chi goi y" chua?

### 00.1 Role Matrix Va Quyen Truy Cap

| Role | Trang/Route chinh | Quyen | Gioi han |
|---|---|---|---|
| Guest (chua login) | `/`, `/login`, `/register` | Xem landing, dang nhap, dang ky | Khong vao duoc workspace/documents/quizzes/submissions |
| User (da login) | `/workspace`, `/documents`, `/quizzes`, `/submissions`, `/leaderboard` | Upload tai lieu, import cau hoi, xem truoc/sua, thi, nop bai, xem ket qua cua minh | Khong vao duoc admin-only route (`/users`, `/questions`, `/quizzes/{id}/export`) |
| Admin | Toan bo route cua user + `/users`, `/questions`, `/quizzes/{id}/export` | Quan tri nguoi dung, ngan hang cau hoi, export de, xem du lieu khong bi owner-scope | Van phai qua CSRF cho form POST nhu role khac |

Owner/Creator checks dang active trong code:
- `DocumentController::show`: user chi xem duoc document cua chinh minh; admin xem tat ca.
- `QuizController::store`: user chi duoc tao quiz tu document cua minh.
- `QuizController::show`: chi `admin` hoac `creator` moi vao trang quan ly quiz; user khac bi redirect sang `/quizzes/{id}/take`.
- `SubmissionController::show`: user chi xem duoc submission cua minh; admin xem tat ca.

Auth/guard diem chot:
- `App/Core/Controller.php::requireAuth(array $roles = [])`
- CSRF check cho form POST: `verifyCsrfToken()`.

### 00.2 Kien Truc Module (Theo Source Hien Tai)

Kien truc tong the:
`HTTP Request -> Router -> Controller -> Service -> Repository -> MySQL`
va
`Controller -> View (PHP template) + public/assets/js`.

Module 1 - Bootstrap + DI Container
- File: `bootstrap.php`, `public/index.php`, `routes.php`.
- Nhiem vu: load env/config, bind repository/service, chon AI provider, dispatch route.

Module 2 - Auth + Session + CSRF
- File: `AuthController`, `AuthService`, `Core/Session`.
- Nhiem vu: register/login/logout, luu user session, tao/verify csrf token.

Module 3 - Document Intake
- File: `DocumentController`, `DocumentTextExtractorService`.
- Nhiem vu: upload file (`pdf/docx/txt`), gioi han size, trich xuat text, luu vao `documents`.

Module 4 - Import Question Parser (Khong AI)
- File: `QuizController::store`, `QuizGenerationService::extractQuestionsFromDocument`.
- Nhiem vu: parse cau truc cau hoi truc tiep tu noi dung file import.

Module 5 - Preview/Edit Draft
- File: `QuizController::preview/savePreview/discardPreview`, `app/Views/quizzes/preview.php`, `public/assets/js/app.js`.
- Nhiem vu: hien danh sach cau hoi da nhan dien, cho phep sua, them cau thu cong, xoa cau.
- Draft duoc luu trong session key: `quiz_generation_draft`.

Module 6 - AI Suggestion (Optional, Explicit)
- File: `QuizController::suggestAiPreview`, `QuizGenerationService::generateAiSuggestions`, `Prompt/*`, `Services/AI/*`.
- Nhiem vu: chi goi y them cau hoi tren man preview theo nut bam cua tac gia.

Module 7 - Quiz Persistence + Take/Submit
- File: `QuizController::savePreview/take/submit`, `SubmissionEvaluationService`.
- Nhiem vu: luu quiz vao DB, cho nguoi dung lam bai, cham diem va luu submission.

Module 8 - Admin Operations
- File: `QuestionController`, `UserController`, `QuizController::export`.
- Nhiem vu: quan tri user, CRUD cau hoi, export de thi.

Module 9 - Ranking/Reporting
- File: `LeaderboardController`, `SubmissionController`.
- Nhiem vu: xep hang theo score/total_correct/time, xem chi tiet bai lam.

Luu y ve "admin dashboard":
- Hien tai chua co route dashboard rieng cho admin.
- Ca user/admin vao chung `/workspace`, nhung menu va quyen thao tac da role-aware.

### 00.3 AI Module Design (Implementation Thuc Te)

Nguyen tac bat buoc:
- `Import` = parser tu file.
- `AI` = goi y bo sung sau import, khong thay the import.

Trigger AI:
- Endpoint: `POST /quizzes/preview/suggest-ai`.
- Method: `QuizController::suggestAiPreview()`.
- Dieu kien: phai co draft preview trong session + csrf hop le.

Input vao AI flow:
- `title` (tieu de dang sua o preview).
- `questions_payload` (JSON danh sach cau hoi hien tai) hoac fallback `questions[...]`.
- `suggestion_count` (UI cho 1..10).

Xu ly trong controller:
- Normalize + validate cau hoi hien tai truoc khi goi AI.
- Goi `QuizGenerationService::generateAiSuggestions(documentTitle, documentContent, questionCount, difficulty)`.
- `difficulty` hien set mac dinh noi bo: `medium`.

Xu ly trong service:
- Clamp suggestion count: 1..10 (`resolveAiQuestionCount`).
- Build prompt qua `QuizFromDocumentPromptBuilder`.
- Chuan prompt co cac field: `DOC_TITLE`, `DIFFICULTY`, `QUESTION_COUNT`, schema JSON output.
- Provider abstraction:
  - `OpenAIProvider` neu config la `openai`.
  - `MockAIProvider` neu config la `mock`.

Config provider:
- File: `config/app.php`.
- Rule:
  - Neu `AI_PROVIDER` co gia tri -> dung gia tri do.
  - Neu `AI_PROVIDER` rong:
    - co `OPENAI_API_KEY` -> `openai`
    - khong co key -> `mock`.

OpenAI call details:
- File: `app/Services/AI/OpenAIProvider.php`.
- Endpoint: `https://api.openai.com/v1/chat/completions`.
- Payload co `response_format: { type: "json_object" }`.
- Co xu ly loi ket noi, loi HTTP >= 400, loi schema response.

AI output validation:
- `decodeJson()` + `normalizeQuestions()`.
- Loai bo cau loi schema, dap an trung, correct answer khong hop le, cau trung lap.
- Mode suggestion cho phep "partial count", nhung phai con it nhat 1 cau hop le.

Merge + human confirmation:
- Suggestions duoc merge dedupe voi danh sach hien co (`mergeSuggestionQuestions`).
- User phai tick `include_suggestions[]` de chap nhan tung cau.
- Khi `savePreview`, chi suggestion da tick moi duoc merge vao de chinh.

Ket luan AI boundary:
- Khong co duong code nao dung AI de tu tao de ngay sau import.
- AI khong tu dong ghi de vao DB.
- AI chi tao "de xuat", quyet dinh cuoi cung la cua tac gia.

### 00.4 Thiet Ke Parser Nhan Dien Cau Hoi Tu File Import

File chinh:
- `app/Services/QuizGenerationService.php::parseQuestionsFromDocument()`
- `parseQuestionsByExplicitMarkers()` (secondary exhaustive parser)

Muc tieu parser:
- Nhan dien duoc cau hoi co cau truc tu van ban import (txt/docx/pdf da extract text).
- Co gang khong bo sot cau co marker ro rang.
- Khong gioi han so cau import theo so luong co san trong file.

Chi tiet thuat toan:

1. Chuan hoa text dau vao
- Chuan hoa xuong dong, khoang trang, bo dong trong thua.

2. Tach dong + xu ly option inline
- `splitLinesWithInlineOptions()` + `explodeInlineOptions()`.
- Ho tro truong hop A/B/C/D nam tren cung 1 dong.

3. Primary state-machine parser
- State gom: `question_lines`, `options`, `explicit_correct`, `has_question_marker`.
- Detect start cau hoi (`isQuestionStartLine`) theo pattern:
  - `Cau N`, `Bai N`, `Question N`, `QN`, hoac so thu tu + dong tu hoi bai toan.
- Parse option line:
  - Ho tro `A/B/C/D` va ca dang so `1/2/3/4`.
- Parse dong dap an:
  - Ho tro `Dap an: A`, `Answer - B`, va bien the marker.
- Ho tro option xuong dong nhieu dong (append vao option truoc do).

4. Secondary exhaustive parser theo marker ro rang
- `parseQuestionsByExplicitMarkers()` chia block theo marker cau hoi.
- `buildQuestionFromExplicitBlock()` co kha nang "cuu du lieu":
  - chap nhan block co >=3 option,
  - bo sung placeholder cho option thieu de giam mat cau,
  - tinh diem chat luong (`scoreParsedQuestionQuality`) de chon ban tot hon khi trung so cau.

5. Merge hai ket qua parser
- `mergeQuestionCollections()` dedupe theo fingerprint noi dung cau.
- KQ cuoi cung giu duy nhat, tranh trung lap.

6. Validation de tao de
- Neu parser khong lay duoc cau hop le nao -> throw `ValidationException`.
- Message loi import: khong nhan dien duoc bo cau hoi co cau truc.

Bao dam "khong gioi han so cau trong file":
- Khong co hard limit so cau o `extractQuestionsFromDocument()/parseQuestionsFromDocument()`.
- So cau parser tra ve phu thuoc vao du lieu file.
- Muc gioi han 1..10 chi ap dung cho **AI suggestion count**, khong ap dung cho import parser.

Bao dam gui du lieu day du o preview:
- Dung hidden JSON `questions_payload` khi submit preview.
- Muc dich: tranh truncation do `max_input_vars` khi so cau lon.
- Vi tri: `app/Views/quizzes/preview.php` + `public/assets/js/app.js::setupPreviewQuestionPayload()`.

### 00.5 Checklist Doi Chieu Nhanh

- [x] Role matrix va route access da ghi ro.
- [x] Owner-check critical da liet ke.
- [x] Module architecture da tach theo responsibility.
- [x] AI boundary "chi goi y" da mo ta theo endpoint + flow.
- [x] Parser architecture nhan dien cau hoi tu file da ghi chi tiet.
- [x] Constraint hien tai (difficulty default medium, admin dashboard chung workspace) da neu ro.

