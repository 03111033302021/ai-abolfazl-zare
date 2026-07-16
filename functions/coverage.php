<?php

function calculateCoverage(array $chunks, array $sectionScores): array
{
    $coverage = [];
    $totalScore = 0;

    foreach ($sectionScores as $score) {
        if (is_array($score)) {
            $totalScore += array_sum($score);
        } else {
            $totalScore += $score;
        }
    }

    if ($totalScore == 0) {
        $count = count($chunks);
        if ($count > 0) {
            $equalShare = round(100 / $count, 2);
            foreach ($chunks as $index => $chunk) {
                $coverage["chunk_" . ($index + 1)] = $equalShare;
            }
        }
        return $coverage;
    }

    foreach ($sectionScores as $key => $score) {
        if (is_array($score)) {
            $score = array_sum($score);
        }
        $coverage[$key] = round(($score / $totalScore) * 100, 2);
    }

    return $coverage;
}

function distributeQuestions(int $totalQuestions, array $coverage): array
{
    if (empty($coverage) || $totalQuestions <= 0) {
        return [];
    }

    $chunkKeys = array_keys($coverage);
    $chunkCount = count($chunkKeys);

    // ===== وقتی سوال کمتر از chunk هست =====
    // فقط به مهم‌ترین chunk‌ها (بر اساس درصد) سوال بده
    if ($totalQuestions <= $chunkCount) {
        // مرتب‌سازی بر اساس درصد (نزولی)
        $sorted = $coverage;
        arsort($sorted);
        $topKeys = array_slice(array_keys($sorted), 0, $totalQuestions);

        $distribution = [];
        foreach ($topKeys as $key) {
            $distribution[$key] = 1;
        }
        return $distribution;
    }

    // ===== توزیع بر اساس درصد =====
    $distribution = [];
    $totalAllocated = 0;
    $floats = [];

    foreach ($chunkKeys as $key) {
        $exact = ($coverage[$key] / 100) * $totalQuestions;
        $floats[$key] = $exact;
        $distribution[$key] = (int) floor($exact);
        $totalAllocated += $distribution[$key];
    }

    // اطمینان از حداقل ۱ سوال به هر chunk
    foreach ($chunkKeys as $key) {
        if ($distribution[$key] < 1) {
            $distribution[$key] = 1;
            $totalAllocated++;
        }
    }

    // توزیع باقی‌مانده بر اساس بزرگترین کسر اعشاری
    $remaining = $totalQuestions - $totalAllocated;

    if ($remaining > 0) {
        // محاسبه کسر اعشاری برای هر chunk
        $fractions = [];
        foreach ($chunkKeys as $key) {
            $fractions[$key] = $floats[$key] - floor($floats[$key]);
        }
        arsort($fractions);

        $i = 0;
        $fractionKeys = array_keys($fractions);
        while ($remaining > 0) {
            $key = $fractionKeys[$i % count($fractionKeys)];
            $distribution[$key]++;
            $remaining--;
            $i++;
        }
    } elseif ($remaining < 0) {
        // اگه بیشتر تخصیص دادیم، از کوچک‌ترین chunk‌ها کم کن
        $extra = abs($remaining);
        asort($distribution);
        $keys = array_keys($distribution);
        $i = 0;
        while ($extra > 0) {
            $key = $keys[$i % count($keys)];
            if ($distribution[$key] > 1) {
                $distribution[$key]--;
                $extra--;
            }
            $i++;
        }
    }

    // تضمین مجموع صحیح
    $total = array_sum($distribution);
    if ($total !== $totalQuestions) {
        $diff = $totalQuestions - $total;
        arsort($distribution);
        $keys = array_keys($distribution);
        $i = 0;
        while ($diff != 0) {
            $key = $keys[$i % count($keys)];
            if ($diff > 0) {
                $distribution[$key]++;
                $diff--;
            } elseif ($distribution[$key] > 1) {
                $distribution[$key]--;
                $diff++;
            }
            $i++;
        }
    }

    // حذف صفرها
    return array_filter($distribution, fn($c) => $c > 0);
}

function selectSectionsForQuestions(array $chunks, array $sectionScores, int $totalQuestions): array
{
    if (empty($chunks)) {
        return [];
    }

    $coverage = calculateCoverage($chunks, $sectionScores);
    $distribution = distributeQuestions($totalQuestions, $coverage);

    if (empty($distribution)) {
        $distribution = [];
        foreach ($chunks as $index => $chunk) {
            $distribution["chunk_" . ($index + 1)] = 1;
        }
        $distribution = array_slice($distribution, 0, $totalQuestions, true);
    }

    $selectedSections = [];
    foreach ($distribution as $chunkKey => $count) {
        $index = (int) str_replace('chunk_', '', $chunkKey) - 1;
        if (isset($chunks[$index])) {
            $selectedSections[] = [
                'chunk'          => $chunks[$index],
                'chunk_key'      => $chunkKey,
                'question_count' => $count,
                'percentage'     => $coverage[$chunkKey] ?? 0
            ];
        }
    }

    return $selectedSections;
}
