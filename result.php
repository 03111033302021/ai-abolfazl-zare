<?php
session_start();

if (!isset($_SESSION['generated_questions'])) {
    header("Location: index.php");
    exit;
}

require_once(__DIR__ . "/includes/db.php");

$questions = $_SESSION['generated_questions'];
$coverage = $_SESSION['coverage'] ?? [];
$summary = $_SESSION['exam_summary'] ?? [];
$validation = $_SESSION['validation_result'] ?? [];

// ===== ذخیره در دیتابیس =====
$dbError = null;
try {
    $examData = [
        'user_id'       => $_SESSION['user_id'] ?? null,
        'exam_title'    => 'آزمون از ' . ($_SESSION['file_name'] ?? 'فایل آموزشی'),
        'file_name'     => $_SESSION['file_name'] ?? 'unknown.pdf',
        'file_path'     => $_SESSION['pdf'] ?? '',
        'difficulty'    => $_SESSION['difficulty'] ?? 'medium',
        'question_type' => $_SESSION['question_type'] ?? 'multiple_choice',
        'questions'     => $questions,
        'status'        => 'completed'
    ];

    $examId = saveExam($examData);
    $_SESSION['last_exam_id'] = $examId;
} catch (Exception $e) {
    $dbError = $e->getMessage();
    error_log("خطا در ذخیره آزمون: " . $dbError);
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آزمون تولید شده</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Vazirmatn', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #4F46E5; margin-bottom: 10px; font-size: 28px; }
        .subtitle { text-align: center; color: #6b7280; margin-bottom: 30px; font-size: 14px; }
        .stats-box { background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .stats-box h3 { margin-bottom: 15px; color: #1e293b; font-size: 16px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; }
        .stat-item { text-align: center; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-number { display: block; font-size: 24px; font-weight: bold; color: #4F46E5; }
        .stat-number.green { color: #10b981; }
        .stat-number.red { color: #ef4444; }
        .stat-label { font-size: 12px; color: #64748b; }
        .coverage-box { background: #f3f4f6; padding: 12px 15px; border-radius: 10px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 10px 20px; }
        .coverage-item { font-size: 13px; }
        .coverage-item strong { color: #4F46E5; }
        .question { border-bottom: 1px solid #e5e7eb; padding: 25px 0; }
        .question:last-child { border-bottom: none; }
        .q-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .q-number { background: #4F46E5; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }
        .q-type { background: #e5e7eb; padding: 2px 10px; border-radius: 20px; font-size: 11px; color: #374151; }
        .q-source { background: #dbeafe; padding: 2px 10px; border-radius: 20px; font-size: 11px; color: #1e40af; }
        .q-text { font-size: 17px; font-weight: 500; margin: 10px 0; }
        .options { padding-right: 20px; margin: 10px 0; }
        .options li { list-style: none; padding: 5px 0; font-size: 15px; }
        .options li::before { content: "•"; color: #4F46E5; margin-left: 8px; }
        .answer-box { background: #ecfdf5; padding: 10px 15px; border-radius: 10px; margin-top: 10px; border-right: 4px solid #10b981; }
        .answer-box strong { color: #065f46; }
        .answer-box.invalid { background: #fef2f2; border-right-color: #ef4444; }
        .explanation-box { background: #fffbeb; padding: 10px 15px; border-radius: 10px; margin-top: 8px; border-right: 4px solid #f59e0b; }
        .explanation-box strong { color: #92400e; }
        .difficulty-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .diff-easy { background: #d1fae5; color: #065f46; }
        .diff-medium { background: #fef3c7; color: #92400e; }
        .diff-hard { background: #fee2e2; color: #991b1b; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 25px; background: #4F46E5; color: white; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; font-size: 16px; transition: 0.3s; }
        .btn-back:hover { background: #4338ca; transform: translateY(-2px); }
        .print-btn { background: #0d9488; margin-left: 10px; }
        .print-btn:hover { background: #0f766e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 آزمون تولید شده</h1>
        <p class="subtitle">تعداد سوالات: <?= count($questions) ?></p>
        
        <?php if (isset($examId)): ?>
            <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:8px 14px;border-radius:8px;margin-bottom:15px;font-size:13px;color:#065f46;">
                ✅ آزمون در دیتابیس ذخیره شد (ID: <?= $examId ?>)
            </div>
        <?php elseif (isset($dbError) && $dbError): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;padding:8px 14px;border-radius:8px;margin-bottom:15px;font-size:13px;color:#991b1b;">
                ⚠️ خطا در ذخیره دیتابیس: <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-box">
            <h3>📊 آمار آزمون</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?= count($questions) ?></span>
                    <span class="stat-label">کل سوالات</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $summary['multiple_choice'] ?? 0 ?></span>
                    <span class="stat-label">تستی</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $summary['descriptive'] ?? 0 ?></span>
                    <span class="stat-label">تشریحی</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number green"><?= $validation['valid'] ?? 0 ?></span>
                    <span class="stat-label">✅ پاسخ معتبر</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number red"><?= $validation['invalid'] ?? 0 ?></span>
                    <span class="stat-label">❌ پاسخ نامعتبر</span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($coverage)): ?>
        <div class="coverage-box">
            <?php foreach ($coverage as $key => $percent): ?>
                <span class="coverage-item"><strong><?= $key ?>:</strong> <?= $percent ?>%</span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php 
        $difficultyMap = [
            'easy' => 'آسان',
            'medium' => 'متوسط',
            'hard' => 'سخت',
            'آسان' => 'easy',
            'متوسط' => 'medium',
            'سخت' => 'hard'
        ];
        
        foreach ($questions as $index => $q): 
            $diffKey = $q['difficulty'] ?? 'medium';
            if (in_array($diffKey, ['آسان', 'متوسط', 'سخت'])) {
                $diffKey = $difficultyMap[$diffKey] ?? 'medium';
            }
            $diffLabel = $difficultyMap[$diffKey] ?? 'متوسط';
            $isValid = $q['answer_valid'] ?? true;
        ?>
            <div class="question">
                <div class="q-header">
                    <span class="q-number"><?= $index + 1 ?></span>
                    <span class="q-type"><?= ($q['type'] ?? 'multiple_choice') === 'multiple_choice' ? 'تستی' : 'تشریحی' ?></span>
                    <span class="q-source"><?= $q['source'] ?? 'نامشخص' ?></span>
                    <span class="difficulty-badge diff-<?= $diffKey ?>"><?= $diffLabel ?></span>
                    <?php if (!$isValid): ?>
                        <span style="color:#ef4444;font-size:12px;">⚠️ پاسخ نامعتبر</span>
                    <?php endif; ?>
                </div>
                
                <div class="q-text"><?= htmlspecialchars($q['question'] ?? '') ?></div>
                
                <?php if (isset($q['options']) && is_array($q['options'])): ?>
                    <ul class="options">
                        <?php foreach ($q['options'] as $option): ?>
                            <li><?= htmlspecialchars($option) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="answer-box <?= $isValid ? '' : 'invalid' ?>">
                    <strong>✅ پاسخ صحیح:</strong> <?= htmlspecialchars($q['correct_answer'] ?? '') ?>
                </div>
                
                <?php if (isset($q['explanation']) && !empty($q['explanation'])): ?>
                    <div class="explanation-box">
                        <strong>📖 توضیح:</strong> <?= htmlspecialchars($q['explanation']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn-back">🏠 بازگشت به صفحه اصلی</a>
            <a href="export_pdf.php" class="btn btn-danger">📄 دانلود PDF</a>
        </div>
        
    </div>
</body>
</html>