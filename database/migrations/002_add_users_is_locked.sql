-- Bảng users cũ thiếu cột is_locked (khóa tài khoản / danh sách thành viên).

ALTER TABLE users
ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER role;
