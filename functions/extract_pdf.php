<?php

function extractPDF($pdfPath)
{
    $exe = 'C:\\poppler\\Library\\bin\\pdftotext.exe';

    $txtFile = tempnam(sys_get_temp_dir(), "pdf_") . ".txt";

    $command = '"' . $exe . '" -enc UTF-8 "' . $pdfPath . '" "' . $txtFile . '"';

    exec($command, $output, $status);

    if ($status !== 0 || !file_exists($txtFile)) {
        return false;
    }

    $text = file_get_contents($txtFile);

    unlink($txtFile);

    return trim($text);
}

?>