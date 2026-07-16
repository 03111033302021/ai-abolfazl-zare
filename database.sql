-- ایجاد دیتابیس با پشتیبانی کامل از فارسی و ایموجی
CREATE DATABASE IF NOT EXISTS ai_exam_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_exam_generator;

-- جدول کاربران
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول آزمون‌ها
CREATE TABLE IF NOT EXISTS exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    exam_title VARCHAR(255),
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    num_questions INT,
    difficulty VARCHAR(20),
    question_type VARCHAR(50),
    questions LONGTEXT,
    answer_key LONGTEXT,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول فایل‌های موقت
CREATE TABLE IF NOT EXISTS temp_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    extracted_text LONGTEXT,
    chunks LONGTEXT,
    keywords LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تنظیمات
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تنظیمات پیش‌فرض
INSERT INTO settings (setting_key, setting_value) VALUES
('api_key', 'your-openrouter-api-key-here'),
('api_model', 'deepseek/deepseek-chat'),
('default_questions', '10'),
('default_difficulty', 'medium')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
