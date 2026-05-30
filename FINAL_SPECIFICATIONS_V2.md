# Đặc tả cuối cùng V2 — LivQuiz / PRX

Tài liệu này phản ánh **phạm vi chức năng đã lược bỏ** so với phiên bản đặc tả rộng hơn: **không** mô tả **Gợi ý AI trên preview (U7)**, **Ngân hàng câu hỏi (U9)**, và **Duyệt câu hỏi / Báo cáo** (`/admin/questions`, `/admin/reports`, …). Các route tương ứng **có thể vẫn còn trong** `routes.php` và controller — chúng được liệt kê ở mục *Phạm vi loại trừ* để tránh nhầm lẫn, nhưng **không** có trong biểu đồ và bảng đặc tả chi tiết bên dưới.

**Điểm vào ứng dụng:** `public/index.php` → `bootstrap.php` (container) → `Router::dispatch` với các tuyến khai báo trong `routes.php` (đối chiếu trực tiếp tại thời điểm rà soát).

---

## 1. Phạm vi và loại trừ

### 1.1. Thuộc phạm vi V2 (có Use Case, đặc tả, sequence)

| Nhóm | Nội dung | Route chính (tham chiếu `routes.php`) |
| --- | --- | --- |
| Công khai | Trang landing, chính sách, trợ giúp, liên hệ | `GET /`, `/privacy-policy`, `/terms-of-use`, `/help-center`, `/contact` |
| U2 | Đăng ký, đăng nhập, đăng xuất | `GET/POST /login`, `GET/POST /register`, `POST /logout` |
| U3 | Workspace / Dashboard (redirect theo vai trò) | `GET /workspace`, `GET /dashboard` |
| U4 | Quản lý tài liệu (người dùng) | `GET /documents`, `/documents/create`, `POST /documents`, `GET /documents/{id}`, `POST /documents/{id}/delete` |
| U5 | Tạo đề từ tệp + AI | `POST /documents` (`upload_mode=ai`) → preview → lưu |
| U6 | Tạo đề từ nội dung dán (parse, không AI) | `GET /quizzes/create`, `POST /quizzes` → preview → lưu |
| Luồng preview / lưu | Xem trước, lưu đề, hủy preview (**không** gợi ý AI) | `GET /quizzes/preview`, `POST /quizzes/preview/save`, `POST /quizzes/preview/discard` |
| U8 | Đề thi: danh sách, xem, làm bài, nộp, xóa, xuất; bài nộp | `GET/POST` các route `/quizzes*`, `/submissions*`, `GET /quizzes/{id}/export` |
| A1 | Dashboard quản trị | `GET /admin`, `GET /admin/dashboard` |
| A2 | Cấu hình AI runtime (form admin) | `GET /admin/ai`, `POST /admin/ai` |
| A4 | Thành viên: danh sách, đổi vai trò, khóa | `GET /admin/members`, `GET /admin/users`, `POST /admin/users/{id}/role`, `POST /admin/users/{id}/lock` |

### 1.2. Ngoài phạm vi V2 (đã lược bỏ khỏi tài liệu & biểu đồ)

| Mục | Route / Controller (vẫn có thể tồn tại trong mã) |
| --- | --- |
| Gợi ý AI (U7) | `POST /quizzes/preview/suggest-ai` → `QuizController::suggestAiPreview` |
| Ngân hàng câu hỏi (U9) | `GET/POST /questions*`, `QuestionController` (trừ khi sau này gỡ route) |
| Duyệt câu hỏi & Báo cáo (phần A3) | `GET /admin/questions`, `GET /admin/reports`, `POST /admin/reports/{id}/status` |

**Ghi chú:** `GET /admin/documents`, `POST /admin/documents/{id}/delete` vẫn map trong `routes.php` nhưng **không** nằm trong danh sách Use Case tổng quát do người dùng yêu cầu; có thể bổ sung ở phiên bản đặc tả sau nếu cần.

---

## 2. Biểu đồ Use Case tổng quát (phạm vi V2)

```mermaid
flowchart TB
    Guest((Khách))
    User((Người dùng))
    Admin((Quản trị viên))
    AISvc((CHATBOT-AI / LLM))

    subgraph UC_PUB["Công khai"]
        P1[Xem landing & trang thông tin]
    end

    subgraph UC_USER["Người dùng"]
        U2[Đăng ký / Đăng nhập / Đăng xuất]
        U3[Workspace / Dashboard]
        U4[Quản lý tài liệu]
        U5[Tạo đề từ tệp + AI]
        U6[Tạo đề từ nội dung dán]
        PV[Xem trước & Lưu / Hủy preview]
        U8[Đề thi: danh sách, xem, làm, nộp, xuất]
        US[Xem bài nộp]
    end

    subgraph UC_ADMIN["Quản trị"]
        A1[Dashboard thống kê]
        A2[Cấu hình AI runtime]
        A4[Thành viên: vai trò & khóa]
    end

    Guest --> P1
    Guest --> U2
    User --> U3
    User --> U4
    User --> U5
    User --> U6
    User --> PV
    User --> U8
    User --> US
    Admin --> A1
    Admin --> A2
    Admin --> A4
    Admin --> U4
    U5 --> AISvc
```

---

## 3. Bảng đặc tả Use Case chi tiết (đối chiếu `routes.php`)

Cột **Route** ghi đúng method và path đang đăng ký trong `routes.php` (rà soát theo mã nguồn hiện tại).

| ID | Tên | Route | Controller::method | Tác nhân | Tiền điều kiện | Luồng chính | Ngoại lệ |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **P1** | Trang công khai | `GET /`, `/privacy-policy`, `/terms-of-use`, `/help-center`, `/contact` | `LandingController::index`, `privacyPolicy`, `termsOfUse`, `helpCenter`, `contact` | Khách | Không | Router dispatch → render view landing / `info_page`. `GET /` nếu đã đăng nhập thì redirect `roleHomePath`. | — |
| **U2** | Đăng ký / Đăng nhập / Đăng xuất | `GET/POST /login`, `GET/POST /register`, `POST /logout` | `AuthController` | Khách / Người dùng | Form có CSRF (POST); đăng ký: mật khẩu ≥ 6 ký tự | Validator → `AuthService::register` / `login` → `PlatformRepository` → ghi session `auth_user_id` → redirect. Logout: xóa session. | CSRF; sai thông tin; email trùng; tài khoản khóa |
| **U3** | Workspace / Dashboard | `GET /workspace`, `GET /dashboard` | `WorkspaceController::index` | Người dùng / Admin | Đã đăng nhập | `requireAuth` → redirect: admin → `/admin/dashboard`, user → `/quizzes`. | Chưa đăng nhập → `/login` |
| **U4** | Quản lý tài liệu | `GET /documents`, `/documents/create`, `POST /documents`, `GET /documents/{id}`, `POST /documents/{id}/delete` | `DocumentController` | Người dùng; Admin xem toàn bộ | Đã đăng nhập; POST có CSRF | `index` / `create` / `show` / `delete`: `listDocuments` / `findDocumentById` / `deleteDocument` + quyền sở hữu; `store` với `upload_mode=ai` thuộc **U5** (xem bảng U5). | Không quyền; không tìm thấy; CSRF |
| **U5** | Tạo đề từ tệp + AI | `POST /documents` (`upload_mode=ai`); sau đó `GET /quizzes/preview`, `POST /quizzes/preview/save` | `DocumentController::store` → `handleGenerateWithAI`; `QuizController::preview`, `savePreview` | Người dùng; AI | Đã đăng nhập; cấu hình AI hợp lệ | Trích xuất văn bản → `createDocument` → `QuizGenerationService::generateAiSuggestions` → `AIProviderInterface` (vd. `ChatbotAIServiceProvider` → FastAPI `/upload`, `/generate`) → session draft → preview → lưu `createQuiz`. | File lỗi; trích xuất lỗi; AI lỗi; CSRF; validation preview |
| **U6** | Tạo đề từ nội dung dán | `GET /quizzes/create`, `POST /quizzes`; sau đó preview / save | `QuizController::create`, `store`, `preview`, `savePreview` | Người dùng | Đã đăng nhập | `store`: `extractQuestionsFromDocument` (parse, **không** gọi AI) → draft session → preview → `savePreview` → `createQuiz`. | Parse thất bại; CSRF; validation |
| **PV** | Xem trước & Lưu / Hủy | `GET /quizzes/preview`, `POST /quizzes/preview/save`, `POST /quizzes/preview/discard` | `QuizController::preview`, `savePreview`, `discardPreview` | Người dùng | Có draft session (trừ discard có thể xử lý CSRF) | `preview` render; `savePreview` merge câu (và gợi ý đã tick nếu có trong draft từ phiên bản cũ — trong V2 không tạo gợi ý mới); `discardPreview` xóa draft. | Không draft; không câu hợp lệ khi lưu |
| **U8** | Đề thi & làm bài & xuất | `GET /quizzes`, `/quizzes/create`, `/quizzes/{id}`, `/quizzes/{id}/take`, `POST /quizzes/{id}/submit`, `POST /quizzes/{id}/delete`, `GET /quizzes/{id}/export` | `QuizController` | Người dùng; Admin | Đã đăng nhập | Danh sách theo `created_by`; `show` chỉ admin/chủ đề; `take`/`submit` qua `SubmissionEvaluationService`; `export` DOCX; `delete` có quyền. | Quiz không tồn tại; quyền; lỗi xuất file |
| **US** | Xem bài nộp | `GET /submissions`, `GET /submissions/{id}` | `SubmissionController::index`, `show` | Người dùng; Admin | Đã đăng nhập | `listSubmissions` (lọc user hoặc toàn bộ admin); `show` + `findSubmissionAnswers` với kiểm tra quyền. | Không tìm thấy; không quyền |
| **A1** | Dashboard Admin | `GET /admin`, `GET /admin/dashboard` | `AdminController::index`, `dashboard` | Admin | Role admin | `index` redirect dashboard; thống kê + chuỗi ngày biểu đồ. | `requireAuth` từ chối |
| **A2** | Cấu hình AI | `GET /admin/ai`, `POST /admin/ai` | `AdminController::aiSettings`, `saveAiSettings` | Admin | Role admin | Đọc/ghi settings DB + env; lưu provider OpenAI/Gemini/DeepSeek, model, key. | CSRF; model rỗng |
| **A4** | Thành viên | `GET /admin/members`, `GET /admin/users`, `POST /admin/users/{id}/role`, `POST /admin/users/{id}/lock` | `AdminController::members`, `users`, `updateUserRole`, `updateUserLock` | Admin | Role admin | Danh sách user; đổi role (không hạ admin cuối); khóa/mở (không khóa chính mình / admin). | CSRF; rule nghiệp vụ |

---

## 4. Biểu đồ trình tự (Sequence Diagram) — theo nhóm

### Nhóm 1 — Xác thực & Workspace (U2, U3)

```mermaid
sequenceDiagram
    autonumber
    actor U as Người dùng
    participant BR as Trình duyệt
    participant IDX as public/index.php
    participant RT as Router + routes.php
    participant AC as AuthController
    participant WC as WorkspaceController
    participant AS as AuthService
    participant SE as Session
    participant REPO as PlatformRepositoryInterface
    participant DB as Database

    rect rgb(245, 245, 255)
        Note over U,DB: U2a — POST /register
        U->>BR: Form đăng ký + CSRF
        BR->>IDX: POST /register
        IDX->>RT: dispatch
        RT->>AC: register
        AC->>AC: verifyCsrfToken, Validator
        AC->>AS: register(name, email, password)
        AS->>REPO: findUserByEmail
        REPO->>DB: SELECT
        DB-->>REPO: row hoặc null
        AS->>REPO: createUser (nếu hợp lệ)
        REPO->>DB: INSERT
        AS->>SE: put(auth_user_id)
        AC-->>BR: 302 roleHomePath
    end

    rect rgb(255, 250, 240)
        Note over U,DB: U2b — POST /login
        U->>BR: POST /login
        BR->>RT: dispatch → AC: login
        AC->>AS: login(email, password)
        AS->>REPO: findUserByEmail
        REPO->>DB: SELECT
        AS->>SE: put(auth_user_id) nếu hợp lệ
        AC-->>BR: 302 roleHomePath
    end

    rect rgb(240, 255, 240)
        Note over U,BR: U2c — POST /logout
        U->>BR: POST /logout + CSRF
        BR->>RT: dispatch → AC: logout
        AC->>AS: logout (invalidate session)
        AC-->>BR: 302 /
    end

    rect rgb(248, 248, 248)
        Note over U,BR: U3 — GET /workspace hoặc /dashboard
        U->>BR: GET /workspace
        BR->>RT: dispatch → WC: index
        WC->>WC: requireAuth
        WC-->>BR: 302 /admin/dashboard hoặc /quizzes
    end
```

### Nhóm 2 — Quản lý tài liệu & Tạo đề AI / Thủ công (U4, U5, U6)

```mermaid
sequenceDiagram
    autonumber
    actor U as Người dùng
    participant BR as Trình duyệt
    participant IDX as public/index.php
    participant RT as Router + routes.php
    participant DC as DocumentController
    participant EXT as DocumentTextExtractorService
    participant QC as QuizController
    participant QGS as QuizGenerationService
    participant AI as AIProviderInterface\n(ChatbotAIServiceProvider\nhoặc LLM khác)
    participant FAST as CHATBOT-AI FastAPI\nkhi chatbot_ai
    participant REPO as PlatformRepositoryInterface
    participant DB as Database
    participant SE as Session

    rect rgb(240, 248, 255)
        Note over U,DB: U4 — GET /documents (danh sách)
        U->>BR: GET /documents
        BR->>RT: dispatch → DC: index
        DC->>DC: requireAuth
        DC->>REPO: listDocuments(userId hoặc null)
        REPO->>DB: SELECT
        DC-->>BR: HTML

        Note over U,DB: U4 — GET /documents/create, GET /documents/id, POST delete (rút gọn)
        U->>BR: POST /documents/id/delete + CSRF
        BR->>RT: dispatch → DC: delete
        DC->>REPO: findDocumentById, deleteDocument\n+ unlink file nếu có
    end

    rect rgb(255, 245, 238)
        Note over U,FAST: U5 — POST /documents upload_mode=ai (rút gọn nhánh AI)
        U->>BR: POST /documents multipart + CSRF
        BR->>RT: dispatch → DC: store → handleGenerateWithAI
        DC->>DC: prepareUploadedDocument, move file
        DC->>EXT: extract(path, ext)
        EXT-->>DC: extracted_content
        DC->>REPO: createDocument
        REPO->>DB: INSERT document
        DC->>QGS: generateAiSuggestions(...)
        QGS->>AI: generate(prompt)
        opt AI_PROVIDER = chatbot_ai
            AI->>FAST: POST /upload
            FAST-->>AI: session_id
            AI->>FAST: POST /generate
            FAST-->>AI: questions JSON
        end
        AI-->>QGS: AIResult
        QGS-->>DC: questions
        DC->>SE: put quiz_generation_draft
        DC-->>BR: 302 /quizzes/preview
    end

    rect rgb(245, 255, 245)
        Note over U,SE: U6 — POST /quizzes (dán nội dung, không AI)
        U->>BR: POST /quizzes + CSRF
        BR->>RT: dispatch → QC: store
        QC->>QGS: extractQuestionsFromDocument
        QGS-->>QC: questions (parse)
        QC->>SE: saveDraft
        QC-->>BR: 302 /quizzes/preview
    end

    rect rgb(255, 255, 240)
        Note over U,DB: Lưu đề từ preview — POST /quizzes/preview/save
        U->>BR: POST save + CSRF
        BR->>RT: dispatch → QC: savePreview
        QC->>REPO: createQuiz(..., questions)
        REPO->>DB: INSERT quiz + questions
        QC->>QC: clearDraft
        QC-->>BR: 302 /quizzes/{id}
    end
```

### Nhóm 3 — Thực hiện bài thi & Xuất đề (U8)

```mermaid
sequenceDiagram
    autonumber
    actor U as Người dùng
    participant BR as Trình duyệt
    participant RT as Router + routes.php
    participant QC as QuizController
    participant SC as SubmissionController
    participant EV as SubmissionEvaluationService
    participant EXP as QuizDocxExportService
    participant RES as Response
    participant REPO as PlatformRepositoryInterface
    participant DB as Database

    rect rgb(240, 248, 255)
        Note over U,DB: Danh sách & xem đề
        U->>BR: GET /quizzes
        BR->>RT: dispatch → QC: index
        QC->>REPO: listQuizzes + lọc created_by
        QC-->>BR: HTML

        U->>BR: GET /quizzes/{id}
        BR->>RT: dispatch → QC: show
        QC->>REPO: findQuizById, findQuestionsByQuizId
        QC-->>BR: HTML (quyền admin/chủ đề)
    end

    rect rgb(255, 248, 240)
        Note over U,DB: Làm bài & nộp bài
        U->>BR: GET /quizzes/{id}/take
        BR->>RT: dispatch → QC: take
        QC->>REPO: findQuizById, findQuestionsByQuizId
        QC-->>BR: HTML làm bài

        U->>BR: POST /quizzes/{id}/submit + CSRF
        BR->>RT: dispatch → QC: submit
        QC->>REPO: findQuestionsByQuizId
        QC->>EV: evaluate(questions, answers)
        QC->>REPO: createSubmission(...)
        REPO->>DB: INSERT submission
        QC-->>BR: 302 /submissions/{id}
    end

    rect rgb(245, 255, 245)
        Note over U,RES: Xuất DOCX — GET /quizzes/{id}/export
        U->>BR: GET /quizzes/{id}/export
        BR->>RT: dispatch → QC: export
        QC->>REPO: findQuizById, findQuestionsByQuizId
        QC->>EXP: build(quiz, questions, withAnswers)
        EXP-->>QC: binary docx
        QC->>RES: download(...)
        RES-->>BR: file Word
    end

    rect rgb(248, 248, 255)
        Note over U,DB: Xóa đề — POST /quizzes/{id}/delete
        U->>BR: POST delete + CSRF
        BR->>RT: dispatch → QC: delete
        QC->>REPO: deleteQuiz (nếu quyền)
        QC-->>BR: 302 /quizzes
    end

    rect rgb(255, 255, 240)
        Note over U,DB: Xem bài nộp — GET /submissions, /submissions/{id}
        U->>BR: GET /submissions
        BR->>RT: dispatch → SC: index
        SC->>REPO: listSubmissions
        SC-->>BR: HTML

        U->>BR: GET /submissions/{id}
        BR->>RT: dispatch → SC: show
        SC->>REPO: findSubmissionById, findSubmissionAnswers
        SC-->>BR: HTML
    end
```

### Nhóm 4 — Quản trị (A1, A2, A4)

```mermaid
sequenceDiagram
    autonumber
    actor A as Quản trị viên
    participant BR as Trình duyệt
    participant RT as Router + routes.php
    participant AD as AdminController
    participant REPO as PlatformRepositoryInterface
    participant DB as Database

    rect rgb(240, 240, 255)
        Note over A,DB: A1 — GET /admin/dashboard
        A->>BR: GET /admin/dashboard
        BR->>RT: dispatch → AD: dashboard
        AD->>REPO: getAdminDashboardStats,\ngetDocumentUploadCountsByDay,\ngetQuestionActivityByDay
        REPO->>DB: SELECT / aggregate
        AD-->>BR: HTML dashboard
    end

    rect rgb(255, 245, 238)
        Note over A,DB: A2 — GET/POST /admin/ai
        A->>BR: GET /admin/ai
        BR->>RT: dispatch → AD: aiSettings
        AD->>REPO: getSetting (provider, model, keys)
        AD-->>BR: HTML cấu hình AI

        A->>BR: POST /admin/ai + CSRF
        BR->>RT: dispatch → AD: saveAiSettings
        AD->>REPO: setSetting (provider, model, keys, clear template)
        AD-->>BR: 302 /admin/ai
    end

    rect rgb(245, 255, 245)
        Note over A,DB: A4 — Thành viên
        A->>BR: GET /admin/members
        BR->>RT: dispatch → AD: members
        AD->>REPO: listUsers, countUsersByRole
        AD-->>BR: HTML

        A->>BR: POST /admin/users/{id}/role + CSRF
        BR->>RT: dispatch → AD: updateUserRole
        AD->>REPO: updateUserRole (sau kiểm tra admin cuối)

        A->>BR: POST /admin/users/{id}/lock + CSRF
        BR->>RT: dispatch → AD: updateUserLock
        AD->>REPO: updateUserLocked
        AD-->>BR: 302 /admin/members
    end
```

---

## 5. Biểu đồ hoạt động — Upload tài liệu → AI → Preview → Lưu đề

Luồng **U5** kết hợp **`POST /documents`** (AI) và **`POST /quizzes/preview/save`**. Nhánh **CHATBOT-AI** minh họa khi `AI_PROVIDER=chatbot_ai`; provider khác thay bằng gọi API LLM trực tiếp trong PHP.

```mermaid
flowchart TD
    Start([GET /documents/create]) --> Fill[Điền form + chọn file]
    Fill --> Post[POST /documents\nupload_mode = ai]
    Post --> V1{CSRF + title + file\nhợp lệ?}
    V1 -->|Không| Err1[Flash → /documents/create]
    V1 -->|Có| Save[move_uploaded_file\nstorage/uploads]
    Save --> Ex[DocumentTextExtractorService::extract]
    Ex -->|Lỗi| Err2[Xóa file, flash]
    Ex -->|OK| Ins[createDocument → MySQL]
    Ins --> Gen[QuizGenerationService::generateAiSuggestions]
    Gen --> Build[QuizFromDocumentPromptBuilder::build]
    Build --> Prov{Provider}
    Prov -->|chatbot_ai| Up[Temp .txt + POST\nCHATBOT-AI /upload]
    Up --> Gn[POST /generate\nJSON session_id, ...]
    Gn --> Map[mapQuestions + normalizeQuestions]
    Prov -->|Khác| Api[Gọi LLM trong PHP] --> Map
    Map --> Sess[Session quiz_generation_draft]
    Sess --> Prev[GET /quizzes/preview\nhiển thị form]
    Prev --> Edit[Người dùng chỉnh sửa tiêu đề / câu hỏi]
    Edit --> SaveP[POST /quizzes/preview/save]
    SaveP --> V2{CSRF + draft +\ntiêu đề + câu hợp lệ?}
    V2 -->|Không| Err3[Flash → preview]
    V2 -->|Có| CQ[createQuiz + câu hỏi\n→ MySQL]
    CQ --> Clr[clearDraft]
    Clr --> Done([302 /quizzes/id])
    Err1 --> End([Kết thúc])
    Err2 --> End
    Err3 --> End
    Done --> End
```

---

## 6. Tóm tắt route `routes.php` thuộc phạm vi V2

Các dòng sau **có trong** `routes.php` và **được đặc tả** trong V2:

- `LandingController`: `/`, `/privacy-policy`, `/terms-of-use`, `/help-center`, `/contact`
- `AuthController`: `/login`, `/register`, `/logout` (GET/POST theo routes)
- `WorkspaceController`: `/workspace`, `/dashboard`
- `DocumentController`: `/documents` (GET create, POST, GET `{id}`, POST delete)
- `QuizController`: `/quizzes`, `/quizzes/create`, POST `/quizzes`, `/quizzes/preview`, POST `preview/save`, POST `preview/discard`, `/quizzes/{id}`, `take`, POST `submit`, POST `delete`, GET `export`
- `SubmissionController`: `/submissions`, `/submissions/{id}`
- `AdminController`: `/admin`, `/admin/dashboard`, `/admin/ai`, `/admin/members`, `/admin/users`, POST `users/{id}/role`, POST `users/{id}/lock`
- `LeaderboardController`: `/leaderboard` (redirect — có thể ghi chú ngắn trong triển khai)

**Không đưa vào đặc tả V2 (theo yêu cầu lược bỏ):** `POST /quizzes/preview/suggest-ai`; toàn bộ `/questions*`; `/admin/questions`, `/admin/reports`, `POST /admin/reports/{id}/status`.

---

*Tài liệu V2 — đối chiếu mã nguồn `routes.php` và controller map tại thời điểm tạo. Khi gỡ route hoặc đổi tên action, cần cập nhật lại tài liệu này.*
