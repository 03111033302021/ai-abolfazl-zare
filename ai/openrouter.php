<?php

/**
 * -----------------------------------------
 * AI Exam Generator
 * ارتباط با OpenRouter API (DeepSeek)
 * -----------------------------------------
 */

function getApiKey(): string
{
    if (defined('OPENROUTER_API_KEY')) {
        return OPENROUTER_API_KEY;
    }

    $configFile = __DIR__ . '/../includes/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        if (defined('OPENROUTER_API_KEY')) {
            return OPENROUTER_API_KEY;
        }
    }

    if (isset($_SESSION['api_key']) && !empty($_SESSION['api_key'])) {
        return $_SESSION['api_key'];
    }

    return '';
}

function callOpenRouter(
    string $prompt,
    string $model = 'deepseek/deepseek-chat',
    float  $temperature = 0.7,
    int    $maxTokens = 4000
): string {

    // اطمینان از اینکه PHP کافی وقت داره
    $curlTimeout = 200;
    @set_time_limit($curlTimeout + 30);

    $apiKey = getApiKey();

    if (empty($apiKey)) {
        return "❌ خطا: کلید API تنظیم نشده است. لطفاً در فایل config.php کلید را وارد کنید.";
    }

    $data = [
        'model'    => $model,
        'messages' => [
            [
                'role'    => 'system',
                'content' => 'تو یک استاد دانشگاه هستی که سوالات آزمون استاندارد و حرفه‌ای طراحی می‌کنی. همیشه پاسخت را فقط به صورت JSON خالص برگردان بدون هیچ متن اضافی.'
            ],
            [
                'role'    => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => $temperature,
        'max_tokens'  => $maxTokens,
        'top_p'       => 0.9
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: http://localhost/AI-Exam-Generator/',
        'X-Title: AI Exam Generator'
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $curlTimeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_ENCODING, '');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($error) {
        // timeout جداگانه گزارش بده
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            return "❌ خطای Timeout: سرور در {$curlTimeout} ثانیه پاسخ نداد.";
        }
        return "❌ خطای CURL ({$errno}): " . $error;
    }

    if ($httpCode !== 200) {
        return "❌ خطای HTTP: " . $httpCode . "\n" . $response;
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }

    if (isset($result['error'])) {
        return "❌ خطای API: " . $result['error']['message'];
    }

    return "❌ خطای ناشناخته: " . $response;
}

/**
 * استخراج JSON از پاسخ LLM
 * مقاوم در برابر markdown، متن اضافی، و nested braces
 */
function extractJSONFromResponse(string $response): ?array
{
    // حذف markdown code blocks
    $response = preg_replace('/```json\s*/i', '', $response);
    $response = preg_replace('/```\s*/i', '', $response);
    $response = trim($response);

    // اگه مستقیم JSON باشه
    $direct = json_decode($response, true);
    if ($direct !== null && isset($direct['questions'])) {
        return $direct;
    }

    // پیدا کردن اولین { و آخرین } با balanced matching
    $start = strpos($response, '{');
    if ($start === false) {
        return null;
    }

    $depth   = 0;
    $inStr   = false;
    $escape  = false;
    $end     = -1;
    $len     = strlen($response);

    for ($i = $start; $i < $len; $i++) {
        $c = $response[$i];

        if ($escape) {
            $escape = false;
            continue;
        }

        if ($c === '\\' && $inStr) {
            $escape = true;
            continue;
        }

        if ($c === '"') {
            $inStr = !$inStr;
            continue;
        }

        if ($inStr) {
            continue;
        }

        if ($c === '{') {
            $depth++;
        } elseif ($c === '}') {
            $depth--;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
    }

    if ($end === -1) {
        return null;
    }

    $jsonStr = substr($response, $start, $end - $start + 1);
    $parsed  = json_decode($jsonStr, true);

    if ($parsed !== null) {
        return $parsed;
    }

    // تلاش آخر: پاک‌سازی کاراکترهای کنترلی
    $jsonStr = preg_replace('/[\x00-\x1F\x7F](?<!["\n\r\t])/u', '', $jsonStr);
    return json_decode($jsonStr, true);
}

function generateQuestionsWithAI(string $chunk, int $count = 10, string $type = 'multiple_choice', string $difficulty = 'medium'): array
{
    require_once __DIR__ . '/prompt.php';

    switch ($type) {
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
    $json     = extractJSONFromResponse($response);

    if ($json && isset($json['questions'])) {
        return $json['questions'];
    }

    return [
        'error'        => true,
        'message'      => 'پاسخ API معتبر نیست',
        'raw_response' => $response
    ];
}
