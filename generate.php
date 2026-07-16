<?php

session_start();

// زمان کافی برای همه API call‌ها
set_time_limit(600);

if (!isset($_SESSION['pdf']) || !isset($_SESSION['text'])) {
    header("Location: index.php");
    exit;
}

require_once "functions/extract_pdf.php";
require_once "functions/clean_text.php";
require_once "functions/remove_headers.php";
require_once "functions/remove_toc.php";
require_once "functions/normalize_titles.php";
require_once "functions/chunk.php";
require_once "functions/keywords.php";
require_once "functions/coverage.php";
require_once "functions/validate.php";
require_once "ai/prompt.php";
require_once "ai/openrouter.php";

$pdf = $_SESSION['pdf'];
$text = $_SESSION['text'];

$questionType = $_SESSION['question_type'] ?? 'multiple_choice';
$questionCount = $_SESSION['question_count'] ?? 10;
$difficulty = $_SESSION['difficulty'] ?? 'medium';

if (!empty($pdf)) {
    $text = extractPDF($pdf);
    if ($text === false) {
        die("خطا در استخراج متن PDF");
    }
}

$text = cleanText($text);
$text = removeHeaders($text);
$text = removeTOC($text);
$text = normalizeTitles($text);

$chunks = makeChunks($text, 50000);

$allKeywords = [];
$sectionScores = [];

foreach ($chunks as $index => $chunk) {
    $keywords = extractKeywords($chunk, 10);
    $importance = calculateSectionImportance($chunk, $keywords);
    
    $allKeywords["chunk_" . ($index + 1)] = $keywords;
    $sectionScores["chunk_" . ($index + 1)] = $importance;
}

$totalQuestions = (int)$questionCount;
$coverage = calculateCoverage($chunks, $sectionScores);
$selectedSections = selectSectionsForQuestions($chunks, $sectionScores, $totalQuestions);

$_SESSION['chunks'] = $chunks;
$_SESSION['keywords'] = $allKeywords;
$_SESSION['section_scores'] = $sectionScores;
$_SESSION['coverage'] = $coverage;
$_SESSION['selected_sections'] = $selectedSections;

// ===== نمایش دیباگ =====
echo "<h2>📊 پوشش مباحث</h2><ul>";
foreach ($coverage as $key => $percent) {
    echo "<li><strong>{$key}:</strong> {$percent}% محتوا</li>";
}
echo "</ul>";

echo "<h2>📝 توزیع سوالات ({$totalQuestions} سوال)</h2><ul>";
foreach ($selectedSections as $section) {
    echo "<li><strong>{$section['chunk_key']}:</strong> {$section['question_count']} سؤال ({$section['percentage']}%)</li>";
}
echo "</ul>";

// ===== تولید سوالات =====
echo "<h2>🔄 تولید سوالات...</h2>";

$allQuestions = [];
$questionId = 1;

foreach ($selectedSections as $section) {
    $chunk = $section['chunk'];
    $count = $section['question_count'];
    
    echo "<p>⏳ تولید {$count} سوال از {$section['chunk_key']}...</p>";
    
    switch ($questionType) {
        case 'multiple_choice':
            $prompt = buildPrompt($chunk, $count, $difficulty);
            break;
        case 'descriptive':
            $prompt = buildPromptDescriptive($chunk, $count, $difficulty);
            break;
        case 'both':
        default:
            $prompt = buildPromptBoth($chunk, $count, $difficulty);
            break;
    }
    
    $response = callOpenRouter($prompt, 'deepseek/deepseek-chat');
    $questions = extractJSONFromResponse($response);
    
    if ($questions && isset($questions['questions'])) {
        // فقط به تعداد درخواست‌شده سوال بگیر، نه بیشتر
        $received = array_slice($questions['questions'], 0, $count);
        foreach ($received as $q) {
            $q['id'] = $questionId++;
            $q['source'] = $section['chunk_key'];
            $allQuestions[] = $q;
        }
    } else {
        echo "<p style='color:red;'>❌ خطا در تولید سوالات از {$section['chunk_key']}</p>";
        // نمایش پاسخ خام برای دیباگ
        if (str_starts_with($response, '❌')) {
            echo "<p style='color:orange;font-size:12px;'>🔍 خطای API: " . htmlspecialchars($response) . "</p>";
        }
    }
}

// ===== پردازش‌های بعد از LLM =====
$fullText = $text;
$finalExam = processAfterLLM($allQuestions, $fullText);

$_SESSION['generated_questions'] = $finalExam['questions'];
$_SESSION['exam_summary'] = $finalExam['summary'];
$_SESSION['validation_result'] = $finalExam['validation'];
$_SESSION['categories'] = $finalExam['categories'] ?? [];

echo "<h2>📋 گزارش پردازش بعد از LLM</h2><ul>";
echo "<li>✅ حذف تکراری‌ها: " . $finalExam['summary']['duplicates_removed'] . " سوال حذف شد</li>";
echo "<li>✅ تستی: " . ($finalExam['summary']['multiple_choice'] ?? 0) . " سوال</li>";
echo "<li>✅ تشریحی: " . ($finalExam['summary']['descriptive'] ?? 0) . " سوال</li>";
echo "<li>✅ پاسخ‌های معتبر: " . $finalExam['validation']['valid'] . " سوال</li>";
echo "<li>❌ پاسخ‌های نامعتبر: " . $finalExam['validation']['invalid'] . " سوال</li>";
echo "<li>✅ مرتب‌سازی بر اساس دشواری: انجام شد</li>";
echo "</ul>";

echo "<h2>✅ تولید نهایی: " . count($finalExam['questions']) . " سوال</h2>";
echo "<p>در حال انتقال به صفحه نتایج...</p>";

header("refresh:3;url=result.php");
exit;