<?php

function cleanText($text)
{
    // تبدیل CRLF به LF
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // حذف Tab
    $text = str_replace("\t", " ", $text);

    // حذف Form Feed
    $text = str_replace("\f", "", $text);

    // یکسان‌سازی حروف عربی و فارسی
    $text = str_replace("ي", "ی", $text);
    $text = str_replace("ك", "ک", $text);

    // حذف شماره صفحات (خطی که فقط عدد است)
    $text = preg_replace('/^\d+$/m', '', $text);

    // حذف فاصله‌های اضافی
    $text = preg_replace('/[ ]+/u', ' ', $text);

    // حذف فاصله قبل از علائم
    $text = preg_replace('/\s+([،؛:.!?])/u', '$1', $text);

    // حذف خطوط خالی متعدد
    $text = preg_replace("/\n{2,}/", "\n\n", $text);

    // حذف فاصله ابتدا و انتها
    return trim($text);
}

?>