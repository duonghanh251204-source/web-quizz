-- Bảng questions cũ thiếu cột source (đếm dashboard / lọc câu hỏi).
-- Chạy một lần trên DB đã tạo trước khi cột này có trong schema.sql.

ALTER TABLE questions
ADD COLUMN source ENUM('ai', 'extract', 'manual') NOT NULL DEFAULT 'extract' AFTER correct_answer;
