<?php
// includes/config.php

// ===== تنظیمات دیتابیس =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'ai_exam_generator');
define('DB_USER', 'root');
define('DB_PASS', '');

// ===== تنظیمات مسیرها =====
define('BASE_URL', 'http://localhost/AI-Exam-Generator/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// ===== تنظیمات OpenRouter API =====
define('OPENROUTER_API_KEY', 'YOUR_API_KEY');
define('OPENROUTER_MODEL', 'deepseek/deepseek-chat');
define('OPENROUTER_SITE', 'AI Exam Generator');

// ===== تنظیمات پیش‌فرض =====
define('DEFAULT_NUM_QUESTIONS', 5);
define('DEFAULT_DIFFICULTY', 'medium');
define('SUPPORTED_EXTENSIONS', ['pdf', 'txt']);

// ===== تنظیمات پردازش متن =====
define('CHUNK_SIZE', 4000);
define('OVERLAP_SIZE', 100);
define('MAX_KEYWORDS', 20);

// شروع سشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}