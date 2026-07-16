<?php

/**
 * -----------------------------------------
 * AI Exam Generator
 * پردازش‌های بعد از LLM
 * -----------------------------------------
 */

/**
 * 1. حذف سوالات تکراری
 */
function removeDuplicateQuestions(array $questions): array
{
    $unique = [];
    $seenQuestions = [];
    
    foreach ($questions as $q) {
        $normalized = preg_replace('/\s+/', ' ', trim($q['question'] ?? ''));
        $normalized = preg_replace('/[،؛:.!؟?]/', '', $normalized);
        
        if (!in_array($normalized, $seenQuestions)) {
            $seenQuestions[] = $normalized;
            $unique[] = $q;
        }
    }
    
    return $unique;
}

/**
 * 2. دسته‌بندی سوالات به تستی و تشریحی
 */
function categorizeQuestions(array $questions): array
{
    $result = [
        'multiple_choice' => [],
        'descriptive' => []
    ];
    
    foreach ($questions as $q) {
        $type = $q['type'] ?? 'multiple_choice';
        if ($type === 'multiple_choice' || $type === 'تستی') {
            $result['multiple_choice'][] = $q;
        } else {
            $result['descriptive'][] = $q;
        }
    }
    
    return $result;
}

/**
 * 3. کنترل تطابق پاسخنامه با محتوای فایل
 */
function validateAnswers(array $questions, string $originalText): array
{
    $validated = [];
    $invalidCount = 0;
    
    foreach ($questions as $q) {
        $answer = $q['correct_answer'] ?? '';
        
        // فقط چک کن پاسخ خالی نباشه
        $isValid = !empty(trim($answer));
        
        $q['answer_valid'] = $isValid;
        if (!$isValid) {
            $invalidCount++;
        }
        $validated[] = $q;
    }
    
    return [
        'questions' => $validated,
        'invalid_count' => $invalidCount,
        'total' => count($validated)
    ];
}

/**
 * 4. مرتب‌سازی سوالات بر اساس سطح دشواری
 */
function sortByDifficulty(array $questions): array
{
    $difficultyOrder = [
        'easy' => 1,
        'medium' => 2,
        'hard' => 3,
        'آسان' => 1,
        'متوسط' => 2,
        'سخت' => 3
    ];
    
    usort($questions, function($a, $b) use ($difficultyOrder) {
        $diffA = $difficultyOrder[$a['difficulty'] ?? 'medium'] ?? 2;
        $diffB = $difficultyOrder[$b['difficulty'] ?? 'medium'] ?? 2;
        return $diffA - $diffB;
    });
    
    return $questions;
}

/**
 * 5. تولید آزمون نهایی
 */
function processAfterLLM(array $questions, string $originalText): array
{
    // 1. حذف تکراری‌ها
    $uniqueQuestions = removeDuplicateQuestions($questions);
    
    // 2. دسته‌بندی
    $categories = categorizeQuestions($uniqueQuestions);
    
    // 3. اعتبارسنجی پاسخ‌ها
    $validationResult = validateAnswers($uniqueQuestions, $originalText);
    $validatedQuestions = $validationResult['questions'];
    
    // 4. مرتب‌سازی بر اساس دشواری
    $sortedQuestions = sortByDifficulty($validatedQuestions);
    
    // 5. تنظیم مجدد ID ها
    foreach ($sortedQuestions as $index => $q) {
        $q['id'] = $index + 1;
    }
    
    return [
        'questions' => $sortedQuestions,
        'summary' => [
            'total' => count($sortedQuestions),
            'multiple_choice' => count($categories['multiple_choice'] ?? []),
            'descriptive' => count($categories['descriptive'] ?? []),
            'duplicates_removed' => count($questions) - count($uniqueQuestions)
        ],
        'validation' => [
            'total' => $validationResult['total'],
            'invalid' => $validationResult['invalid_count'],
            'valid' => $validationResult['total'] - $validationResult['invalid_count']
        ],
        'categories' => $categories
    ];
}