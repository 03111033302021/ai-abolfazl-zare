<?php
session_start();

// دریافت اطلاعات فرم
$text = trim($_POST['text'] ?? '');
$questionCount = (int)($_POST['question_count'] ?? 5);
$questionType = $_POST['question_type'] ?? 'multiple_choice';
$difficulty = $_POST['difficulty'] ?? 'medium';

// بررسی اینکه حداقل فایل یا متن وارد شده باشد
if (empty($_FILES['pdf']['name']) && empty($text)) {
    die("لطفاً فایل PDF یا متن درس را وارد کنید.");
}

// اگر فایل انتخاب شده باشد
if (!empty($_FILES['pdf']['name'])) {

    $uploadDir = "uploads/";

    // ساخت پوشه در صورت عدم وجود
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['pdf'];

    // بررسی خطا
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("خطا در آپلود فایل.");
    }

    // بررسی پسوند
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extension !== "pdf") {
        die("فقط فایل PDF مجاز است.");
    }

    // بررسی MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime !== "application/pdf") {
        die("فایل معتبر PDF نیست.");
    }

    // ساخت نام یکتا
    $newName = uniqid("pdf_") . ".pdf";

    $destination = $uploadDir . $newName;

    // انتقال فایل
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        die("خطا در ذخیره فایل.");
    }

    // ذخیره اطلاعات در Session
    $_SESSION['pdf'] = $destination;
    $_SESSION['text'] = '';

} else {

    // کاربر متن وارد کرده است
    $_SESSION['pdf'] = '';
    $_SESSION['text'] = $text;

}

// تنظیمات آزمون
$_SESSION['question_count'] = $questionCount;
$_SESSION['question_type'] = $questionType;
$_SESSION['difficulty'] = $difficulty;

// مرحله بعد
header("Location: generate.php");
exit;