# Kế hoạch tích hợp CHATBOT-AI và PRX UI vào hệ thống PRX MVC

Dựa trên phân tích, bạn hiện có 3 thành phần chính trong thư mục `C:\xampp\htdocs\PRX`:
1. **Core PRX**: Hệ thống backend PHP MVC đã được xây dựng trước đó (xử lý user, database, routes, views cũ).
2. **CHATBOT-AI**: Backend Python FastAPI chuyên xử lý trích xuất tài liệu và gọi LLM (Gemini/OpenAI) để sinh câu hỏi.
3. **UI**: Giao diện HTML tĩnh mới thiết kế với TailwindCSS (`dangnhap.html`, `import.html`, `Takequiz.html`,...).

Mục tiêu là gộp chúng lại thành một hệ thống hoàn chỉnh: sử dụng giao diện mới, backend PHP quản lý luồng người dùng/dữ liệu, và giao tiếp với microservice Python để xử lý AI.

## User Review Required

> [!IMPORTANT]
> - Vui lòng xem qua kế hoạch này và xác nhận xem việc chuyển đổi logic xử lý AI từ PHP thuần sang gọi API của `CHATBOT-AI` có đúng với ý định của bạn không.
> - Các file HTML tĩnh trong thư mục `UI` sẽ được chuyển thành các file `.php` (Views) của hệ thống MVC. Bạn có muốn giữ cấu trúc component bằng cách tách Header, Sidebar ra thành layout chung không?

## Open Questions

> [!WARNING]
> 1. Hiện tại Python chạy ở cổng 8000, PHP chạy ở thư mục htdocs (XAMPP). Bạn có muốn tạo một script `start.bat` để chạy cả hai cùng lúc không?
> 2. Trong thư mục `UI`, file `Crequiz.html` đang trống. Giao diện tạo quiz (chỉnh sửa câu hỏi) có được bao gồm trong file nào khác (như `preview.html`) không?

## Proposed Changes

### Tích hợp Giao diện (Frontend UI)
Thay thế giao diện cũ trong `app/Views/` bằng giao diện mới từ thư mục `UI`.

#### [MODIFY] `app/Views/layout/main.php`
- Cập nhật layout chính để sử dụng cấu trúc HTML và cấu hình TailwindCSS từ giao diện mới (ví dụ từ `dangnhap.html` hoặc `import.html`).
- Đưa script `<script id="tailwind-config">` và các classes của Tailwind vào layout.
- Tách Sidebar và Top Navbar thành các partial views để dùng chung.

#### [NEW/MODIFY] Các Views chức năng
- `dangnhap.html` ➔ **`app/Views/auth/login.php`**
- `dangky.html` ➔ **`app/Views/auth/register.php`**
- `import.html` ➔ **`app/Views/documents/create.php`** (Giao diện tải tài liệu lên)
- `Takequiz.html` ➔ **`app/Views/quizzes/take.php`** (Giao diện làm bài thi)
- `preview.html` ➔ **`app/Views/quizzes/preview.php`** (Giao diện xem trước và sửa câu hỏi AI sinh ra)
- `Share.html`, `result.html`, `saukhidangnhap.html` ➔ Cập nhật các view tương ứng như `dashboard`, `submissions`.

---

### Tích hợp Logic Sinh câu hỏi (Backend PHP gọi Python API)
Sửa đổi các service PHP để không tự gọi LLM nữa mà chuyển việc này cho FastAPI (`CHATBOT-AI`).

#### [MODIFY] `app/Services/DocumentTextExtractorService.php`
- Thay vì dùng thư viện PHP để đọc file, service này sẽ đẩy file đính kèm qua HTTP (dùng `curl` hoặc Guzzle) lên endpoint `POST http://localhost:8000/upload` của FastAPI.
- Nhận về `session_id` và các thông tin cơ bản.

#### [MODIFY] `app/Services/QuizGenerationService.php`
- Dùng `session_id` thu được từ bước trên để gọi `POST http://localhost:8000/generate` thay vì tự gửi Prompt lên OpenAI/Gemini từ PHP.
- Chờ phản hồi JSON chứa danh sách câu hỏi và map kết quả này vào cơ sở dữ liệu/session của PHP giống luồng hiện tại.

#### [MODIFY] `config/app.php` hoặc `.env`
- Thêm biến môi trường `AI_SERVICE_URL=http://localhost:8000` để PHP trỏ tới Python backend.

## Verification Plan

### Automated / Manual Verification
1. Chạy XAMPP (Apache) cho PRX và chạy `uvicorn app.api.main:app --reload` cho CHATBOT-AI.
2. Mở trình duyệt vào trang web:
   - Đăng nhập/Đăng ký để xem giao diện Tailwind mới có hiển thị đúng không.
   - Test luồng "Upload Tài Liệu" (`/documents/create`): Kiểm tra xem PHP có đẩy file qua Python thành công và trả về UI không.
   - Test luồng "Generate Quiz": Nhấn sinh câu hỏi và xem AI có xử lý trả kết quả về bảng preview không.
3. Test làm bài (`/quizzes/{id}/take`) bằng giao diện mới.
