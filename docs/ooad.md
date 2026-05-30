# OOAD - PRX AI Quiz Platform

## Muc tieu
He thong web ho tro tao va van hanh de thi trac nghiem tu tai lieu hoc tap.

## Actors
- Guest: xem landing, login, register.
- Admin: full quyen quan tri.
- User: lam bai va xem ket qua.

## Use Cases
1. Upload tai lieu (Admin).
2. Generate AI questions tu noi dung tai lieu (Admin).
3. Manage all questions CRUD (Admin).
4. Create quiz (Admin).
5. Export exam (Admin).
6. Take quiz (User/Admin).
7. View result stats (User/Admin theo pham vi quyen).

## Domain Objects
- User
- Document
- Quiz
- Question
- Submission
- SubmissionAnswer

## Kien truc
- MVC + Service Layer + Repository.
- `AIProviderInterface` de thay doi provider linh hoat.
- Controller chi orchestration, validation va authorization.
- Repository thao tac SQL.

## Rule permission
- Admin: full access.
- User: chi take quiz + view own results.
