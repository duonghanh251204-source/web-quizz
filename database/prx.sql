-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 04, 2026 lúc 05:03 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `prx`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_path` varchar(255) NOT NULL,
  `mime_type` varchar(191) NOT NULL,
  `extracted_content` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `title`, `original_file_name`, `stored_file_path`, `mime_type`, `extracted_content`, `created_at`) VALUES
(16, 2, 'lịch sử', 'Các phong trào yêu nước tiêu biểu như.docx', 'storage/uploads/20260604153905_4436b27ebe76.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Các phong trào yêu nước tiêu biểu như: Phong trào Cần Vương do các văn thân, sĩ phu yêu nước lãnh đạo, diễn ra sôi nổi trong cả nước và kéo dài hơn 10 năm từ 1885 đến 1896; các cuộc nổi dậy chống quân xâm lược ở các vùng địch chiếm đóng, tiêu biểu như: khởi nghĩa Trương Định, Nguyễn Trung Trực, Võ Duy Dương, Nguyễn Hữu Huân; các cuộc đấu tranh của nhân dân các địa phương trung du miền núi, nổi bật nhất là khởi nghĩa Yên Thế do Hoàng Hoa Thám lãnh đạo (1884 - 1913); các cuộc khởi nghĩa: Hương Khê (1885 - 1896) do Phan Đình Phùng và Cao Thắng lãnh đạo; Ba Đình (1886 - 1887) do Phạm Bành và Đinh Công Tráng đứng đầu; Bãi Sậy (1885 - 1889) do Nguyễn Thiên Thuật chỉ huy; cuộc nổi dậy ở Hưng Hóa (1885 - 1889) của Nguyễn Quang Bích; phong trào yêu nước theo khuynh hướng dân chủ tư sản do các cụ Phan Bội Châu, Phan Chu Trinh lãnh đạo; cuộc khởi nghĩa Yên Bái do Nguyễn Thái Học lãnh đạo.\nCác văn thân, sĩ phu yêu nước, những người đứng đầu các cuộc đấu tranh đã dựa vào dân, tin tưởng vào sức mạnh và ý chí quật cường của nhân dân, trở thành ngọn cờ quy tụ, đoàn kết nhân dân chống Pháp. Truyền thống yêu nước quật cường của dân tộc trỗi dậy mạnh mẽ, nhân dân khắp cả nước tích cực đứng lên chống lại kẻ thù xâm lược, bảo vệ đất nước. Nhưng do thiếu đường lối đúng đắn, thiếu tổ chức và lực lượng cần thiết nên lần lượt thất bại.\nĐảng Cộng sản Việt Nam ra đời, tổ chức và lãnh đạo mọi thắng lợi của cách mạng Việt Nam\n3/2/1930, Đảng Cộng sản Việt Nam ra đời, lãnh đạo nhân dân cả nước đứng lên chống thực dân, đế quốc, giành độc lập cho dân tộc. Dưới sự lãnh đạo của Đảng, các cao trào cách mạng diễn ra trên khắp cả nước với khí thế sôi nổi.\n9/3/1945, phát xít Nhật đảo chính hất cẳng Pháp. Ngay trong đêm đó, Hội nghị Ban Thường vụ Trung ương mở rộng quyết định phát động một cao trào.\n3/1945, Trung ương Đảng ra Chỉ thị “Nhật - Pháp bắn nhau và hành động của chúng ta”.\n4/1945, Trung ương triệu tập Hội nghị quân sự cách mạng Bắc Kỳ, quyết định nhiều vấn đề quan trọng, thống nhất các lực lượng vũ trang thành Việt Nam Giải phóng quân.\n16/4/1945, Tổng bộ Việt Minh ra Chỉ thị tổ chức các Ủy ban Dân tộc giải phóng các cấp và chuẩn bị thành lập Ủy ban giải phóng dân tộc Việt Nam, tức Chính phủ lâm thời cách mạng Việt Nam.\nTừ tháng 4/1945, cao trào kháng Nhật, cứu nước diễn ra mạnh mẽ.\nĐầu tháng 5/1945, Bác Hồ từ Cao Bằng về Tuyên Quang, chọn Tân Trào làm căn cứ chỉ đạo cách mạng cả nước.', '2026-06-04 15:39:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `questions`
--

CREATE TABLE `questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `question_content` longtext NOT NULL,
  `answer_a` longtext NOT NULL,
  `answer_b` longtext NOT NULL,
  `answer_c` longtext NOT NULL,
  `answer_d` longtext NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `source` enum('ai','extract','manual') NOT NULL DEFAULT 'extract',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `position`, `question_content`, `answer_a`, `answer_b`, `answer_c`, `answer_d`, `correct_answer`, `source`, `created_at`) VALUES
(169, 10, 1, 'Phong trào Cần Vương do ai lãnh đạo?', 'Phan Đình Phùng', 'Hoàng Hoa Thám', 'Các văn thân, sĩ phu yêu nước', 'Nguyễn Thái Học', 'C', 'ai', '2026-06-04 15:39:52'),
(170, 10, 2, 'Phong trào Cần Vương diễn ra trong khoảng thời gian nào?', '1886 đến 1887', '1885 đến 1889', '1884 đến 1913', '1885 đến 1896', 'D', 'ai', '2026-06-04 15:39:52'),
(171, 10, 3, 'Khởi nghĩa Yên Thế do ai lãnh đạo?', 'Nguyễn Thiên Thuật', 'Hoàng Hoa Thám', 'Nguyễn Quang Bích', 'Phan Đình Phùng', 'B', 'ai', '2026-06-04 15:39:52'),
(172, 10, 4, 'Khởi nghĩa Hương Khê do ai lãnh đạo?', 'Nguyễn Thiên Thuật', 'Nguyễn Quang Bích', 'Phan Đình Phùng và Cao Thắng', 'Phạm Bành và Đinh Công Tráng', 'C', 'ai', '2026-06-04 15:39:52'),
(173, 10, 5, 'Khởi nghĩa Ba Đình do ai đứng đầu?', 'Nguyễn Thiên Thuật', 'Phạm Bành và Đinh Công Tráng', 'Phan Đình Phùng và Cao Thắng', 'Nguyễn Quang Bích', 'B', 'ai', '2026-06-04 15:39:52'),
(174, 10, 6, 'Khởi nghĩa Bãi Sậy do ai chỉ huy?', 'Hoàng Hoa Thám', 'Phan Đình Phùng', 'Nguyễn Thiên Thuật', 'Nguyễn Quang Bích', 'C', 'ai', '2026-06-04 15:39:52'),
(175, 10, 7, 'Đảng Cộng sản Việt Nam ra đời vào ngày tháng năm nào?', '3/2/1930', 'Đầu tháng 5/1945', '9/3/1945', '16/4/1945', 'A', 'ai', '2026-06-04 15:39:52'),
(176, 10, 8, 'Sau khi Đảng Cộng sản Việt Nam ra đời, các cao trào cách mạng diễn ra như thế nào?', 'Trên khắp cả nước với khí thế sôi nổi', 'Yếu ớt và rời rạc', 'Không có cao trào nào', 'Chỉ ở một số địa phương', 'A', 'ai', '2026-06-04 15:39:52'),
(177, 10, 9, 'Hội nghị Ban Thường vụ Trung ương mở rộng quyết định phát động một cao trào vào thời điểm nào?', 'Tháng 3/1945', 'Đầu tháng 5/1945', 'Tháng 4/1945', 'Ngay trong đêm 9/3/1945', 'D', 'ai', '2026-06-04 15:39:52'),
(178, 10, 10, 'Bác Hồ chọn địa điểm nào làm căn cứ chỉ đạo cách mạng cả nước vào đầu tháng 5/1945?', 'Cao Bằng', 'Bắc Kỳ', 'Tân Trào', 'Tuyên Quang', 'C', 'ai', '2026-06-04 15:39:52'),
(179, 11, 1, 'Đâu là thuật ngữ điểm ảnh?', 'picel', 'pixel', 'voxel', 'point', 'B', 'extract', '2026-06-04 15:42:13'),
(180, 11, 2, 'Các thang giá trị mức xám trong ảnh thường dùng là các giá trị nào?', '0, 14, 30, 62, 126, 254', '1, 15, 31, 63, 127, 255', '2, 16, 32, 64, 128, 256', '3, 17, 33, 65, 129, 257', 'C', 'extract', '2026-06-04 15:42:13'),
(181, 11, 3, 'Ảnh nhị phân có bao nhiêu màu', '0', '1', '2', '3', 'C', 'extract', '2026-06-04 15:42:13'),
(182, 11, 4, 'Đâu là cấu trúc dữ liệu ảnh', 'Vector', 'Rester', 'Matrix', 'Hue', 'A', 'extract', '2026-06-04 15:42:13'),
(183, 11, 5, 'Đối với cấu trúc dữ liệu ảnh Raster, ảnh được biểu diễn dưới dạng…', 'các đoạn thẳng', 'các mảng màu', 'ma trận các điểm ảnh', 'biểu đồ màu', 'C', 'extract', '2026-06-04 15:42:13'),
(184, 11, 6, 'Ảnh đa mức xám có 256 mức xám, mức xám của ảnh được xác định trong khoảng nào?', '[1, 256]', '[0,256]', '[1, 255]', '[0,255]', 'D', 'extract', '2026-06-04 15:42:13'),
(185, 11, 7, 'Kỹ thuật xử lý ảnh là quá trình….', 'Biến đổi một hình ảnh thành một hình ảnh khác bằng các phương pháp thủ công', 'Biến đổi một hình ảnh thành một hình ảnh khác một cách tự động bằng máy tính', 'Chụp ảnh từ các thiết bị', 'Lưu trữ và nén ảnh', 'B', 'extract', '2026-06-04 15:42:13'),
(186, 11, 8, 'Xử lý ảnh là quá trình biến đổi từ ảnh ban đầu được thu nhận sang một không gian mới nhằm làm gì?', 'Giảm kích thước ảnh', 'Làm nổi bật đặc tính dữ liệu', 'Lưu trữ ảnh trong cơ sở dữ liệu', 'Tăng độ phân giải của ảnh', 'B', 'extract', '2026-06-04 15:42:13'),
(187, 11, 9, 'Quá trình nào trong hệ thống xử lý ảnh liên quan đến việc khử nhiễu và cải thiện chất lượng ảnh?', 'Thu nhận ảnh', 'Tiền xử lý (preprocessing)', 'Trích chọn đặc trưng', 'Ra quyết định', 'B', 'extract', '2026-06-04 15:42:13'),
(188, 11, 10, 'Trích chọn đặc trưng trong hệ thống xử lý ảnh có mục đích gì?', 'Thu nhận ảnh', 'Phân biệt mẫu dữ liệu dễ dàng hơn', 'Biểu diễn tri thức', 'Xử lý nhiễu ngẫu nhiên', 'B', 'extract', '2026-06-04 15:42:13'),
(189, 11, 11, 'Mục đích chính của tiền xử lý ảnh là gì?', 'Tăng kích thước ảnh', 'Khử nhiễu và làm nổi bật các tính chất của ảnh', 'Phân loại các đối tượng trong ảnh', 'Lưu trữ ảnh vào cơ sở dữ liệu', 'B', 'extract', '2026-06-04 15:42:13'),
(190, 11, 12, 'Mô hình màu RGB có các màu cơ sở nào?', 'Red, Green, Blue', 'Cyan, Magenta, Yellow', 'Hue, Saturation, Value', 'Hue, Lightness, Saturation', 'A', 'extract', '2026-06-04 15:42:13'),
(191, 11, 13, 'Trong mô hình màu RGB, gốc tọa độ biểu diễn màu nào?', 'màu đỏ', 'màu trắng', 'màu đen', 'màu xanh', 'C', 'extract', '2026-06-04 15:42:13'),
(192, 11, 14, 'Mô hình màu HSV được biểu diễn bởi…', 'hình nón', 'hình lập phương', 'hình trụ', 'hình lưỡi', 'A', 'extract', '2026-06-04 15:42:13'),
(193, 11, 15, 'Cho biết đây là mô hình màu nào?', 'HSV', 'CMY', 'RGB', 'CIE', 'C', 'extract', '2026-06-04 15:42:13'),
(194, 11, 16, 'Ảnh nhị phân sử dụng bao nhiêu bit/pixel', '0', '1', '2', '3', 'B', 'extract', '2026-06-04 15:42:13'),
(195, 11, 17, 'Ảnh màu RGB thường sử dụng bao nhiêu bit/pixel', '10', '15', '20', '24', 'D', 'extract', '2026-06-04 15:42:13'),
(196, 11, 18, 'Phần mềm Photoshop thực hiện được bước nào trong hệ thống xử lý ảnh?', 'Tiền xử lý ảnh', 'Trích chọn đặc trưng', 'Phân loại, nhận dạng mẫu', 'Ra quyết định', 'A', 'extract', '2026-06-04 15:42:13'),
(197, 11, 19, 'Trong lĩnh vực giao thông, xử lý ảnh được ứng dụng trong hệ thống nào sau đây?', 'Hệ thống nhận dạng vân tay', 'Hệ thống xe không người lái và giám sát giao thông thông minh', 'Hệ thống quản lý hồ sơ y tế', 'Hệ thống dự báo thời tiết', 'B', 'extract', '2026-06-04 15:42:13'),
(198, 11, 20, 'Trong mô hình màu RGB, tọa độ (1,1,1) biểu diễn màu nào?', 'màu đỏ', 'màu trắng', 'màu đen', 'màu xanh', 'B', 'extract', '2026-06-04 15:42:13'),
(199, 11, 21, 'Trong mô hình màu RGB, đường chéo nối tọa độ (0,0,0) và (1,1,1) có màu nào?', 'màu đỏ', 'màu trắng', 'màu đen', 'màu xám', 'D', 'extract', '2026-06-04 15:42:13'),
(200, 11, 22, 'Mô hình màu CMY có các màu cơ sở nào?', 'Red, Green, Blue&nbsp;', 'Cyan, Magenta, Yellow', 'Hue, Saturation, Value', 'Hue, Lightness, Saturation', 'B', 'extract', '2026-06-04 15:42:13'),
(201, 11, 23, 'Ảnh đa cấp xám 256 mức xám sử dụng bao nhiêu bit/pixel', '2', '4', '6', '8', 'D', 'extract', '2026-06-04 15:42:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `question_reports`
--

CREATE TABLE `question_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reason` enum('knowledge','format','other') NOT NULL DEFAULT 'other',
  `status` enum('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `admin_note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `document_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quizzes`
--

INSERT INTO `quizzes` (`id`, `document_id`, `created_by`, `title`, `difficulty`, `created_at`) VALUES
(10, 16, 2, 'Bai kiem tra tu lịch sử (Tiếng Việt)', 'medium', '2026-06-04 15:39:52'),
(11, NULL, 2, 'bai1', 'medium', '2026-06-04 15:42:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `submissions`
--

CREATE TABLE `submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `total_correct` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `submissions`
--

INSERT INTO `submissions` (`id`, `quiz_id`, `user_id`, `score`, `total_questions`, `total_correct`, `created_at`) VALUES
(4, 10, 2, 10, 10, 1, '2026-06-04 15:40:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `submission_answers`
--

CREATE TABLE `submission_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_answer` enum('A','B','C','D','') NOT NULL DEFAULT '',
  `is_correct` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `submission_answers`
--

INSERT INTO `submission_answers` (`id`, `submission_id`, `question_id`, `selected_answer`, `is_correct`) VALUES
(51, 4, 169, 'C', 1),
(52, 4, 170, 'B', 0),
(53, 4, 171, 'A', 0),
(54, 4, 172, 'B', 0),
(55, 4, 173, 'A', 0),
(56, 4, 174, 'A', 0),
(57, 4, 175, 'B', 0),
(58, 4, 176, 'D', 0),
(59, 4, 177, 'A', 0),
(60, 4, 178, 'D', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_locked`, `created_at`) VALUES
(1, 'Administrator', 'admin@prx.local', '$2y$10$BIBTnSiGMFEFwGiwYAABh.0umS/ZH1y5VVe1FKxVUSwHLMs0MXxWK', 'admin', 0, '2026-05-02 23:48:02'),
(2, 'duonghanh', 'hanh123@gmail.com', '$2y$10$r1.dqVdrG6pRcOY/ZTKbGu9YlRgygmta/g1IM4wDao2nvcUawC.rK', 'user', 0, '2026-05-02 18:49:12'),
(3, 'duonghoa', 'hoa123@gmail.com', '$2y$10$wIvQkE32ODJ49w..4LhVROEO644e/SSp.22cirONb.iCPYnUL3i7a', 'admin', 0, '2026-05-04 12:02:45');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Chỉ mục cho bảng `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_documents_user` (`user_id`);

--
-- Chỉ mục cho bảng `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_quiz_position` (`quiz_id`,`position`);

--
-- Chỉ mục cho bảng `question_reports`
--
ALTER TABLE `question_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_question` (`question_id`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Chỉ mục cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quizzes_document` (`document_id`),
  ADD KEY `idx_quizzes_created_by` (`created_by`);

--
-- Chỉ mục cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submissions_quiz` (`quiz_id`),
  ADD KEY `idx_submissions_user` (`user_id`);

--
-- Chỉ mục cho bảng `submission_answers`
--
ALTER TABLE `submission_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submission_answers_submission` (`submission_id`),
  ADD KEY `fk_submission_answers_question` (`question_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT cho bảng `question_reports`
--
ALTER TABLE `question_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `submission_answers`
--
ALTER TABLE `submission_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `question_reports`
--
ALTER TABLE `question_reports`
  ADD CONSTRAINT `fk_reports_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quizzes_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_quizzes_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_submissions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `submission_answers`
--
ALTER TABLE `submission_answers`
  ADD CONSTRAINT `fk_submission_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_answers_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
