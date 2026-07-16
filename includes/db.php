<?php
// includes/db.php

require_once('includes/config.php');

/**
 * دریافت اتصال به دیتابیس
 */
function getDBConnection(): PDO {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("❌ خطا در اتصال به دیتابیس: " . $e->getMessage());
    }
}

/**
 * اجرای کوئری با پارامترها
 */
function executeQuery(string $sql, array $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * دریافت آخرین ID درج‌شده
 */
function getLastInsertId(): int {
    $pdo = getDBConnection();
    return (int) $pdo->lastInsertId();
}

/**
 * ذخیره آزمون در دیتابیس
 */
function saveExam(array $data): int {
    $sql = "INSERT INTO exams (
        user_id, exam_title, file_name, file_path, 
        num_questions, difficulty, question_type, 
        questions, answer_key, status
    ) VALUES (
        :user_id, :exam_title, :file_name, :file_path,
        :num_questions, :difficulty, :question_type,
        :questions, :answer_key, :status
    )";
    
    // استخراج پاسخ‌ها از سوالات
    $answerKey = [];
    if (isset($data['questions']) && is_array($data['questions'])) {
        foreach ($data['questions'] as $q) {
            $answerKey[] = [
                'id' => $q['id'] ?? 0,
                'correct_answer' => $q['correct_answer'] ?? '',
                'source' => $q['source'] ?? ''
            ];
        }
    }
    
    $params = [
        ':user_id' => $data['user_id'] ?? null,
        ':exam_title' => $data['exam_title'] ?? 'آزمون بدون عنوان',
        ':file_name' => $data['file_name'] ?? '',
        ':file_path' => $data['file_path'] ?? '',
        ':num_questions' => count($data['questions'] ?? []),
        ':difficulty' => $data['difficulty'] ?? 'medium',
        ':question_type' => $data['question_type'] ?? 'multiple_choice',
        ':questions' => json_encode($data['questions'] ?? [], JSON_UNESCAPED_UNICODE),
        ':answer_key' => json_encode($answerKey, JSON_UNESCAPED_UNICODE),
        ':status' => $data['status'] ?? 'completed'
    ];
    
    executeQuery($sql, $params);
    return getLastInsertId();
}

/**
 * دریافت آزمون‌های یک کاربر
 */
function getUserExams(int $userId = null): array {
    $sql = "SELECT * FROM exams ORDER BY created_at DESC";
    $params = [];
    
    if ($userId !== null) {
        $sql = "SELECT * FROM exams WHERE user_id = :user_id ORDER BY created_at DESC";
        $params = [':user_id' => $userId];
    }
    
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * دریافت یک آزمون با ID
 */
function getExamById(int $id): ?array {
    $stmt = executeQuery("SELECT * FROM exams WHERE id = :id", [':id' => $id]);
    $exam = $stmt->fetch();
    
    if ($exam) {
        // تبدیل JSON به آرایه
        $exam['questions'] = json_decode($exam['questions'], true);
        $exam['answer_key'] = json_decode($exam['answer_key'], true);
    }
    
    return $exam ?: null;
}

/**
 * ذخیره فایل موقت
 */
function saveTempFile(string $fileName, string $filePath, string $text, array $chunks, array $keywords): int {
    $sql = "INSERT INTO temp_files (
        file_name, file_path, extracted_text, chunks, keywords
    ) VALUES (
        :file_name, :file_path, :extracted_text, :chunks, :keywords
    )";
    
    $params = [
        ':file_name' => $fileName,
        ':file_path' => $filePath,
        ':extracted_text' => $text,
        ':chunks' => json_encode($chunks, JSON_UNESCAPED_UNICODE),
        ':keywords' => json_encode($keywords, JSON_UNESCAPED_UNICODE)
    ];
    
    executeQuery($sql, $params);
    return getLastInsertId();
}

/**
 * دریافت تنظیمات
 */
function getSetting(string $key): ?string {
    $stmt = executeQuery(
        "SELECT setting_value FROM settings WHERE setting_key = :key",
        [':key' => $key]
    );
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

/**
 * ذخیره تنظیمات
 */
function saveSetting(string $key, string $value): void {
    executeQuery(
        "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE setting_value = :value",
        [':key' => $key, ':value' => $value]
    );
}