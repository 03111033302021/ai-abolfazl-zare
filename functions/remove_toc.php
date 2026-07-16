<?php

function removeTOC($text)
{
    $lines = explode("\n", $text);

    $result = [];

    $insideTOC = false;

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line == '') {
            continue;
        }

        // شروع فهرست
        if (preg_match('/^(فهرست|فهرست مطالب|contents)$/iu', $line)) {
            $insideTOC = true;
            continue;
        }

        if ($insideTOC) {

            // خطوط معمول فهرست
            if (
                preg_match('/\.{3,}\s*\d+$/u', $line) ||              // ....... 12
                preg_match('/^فصل.+\.{3,}\s*\d+$/u', $line) ||                   // فصل اول
                preg_match('/^بخش.+\.{3,}\s*\d+$/u', $line) ||                   // بخش
                preg_match('/^درس.+\.{3,}\s*\d+$/u', $line) ||                   // درس
                preg_match('/^\d+([.-]\d+)*\s+/u', $line)            // 1.2 یا 1-2
            ) {
                continue;
            }

            // اگر به متن واقعی رسیدیم
            if (mb_strlen($line) > 80) {
                $insideTOC = false;
            }
        }

        $result[] = $line;
    }

    return implode("\n\n", $result);
}

?>