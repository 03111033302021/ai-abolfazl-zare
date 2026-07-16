<?php

/**
 * -----------------------------------------
 * AI Exam Generator
 * استخراج کلمات کلیدی و مفاهیم مهم
 * -----------------------------------------
 */

/**
 * استخراج کلمات کلیدی از متن
 * 
 * @param string $text متن ورودی
 * @param int $limit تعداد کلمات کلیدی مورد نظر
 * @return array آرایه کلمات کلیدی با وزن
 */
function extractKeywords(string $text, int $limit = 10): array
{
    // 1. پاکسازی متن
    $text = cleanTextForKeywords($text);
    
    // 2. استخراج کلمات
    $words = extractWords($text);
    
    // 3. حذف کلمات کم‌معنی (Stop Words)
    $words = removeStopWords($words);
    
    // 4. شمارش تکرار و محاسبه وزن
    $wordScores = calculateWordScores($words, $text);
    
    // 5. مرتب‌سازی و انتخاب کلمات برتر
    arsort($wordScores);
    $topKeywords = array_slice($wordScores, 0, $limit, true);
    
    return $topKeywords;
}

/**
 * پاکسازی متن برای استخراج کلمات کلیدی
 */
function cleanTextForKeywords(string $text): string
{
    // حذف اعداد
    $text = preg_replace('/[۰-۹0-9]+/u', '', $text);
    
    // حذف علائم نگارشی
    $text = preg_replace('/[،؛:.!؟?()\-]/u', ' ', $text);
    
    // حذف فاصله‌های اضافی
    $text = preg_replace('/\s+/u', ' ', $text);
    
    // تبدیل به حروف کوچک (برای فارسی فرقی نمی‌کنه)
    return trim($text);
}

/**
 * استخراج کلمات از متن
 */
function extractWords(string $text): array
{
    // تقسیم بر اساس فاصله
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    // حذف کلمات کمتر از ۲ کاراکتر
    $words = array_filter($words, function($word) {
        return mb_strlen($word) >= 2;
    });
    
    return $words;
}

/**
 * حذف کلمات کم‌معنی (Stop Words)
 */
function removeStopWords(array $words): array
{
    $stopWords = [
        'و', 'در', 'به', 'از', 'که', 'این', 'با', 'برای', 'را', 'تا',
        'بر', 'است', 'ها', 'های', 'یک', 'دو', 'سه', 'چهار', 'پنج',
        'اول', 'دوم', 'سوم', 'چهارم', 'پنجم', 'بعد', 'قبل', 'حال',
        'آن', 'هم', 'نیز', 'هر', 'چون', 'مگر', 'اما', 'اگر', 'پس',
        'بله', 'خیر', 'نه', 'آری', 'همان', 'همین', 'چنین', 'یا',
        'بلکه', 'چرا', 'چگونه', 'کجا', 'کی', 'چه', 'چند', 'چقدر',
        'همه', 'همگی', 'کدام', 'چنین', 'چنان', 'چون', 'که', 'تا'
    ];
    
    return array_filter($words, function($word) use ($stopWords) {
        return !in_array($word, $stopWords);
    });
}

/**
 * محاسبه وزن کلمات بر اساس تکرار و موقعیت
 */
function calculateWordScores(array $words, string $text): array
{
    // 1. شمارش تکرار کلمات
    $wordCount = array_count_values($words);
    
    // 2. محاسبه وزن بر اساس موقعیت کلمه
    $wordScores = [];
    $lines = explode("\n", $text);
    $totalLines = count($lines);
    
    foreach ($wordCount as $word => $count) {
        // وزن پایه: تعداد تکرار
        $score = $count;
        
        // وزن اضافی: کلماتی که در تیترها هستن (اول خط)
        $inTitle = false;
        foreach ($lines as $index => $line) {
            if (strpos($line, $word) !== false) {
                // کلماتی که در ۲۰٪ اول خطوط هستن، اهمیت بیشتری دارن
                if ($index < $totalLines * 0.2) {
                    $score += 3;
                }
                // کلماتی که در ۵۰٪ اول هستن
                elseif ($index < $totalLines * 0.5) {
                    $score += 1;
                }
                break;
            }
        }
        
        // وزن اضافی: کلماتی که با حرف بزرگ شروع میشن (احتمالاً اسم خاص)
        if (preg_match('/^[آ-ی]/u', $word) && mb_strlen($word) > 2) {
            $score += 0.5;
        }
        
        $wordScores[$word] = $score;
    }
    
    return $wordScores;
}

/**
 * تعیین میزان اهمیت هر بخش
 * 
 * @param string $chunk متن بخش
 * @param array $keywords کلمات کلیدی بخش
 * @return float امتیاز اهمیت (۰ تا ۱۰۰)
 */
/**
 * تعیین میزان اهمیت هر بخش
 * 
 * @param string $chunk متن بخش
 * @param array $keywords کلمات کلیدی بخش
 * @return float امتیاز اهمیت (۰ تا ۱۰۰)
 */

/**
 * تعیین میزان اهمیت هر بخش
 * 
 * @param string $chunk متن بخش
 * @param array $keywords کلمات کلیدی بخش
 * @return float امتیاز اهمیت (۰ تا ۱۰۰)
 */
function calculateSectionImportance(string $chunk, array $keywords): float
{
    $score = 0;
    
    // 1. طول متن (متن‌های طولانی‌تر معمولاً مهم‌ترن)
    $length = mb_strlen($chunk);
    if ($length > 8000) $score += 20;
    elseif ($length > 5000) $score += 15;
    elseif ($length > 3000) $score += 10;
    elseif ($length > 1500) $score += 5;
    else $score += 2;
    
    // 2. تعداد کلمات کلیدی معنی‌دار (با وزن بیشتر از ۲)
    $importantKeywords = array_filter($keywords, function($weight) {
        return $weight > 2;
    });
    $keywordCount = count($importantKeywords);
    
    if ($keywordCount >= 10) $score += 30;
    elseif ($keywordCount >= 7) $score += 22;
    elseif ($keywordCount >= 5) $score += 15;
    elseif ($keywordCount >= 3) $score += 8;
    else $score += 3;
    
    // 3. وجود تیترهای شماره‌دار (۱. , ۲. , ...)
    $lines = explode("\n", $chunk);
    $numberedTitles = 0;
    $totalLines = count($lines);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^[۰-۹0-9]+[\.\-]\s+/', $line)) {
            $numberedTitles++;
        }
    }
    
    // نسبت تیترها به کل خطوط
    $titleRatio = $numberedTitles / max(1, $totalLines);
    if ($titleRatio > 0.05) $score += 20;
    elseif ($titleRatio > 0.02) $score += 10;
    elseif ($numberedTitles > 0) $score += 5;
    
    // 4. وجود آیات، روایات یا مثال‌های مهم
    $hasQuran = preg_match('/(آیه|سوره|قرآن|﴿|﴾)/u', $chunk);
    $hasHadith = preg_match('/(حدیث|روایت|امام|پیامبر|رسول)/u', $chunk);
    $hasExample = preg_match('/(مثال|برای نمونه|به عنوان مثال|مانند)/u', $chunk);
    
    if ($hasQuran) $score += 15;
    if ($hasHadith) $score += 10;
    if ($hasExample) $score += 5;
    
    // 5. وجود کلمات کلیدی خاص (مفاهیم اصلی درس)
    $coreConcepts = [
        'اسلام', 'قرآن', 'خداوند', 'ایمان', 'تقوا', 
        'خانواده', 'ازدواج', 'همسر', 'فرزند', 'تربیت',
        'جمعیت', 'باروری', 'موالید', 'رشد'
    ];
    
    $foundCore = 0;
    foreach ($coreConcepts as $word) {
        if (strpos($chunk, $word) !== false) {
            $foundCore++;
        }
    }
    $score += min(15, $foundCore * 2);
    
    // 6. ضریب تنوع (چقدر مطالب متنوعه)
    $uniqueWords = count(array_keys($keywords));
    // جایگزین امن برای شمارش کلمات فارسی و انگلیسی
    $wordsArray = preg_split('/\s+/u', trim($chunk), -1, PREG_SPLIT_NO_EMPTY);
    $totalWords = count($wordsArray);
    $diversityRatio = $uniqueWords / max(1, $totalWords) * 100;
    if ($diversityRatio > 15) $score += 10;
    elseif ($diversityRatio > 10) $score += 5;
    else $score += 2;
    
    // محدود کردن به ۱۰۰
    return min(100, $score);
}

/**
 * انتخاب بخش‌های مناسب برای طراحی سوال
 * 
 * @param array $chunks لیست بخش‌ها
 * @param int $count تعداد بخش‌های مورد نیاز
 * @return array بخش‌های انتخاب‌شده با اولویت
 */
function selectBestSections(array $chunks, int $count = 5): array
{
    $sections = [];
    
    foreach ($chunks as $index => $chunk) {
        $keywords = extractKeywords($chunk, 10);
        $importance = calculateSectionImportance($chunk, $keywords);
        
        $sections[] = [
            'index' => $index,
            'chunk' => $chunk,
            'keywords' => $keywords,
            'importance' => $importance,
            'length' => mb_strlen($chunk)
        ];
    }
    
    // مرتب‌سازی بر اساس اهمیت (بیشترین اولویت)
    usort($sections, function($a, $b) {
        return $b['importance'] - $a['importance'];
    });
    
    // انتخاب تعداد مورد نظر
    $selected = array_slice($sections, 0, $count);
    
    // مرتب‌سازی مجدد بر اساس ترتیب اصلی
    usort($selected, function($a, $b) {
        return $a['index'] - $b['index'];
    });
    
    return $selected;
}

/**
 * خلاصه‌سازی متن برای پرامپت
 */
function summarizeForPrompt(string $text, int $maxLength = 3000): string
{
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    
    // سعی کن تیترها و جملات مهم رو نگه داری
    $lines = explode("\n", $text);
    $importantLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        
        // تیترها رو نگه دار
        if (mb_strlen($line) < 100 && !preg_match('/[.!؟]/u', $line)) {
            $importantLines[] = $line;
        }
        // جملات با کلمات کلیدی رو نگه دار
        elseif (preg_match('/(آیه|حدیث|روایت|مهم|اساسی|هدف|نتیجه)/u', $line)) {
            $importantLines[] = $line;
        }
    }
    
    $summary = implode("\n", $importantLines);
    
    // اگه بازم بزرگ بود، برش بزن
    if (mb_strlen($summary) > $maxLength) {
        $summary = mb_substr($summary, 0, $maxLength);
    }
    
    return $summary;
}