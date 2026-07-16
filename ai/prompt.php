<?php

/**
 * -----------------------------------------
 * AI Exam Generator
 * ساخت پرامپت برای تولید سوالات
 * -----------------------------------------
 */

function buildPrompt(string $chunk, int $questionCount = 10, string $difficulty = 'medium'): string
{
    $keywords = extractKeywords($chunk, 10);
    $keywordsStr = implode('، ', array_keys($keywords));
    
    $difficultyMap = [
        'easy' => 'آسان',
        'medium' => 'متوسط',
        'hard' => 'سخت'
    ];
    $diff = $difficultyMap[$difficulty] ?? 'متوسط';
    
    $text = mb_substr($chunk, 0, 4000);
    $lastPeriod = mb_strrpos($text, '.');
    if ($lastPeriod !== false) {
        $text = mb_substr($text, 0, $lastPeriod + 1);
    }
    
    $prompt = <<<PROMPT
از متن زیر، {$questionCount} سوال تستی چهارگزینه‌ای با سطح {$diff} به همراه پاسخنامه تولید کن.

متن:
{$text}

کلمات کلیدی مهم: {$keywordsStr}

**قوانین بسیار مهم - لطفاً دقیقاً رعایت کن:**
1. ❌ فقط و فقط از مطالبی که در متن بالا آمده استفاده کن.
2. ❌ هیچ اطلاعاتی از خارج از متن (حتی اطلاعات عمومی، قرآنی یا حدیثی) به کار نبر.
3. ❌ اگه جواب سوالی در متن نبود، اون سوال رو تولید نکن.
4. ✅ پاسخ‌ها باید مستقیماً از متن قابل استخراج باشن.
5. ✅ توضیحات (explanation) باید عیناً از متن گرفته بشه.

خروجی رو به صورت JSON برگردان با این ساختار:
{
    "questions": [
        {
            "id": 1,
            "type": "multiple_choice",
            "question": "متن سوال",
            "options": ["گزینه 1", "گزینه 2", "گزینه 3", "گزینه 4"],
            "correct_answer": "گزینه صحیح",
            "explanation": "توضیح دلیل پاسخ صحیح (فقط از متن)",
            "difficulty": "{$diff}"
        }
    ]
}

فقط JSON برگردان، هیچ توضیح اضافی نده.
PROMPT;

    return $prompt;
}

function buildPromptDescriptive(string $chunk, int $questionCount = 5, string $difficulty = 'medium'): string
{
    $text = mb_substr($chunk, 0, 4000);
    $lastPeriod = mb_strrpos($text, '.');
    if ($lastPeriod !== false) {
        $text = mb_substr($text, 0, $lastPeriod + 1);
    }
    
    $difficultyMap = [
        'easy' => 'آسان',
        'medium' => 'متوسط',
        'hard' => 'سخت'
    ];
    $diff = $difficultyMap[$difficulty] ?? 'متوسط';
    
    $prompt = <<<PROMPT
از متن زیر، {$questionCount} سوال تشریحی با سطح {$diff} به همراه پاسخنامه کامل تولید کن.

متن:
{$text}

**قوانین بسیار مهم - لطفاً دقیقاً رعایت کن:**
1. ❌ فقط و فقط از مطالبی که در متن بالا آمده استفاده کن.
2. ❌ هیچ اطلاعاتی از خارج از متن (حتی اطلاعات عمومی، قرآنی یا حدیثی) به کار نبر.
3. ❌ اگه جواب سوالی در متن نبود، اون سوال رو تولید نکن.
4. ✅ پاسخ‌ها باید مستقیماً از متن قابل استخراج باشن.
5. ✅ توضیحات (explanation) باید عیناً از متن گرفته بشه.

خروجی رو به صورت JSON برگردان با این ساختار:
{
    "questions": [
        {
            "id": 1,
            "type": "descriptive",
            "question": "متن سوال تشریحی",
            "correct_answer": "پاسخ کامل سوال (فقط از متن)",
            "explanation": "توضیح تکمیلی (فقط از متن)",
            "difficulty": "{$diff}"
        }
    ]
}

فقط JSON برگردان، هیچ توضیح اضافی نده.
PROMPT;

    return $prompt;
}

function buildPromptBoth(string $chunk, int $questionCount = 10, string $difficulty = 'medium'): string
{
    $text = mb_substr($chunk, 0, 4000);
    $lastPeriod = mb_strrpos($text, '.');
    if ($lastPeriod !== false) {
        $text = mb_substr($text, 0, $lastPeriod + 1);
    }
    
    $difficultyMap = [
        'easy' => 'آسان',
        'medium' => 'متوسط',
        'hard' => 'سخت'
    ];
    $diff = $difficultyMap[$difficulty] ?? 'متوسط';
    
    $testCount = round($questionCount * 0.6);
    $descriptiveCount = $questionCount - $testCount;
    
    $prompt = <<<PROMPT
از متن زیر، {$testCount} سوال تستی چهارگزینه‌ای و {$descriptiveCount} سوال تشریحی با سطح {$diff} به همراه پاسخنامه تولید کن.

متن:
{$text}

**قوانین بسیار مهم - لطفاً دقیقاً رعایت کن:**
1. ❌ فقط و فقط از مطالبی که در متن بالا آمده استفاده کن.
2. ❌ هیچ اطلاعاتی از خارج از متن (حتی اطلاعات عمومی، قرآنی یا حدیثی) به کار نبر.
3. ❌ اگه جواب سوالی در متن نبود، اون سوال رو تولید نکن.
4. ✅ پاسخ‌ها باید مستقیماً از متن قابل استخراج باشن.
5. ✅ توضیحات (explanation) باید عیناً از متن گرفته بشه.

خروجی رو به صورت JSON برگردان با این ساختار:
{
    "questions": [
        {
            "id": 1,
            "type": "multiple_choice",
            "question": "متن سوال تستی",
            "options": ["گزینه 1", "گزینه 2", "گزینه 3", "گزینه 4"],
            "correct_answer": "گزینه صحیح (فقط از متن)",
            "explanation": "توضیح دلیل پاسخ صحیح (فقط از متن)",
            "difficulty": "{$diff}"
        },
        {
            "id": 2,
            "type": "descriptive",
            "question": "متن سوال تشریحی",
            "correct_answer": "پاسخ کامل سوال (فقط از متن)",
            "explanation": "توضیح تکمیلی (فقط از متن)",
            "difficulty": "{$diff}"
        }
    ]
}

فقط JSON برگردان، هیچ توضیح اضافی نده.
PROMPT;

    return $prompt;
}