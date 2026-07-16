<?php
/**
 * اجرای این فایل یک بار برای fix کردن charset دیتابیس
 * بعد از اجرا این فایل رو پاک کن
 */
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sqls = [
        "ALTER DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE exams CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE temp_files CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    foreach ($sqls as $sql) {
        $pdo->exec($sql);
        echo "✅ " . htmlspecialchars($sql) . "<br>";
    }

    echo "<br><strong>✅ همه جداول به utf8mb4 تبدیل شدند. این فایل را پاک کنید.</strong>";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage();
}
