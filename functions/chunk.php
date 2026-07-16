<?php

/**
 * -----------------------------------------
 * AI Exam Generator
 * Chunk Builder - V5 (ساده و مستقیم)
 * -----------------------------------------
 */

function makeChunks(string $text, int $maxLength = 100000): array
{
    $text = trim($text);
    if ($text == '') {
        return [];
    }

    // حذف کاراکترهای اضافی
    $text = str_replace(["\xE2\x80\xAB", "\xE2\x80\xAC", "\xE2\x80\xAA", "\xE2\x80\xAD", "\xE2\x80\xAE"], '', $text);
    
    
    $lines = preg_split('/\R/u', $text);

$chunks = [];
$current = [];

foreach ($lines as $line) {

    $line = trim($line);

    if ($line === '') {
        continue;
    }

    $isHeading = false;

    // فصل، بخش، درس، جلسه، مبحث ...
    if (preg_match('/^(فصل|بخش|درس|جلسه|مبحث|سرفصل|قسمت|واحد|آزمایش|تمرین|پیوست)\s+/u', $line)) {
        $isHeading = true;
    }

    // Chapter 1
    elseif (preg_match('/^(chapter|section|lesson|unit)\b/i', $line)) {
        $isHeading = true;
    }

    // 1
    // 1.1
    // 2-3
    elseif (
    preg_match('/^[۰-۹0-9]+([.][۰-۹0-9]+)*[.)-]\s+\S+/u', $line)
) {
    $isHeading = true;
}

    

    if ($isHeading && !empty($current)) {
        $chunks[] = implode("\n", $current);
        $current = [];
    }

    $current[] = $line;
}

if (!empty($current)) {
    $chunks[] = implode("\n", $current);
}

// اگر یک Chunk خیلی بزرگ بود، آن را خرد کن
$finalChunks = [];

foreach ($chunks as $chunk) {

    if (mb_strlen($chunk) > $maxLength) {
        $finalChunks = array_merge(
            $finalChunks,
            splitChapter($chunk, $maxLength)
        );
    } else {
        $finalChunks[] = $chunk;
    }
}

$chunks = $finalChunks;

// اگر هیچ Heading پیدا نشد
if (count($chunks) <= 1) {

    if (mb_strlen($text) <= $maxLength) {
        return [$text];
    }

    return splitByParagraph($text, $maxLength);
}

return $chunks;

}

/**
 * تقسیم یک فصل بزرگ به بخش‌های کوچیک‌تر
 */
function splitChapter(string $text, int $maxLength): array
{
    // اول سعی کن بر اساس تیترهای شماره‌دار تقسیم کنی (۱. , ۲. , ...)
    $parts = preg_split(
    '/(?=^[۰-۹0-9]+([.-][۰-۹0-9]+)*\s+\S+)/mu',
    $text,
    -1,
    PREG_SPLIT_NO_EMPTY
    );
    
    if (count($parts) > 1) {
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) > $maxLength) {
                // اگه بازم بزرگ بود، بر اساس پاراگراف تقسیم کن
                $result = array_merge($result, splitByParagraph($part, $maxLength));
            } else {
                $result[] = $part;
            }
        }
        return $result;
    }
    
    // اگه تیتر شماره‌دار نداشت، بر اساس پاراگراف تقسیم کن
    return splitByParagraph($text, $maxLength);
}

/**
 * تقسیم بر اساس پاراگراف
 */
function splitByParagraph(string $text, int $maxLength): array
{
    $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    if (count($paragraphs) <= 1) {
        // اگه فقط یک پاراگراف بود، سعی کن بین جمله‌ها برش بزنی
        return splitBySentence($text, $maxLength);
    }
    
    $result = [];
    $current = '';
    
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') continue;
        
        // اگه اضافه کردن این پاراگراف از maxLength بیشتر بشه
        if (mb_strlen($current . "\n\n" . $para) > $maxLength) {
            // اگه current خالی نیست، ذخیره کن
            if ($current !== '') {
                $result[] = trim($current);
            }
            // پاراگراف جدید رو شروع کن
            $current = $para;
        } else {
            // پاراگراف رو به current اضافه کن
            $current .= ($current ? "\n\n" : '') . $para;
        }
    }
    
    // آخرین بخش رو ذخیره کن
    if ($current !== '') {
        $result[] = trim($current);
    }
    
    return $result;
}

/**
 * تقسیم بر اساس جمله
 */
function splitBySentence(string $text, int $maxLength): array
{
    $sentences = preg_split('/(?<=[.!؟?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    if (count($sentences) <= 1) {
        return [$text];
    }
    
    $result = [];
    $current = '';
    
    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if ($sentence === '') continue;
        
        if (mb_strlen($current . ' ' . $sentence) <= $maxLength) {
            $current .= ($current ? ' ' : '') . $sentence;
        } else {
            if ($current !== '') {
                $result[] = $current;
            }
            $current = $sentence;
        }
    }
    
    if ($current !== '') {
        $result[] = $current;
    }

    // اگر هنوز خیلی بزرگ بود، برش اجباری
if (count($result) == 1 && mb_strlen($result[0]) > $maxLength) {

    return array_map(
        'trim',
        preg_split(
            '/(.{1,' . $maxLength . '})/us',
            $result[0],
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        )
    );
}
    
    return $result;
}

?>