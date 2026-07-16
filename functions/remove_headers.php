<?php

function removeHeaders($text)
{
    $lines = explode("\n", $text);

    $lineCount = [];

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '') {
            continue;
        }

        // فقط خطوط کوتاه را بررسی می‌کنیم
        if (mb_strlen($line) > 60) {
            continue;
        }

        if (!isset($lineCount[$line])) {
            $lineCount[$line] = 0;
        }

        $lineCount[$line]++;
    }

    $result = [];

    foreach ($lines as $line) {

        $trim = trim($line);

        // حذف شماره صفحه
        if (preg_match('/^\d+$/', $trim)) {
            continue;
        }

        // حذف خطوطی که زیاد تکرار شده‌اند
        if (
            isset($lineCount[$trim]) &&
            $lineCount[$trim] >= 3 &&
            mb_strlen($trim) <= 60
        ) {
            continue;
        }

        $result[] = $line;
    }

    return implode("\n", $result);
}

?>