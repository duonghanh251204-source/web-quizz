SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
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
    document_id INT UNSIGNED NULL DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_quizzes_document (document_id),
    KEY idx_quizzes_created_by (created_by),
    CONSTRAINT fk_quizzes_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
    CONSTRAINT fk_quizzes_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    question_content LONGTEXT NOT NULL,
    answer_a LONGTEXT NOT NULL,
    answer_b LONGTEXT NOT NULL,
    answer_c LONGTEXT NOT NULL,
    answer_d LONGTEXT NOT NULL,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    evidence_quote LONGTEXT NULL,
    reasoning LONGTEXT NULL,
    explanation LONGTEXT NULL,
    confidence_score TINYINT UNSIGNED NULL,
    grounding_status VARCHAR(20) NULL DEFAULT 'unknown',
    source ENUM('ai', 'extract', 'manual') NOT NULL DEFAULT 'extract',
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

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reason ENUM('knowledge', 'format', 'other') NOT NULL DEFAULT 'other',
    status ENUM('open', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    admin_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reports_status (status),
    KEY idx_reports_question (question_id),
    CONSTRAINT fk_reports_question FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
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

-- Đồng bộ quizzes.document_id: cho DB cũ (NOT NULL + ON DELETE CASCADE) hoặc tái chạy script;
-- với DB mới tạo từ các CREATE ở trên, ba lệnh này vẫn an toàn (khôi phục đúng FK).
ALTER TABLE quizzes DROP FOREIGN KEY fk_quizzes_document;
ALTER TABLE quizzes MODIFY document_id INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;