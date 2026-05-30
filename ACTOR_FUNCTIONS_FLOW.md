# Chuc Nang Theo Actor Va Luong Hoat Dong He Thong

Tai lieu nay tong hop cac chuc nang da trien khai trong PRX theo tung actor va mo ta luong hoat dong thuc te theo code hien tai.

## 1) Tong Quan Actor

- Guest (khach chua dang nhap)
  - Truy cap cac trang gioi thieu.
  - Dang ky tai khoan.
  - Dang nhap vao he thong.

- User (thi sinh/nguoi dung thuong)
  - Tai len tai lieu, trich xuat noi dung.
  - Tao quiz tu tai lieu, xem truoc, yeu cau goi y AI, luu quiz.
  - Lam bai kiem tra, nop bai, xem ket qua cua chinh minh.
  - Bao cao cau hoi loi (sai kien thuc/loi dinh dang/khac).

- Admin (quan tri vien)
  - Co toan bo quyen cua User.
  - Dashboard thong ke he thong.
  - Cau hinh AI (prompt + API key).
  - Giam sat ngan hang cau hoi va xu ly bao cao loi.
  - Quan ly tai lieu he thong.
  - Quan ly thanh vien (phan quyen, khoa/mo khoa tai khoan).

## 2) Chuc Nang Theo Tung Actor

## Guest

- Truy cap cac trang cong khai:
  - `/`
  - `/privacy-policy`
  - `/terms-of-use`
  - `/help-center`
  - `/contact`
- Dang ky:
  - `GET /register` mo form.
  - `POST /register` tao tai khoan.
  - Validate co ban: name, email, password; password >= 6 ky tu.
- Dang nhap:
  - `GET /login` mo form.
  - `POST /login` xac thuc tai khoan.
- Sau dang nhap:
  - Dieu huong theo vai tro qua `roleHomePath()`.
  - User -> khu vuc hoc tap/quiz.
  - Admin -> `/admin/dashboard`.

## User

- Workspace va dieu huong:
  - `GET /workspace` va `GET /dashboard` deu redirect theo vai tro.

- Quan ly tai lieu:
  - `GET /documents`: danh sach tai lieu cua chinh user.
  - `GET /documents/create`: form tai tai lieu.
  - `POST /documents`: upload PDF/DOCX/TXT, gioi han 10MB, trich xuat text.
  - `GET /documents/{id}`: xem chi tiet tai lieu (owner-scoped).

- Tao va quan ly quiz:
  - `GET /quizzes`: xem danh sach quiz.
  - `GET /quizzes/create`: chon tai lieu de tao quiz.
  - `POST /quizzes`: parser/trich xuat cau hoi tu noi dung tai lieu -> tao draft.
  - `GET /quizzes/preview`: xem truoc bo cau hoi.
  - `POST /quizzes/preview/suggest-ai`: yeu cau AI goi y them cau hoi.
  - `POST /quizzes/preview/save`: chot de, luu quiz + question vao CSDL.
  - `POST /quizzes/preview/discard`: huy draft.
  - `GET /quizzes/{id}`: xem chi tiet quiz.
  - `GET /quizzes/{id}/take`: vao trang lam bai.
  - `POST /quizzes/{id}/submit`: nop bai va cham diem tu dong.

- Ket qua va lich su:
  - `GET /submissions`: xem danh sach bai nop cua minh.
  - `GET /submissions/{id}`: xem chi tiet bai nop cua minh.

- Bao cao chat luong cau hoi:
  - `POST /questions/{id}/report`: gui bao cao loi cau hoi.
  - Ly do bao cao duoc ho tro: `knowledge`, `format`, `other`.

## Admin

- Dashboard he thong:
  - `GET /admin` -> redirect `/admin/dashboard`.
  - `GET /admin/dashboard`:
    - So lieu tong: users, documents, questions, questions_ai, questions_extract.
    - Bieu do theo ngay (7/14/30 ngay): luong upload va luong tao cau hoi.

- Quan ly cau hinh AI:
  - `GET /admin/ai`: mo man hinh cau hinh AI.
  - `POST /admin/ai`:
    - Luu prompt template.
    - Luu/thay doi/xoa API key OpenAI trong settings.

- Quan ly ngan hang cau hoi:
  - `GET /admin/questions`:
    - Loc theo quiz.
    - Loc theo nguon: `ai` / `extract`.
    - Xem nhanh va mo rong noi dung cau hoi.
  - Qua `QuestionController` (yeu cau admin):
    - `GET /questions`
    - `GET /questions/create`
    - `POST /questions`
    - `GET /questions/{id}/edit`
    - `POST /questions/{id}/update`
    - `POST /questions/{id}/correct`
    - `POST /questions/{id}/delete`

- Xu ly bao cao loi:
  - `GET /admin/reports`: xem danh sach report.
  - `POST /admin/reports/{id}/status`: doi trang thai `open/resolved/dismissed` + ghi chu admin.

- Quan ly tai lieu he thong:
  - `GET /admin/documents`: xem tat ca tai lieu.
  - `POST /admin/documents/{id}/delete`: xoa ban ghi va tep dinh kem tren server (neu co).

- Quan ly thanh vien:
  - `GET /admin/members`: thong ke va danh sach user.
  - `GET /admin/users`: route cu, redirect ve `/admin/members`.
  - `POST /admin/users/{id}/role`: doi role `user/admin`.
  - `POST /admin/users/{id}/lock`: khoa/mo khoa tai khoan.
  - Rang buoc bao ve:
    - Khong duoc khoa chinh minh.
    - Khong duoc ha quyen admin cuoi cung.
    - Khong khoa tai khoan admin khi chua ha quyen.

## 3) Luong Hoat Dong Chinh

## Luong A - Guest vao he thong

1. Guest mo trang chu `/`.
2. Chon dang ky hoac dang nhap.
3. He thong validate form + CSRF.
4. Dang nhap thanh cong -> redirect theo vai tro.

## Luong B - User tao de tu tai lieu

1. User upload tai lieu (`/documents/create` -> `POST /documents`).
2. He thong:
   - Validate extension va kich thuoc.
   - Luu file vao `storage/uploads`.
   - Trich xuat noi dung bang `DocumentTextExtractorService`.
   - Luu metadata + extracted_content vao CSDL.
3. User vao `/quizzes/create`, chon tai lieu.
4. `POST /quizzes`:
   - Parser sinh bo cau hoi draft.
   - Luu draft vao session.
5. User vao `/quizzes/preview`:
   - Sua noi dung cau hoi.
   - Co the goi AI de de xuat them (`suggest-ai`).
6. `POST /quizzes/preview/save`:
   - Hop nhat cau hoi goc + cau goi y duoc chon.
   - Tao quiz + questions trong CSDL.
7. User chia se/lam bai theo lien ket quiz.

## Luong C - User lam bai va xem ket qua

1. User vao `/quizzes/{id}/take`.
2. Chon dap an va nop bai (`POST /quizzes/{id}/submit`).
3. He thong cham diem (`SubmissionEvaluationService`) va luu submission.
4. User xem lich su tai `/submissions` va chi tiet tai `/submissions/{id}`.

## Luong D - User bao cao cau hoi loi

1. Trong qua trinh lam bai/xem quiz, user bao cao cau hoi.
2. `POST /questions/{id}/report` luu report vao he thong.
3. Admin nhin thay tai `/admin/reports` de xu ly.

## Luong E - Admin van hanh va kiem soat

1. Admin vao `/admin/dashboard` de theo doi suc khoe he thong.
2. Neu can tinh chinh AI:
   - Vao `/admin/ai`.
   - Sua prompt va/hoac API key.
3. Neu can kiem duyet du lieu:
   - Vao `/admin/questions` de sua/xoa/doi dap an.
4. Neu co report:
   - Vao `/admin/reports`, cap nhat trang thai xu ly.
5. Neu can don dep tai nguyen:
   - Vao `/admin/documents`, xoa tep khong phu hop.
6. Quan tri nguoi dung:
   - Vao `/admin/members`, phan quyen/khoa-mo khoa.

## 4) Quy Tac Bao Mat Va Phan Quyen

- Xac thuc:
  - Cac route nghiep vu deu yeu cau dang nhap qua `requireAuth()`.
- Phan quyen:
  - Chuc nang admin duoc bao ve boi `requireAuth(['admin'])`.
  - Nguoi dung thuong bi owner-scope voi tai lieu va submission.
- CSRF:
  - Tat ca form POST quan trong deu verify token.
- Session:
  - Luu user login state.
  - Luu draft quiz trong luong preview.

## 5) Ghi Chu Van Hanh

- AI trong he thong mang tinh "goi y co chu dong" (khong bat buoc), ket hop voi bo parser/trich xuat tu tai lieu.
- Dashboard admin la noi theo doi tong quan + luu luong theo ngay de quan sat tai nguyen.
- Co route legacy `/admin/users` de tuong thich, nhung man hinh chuan la `/admin/members`.
