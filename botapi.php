<?php
require_once __DIR__ . '/config.php';
function telegram($method, $datas = [], $token = null)
{
    global $APIKEY, $telegramCurlTimeout;

    $token = $token === null ? $APIKEY : $token;
    $url = "https://api.telegram.org/bot" . $token . "/" . $method;

    if (!is_array($datas)) {
        $datas = [];
    }

    if (isset($datas['message_thread_id']) && intval($datas['message_thread_id']) <= 0) {
        unset($datas['message_thread_id']);
    }

    $preparedPayload = prepareTelegramRequestPayload($datas);

    $ch = curl_init($url);
    if ($ch === false) {
        error_log('Unable to initialise cURL for Telegram request.');
        return [
            'ok' => false,
            'description' => 'Unable to initialise cURL for Telegram request.'
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $timeout = isset($telegramCurlTimeout) && is_numeric($telegramCurlTimeout) ? (int)$telegramCurlTimeout : 10;
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    if (!empty($preparedPayload['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $preparedPayload['headers']);
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $preparedPayload['body']);

    $requestStartedAt = microtime(true);
    $rawResponse = curl_exec($ch);
    if ($rawResponse === false) {
        $curlErrorNumber = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $logError = $curlError !== '' ? $curlError : 'Unknown cURL error';
        error_log(sprintf('Telegram request failed (errno: %d, url: %s): %s', $curlErrorNumber, $url, $logError));

        return [
            'ok' => false,
            'description' => ($curlError !== '' ? $curlError : 'Telegram request failed.') . ' اتصال به تلگرام در مهلت مقرر برقرار نشد؛ فایروال یا پراکسی خروجی را بررسی کنید.'
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = microtime(true) - $requestStartedAt;
    curl_close($ch);

    if ($duration >= 1.5) {
        error_log(sprintf('Slow Telegram response detected (method: %s, http_code: %d, duration: %.3fs)', $method, $httpCode, $duration));
    }

    $decodedResponse = json_decode($rawResponse, true);
    if (!is_array($decodedResponse)) {
        $logSnippet = substr($rawResponse, 0, 200);
        error_log(sprintf('Invalid response from Telegram API (HTTP %d): %s', $httpCode, $logSnippet));

        return [
            'ok' => false,
            'error_code' => $httpCode,
            'description' => 'Invalid response received from Telegram.'
        ];
    }

    if (isset($decodedResponse['ok']) && !$decodedResponse['ok']) {
        error_log(json_encode($decodedResponse));
    }

    return $decodedResponse;
}
function prepareTelegramRequestPayload(array $datas)
{
    $normalised = [];
    foreach ($datas as $key => $value) {
        if ($value === null) {
            continue;
        }

        $normalised[$key] = normaliseTelegramValue($value);
    }

    $containsFile = false;
    foreach ($normalised as $value) {
        if ($value instanceof CURLFile) {
            $containsFile = true;
            break;
        }
    }

    if ($containsFile) {
        return [
            'body' => $normalised,
            'headers' => ['Expect:']
        ];
    }

    $stringBody = http_build_query($normalised, '', '&', PHP_QUERY_RFC3986);

    return [
        'body' => $stringBody,
        'headers' => ['Expect:', 'Content-Type: application/x-www-form-urlencoded']
    ];
}

function normaliseTelegramValue($value)
{
    if ($value instanceof CURLFile) {
        return $value;
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value) || is_object($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = json_encode($value);
        }

        return $encoded === false ? '' : $encoded;
    }

    return $value;
}
function sendmessage($chat_id,$text,$keyboard,$parse_mode,$bot_token = null){
    if(intval($chat_id) == 0)return ['ok' => false];
    return telegram('sendmessage',[
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,
        
        ],$bot_token);
}
function prepareTelegramInputFile($input)
{
    if ($input instanceof CURLFile) {
        return $input;
    }

    if (is_string($input)) {
        if (preg_match('/^https?:\/\//i', $input)) {
            return $input;
        }

        $realPath = realpath($input);
        if ($realPath !== false && is_file($realPath) && is_readable($realPath)) {
            return new CURLFile($realPath);
        }

        error_log(sprintf('Telegram document path is not readable: %s', $input));
        return null;
    }

    error_log('Unsupported Telegram input file type: ' . gettype($input));
    return null;
}

function sendDocument($chat_id, $documentPath, $caption)
{
    $document = prepareTelegramInputFile($documentPath);
    if ($document === null) {
        return [
            'ok' => false,
            'description' => 'Document could not be prepared for Telegram upload.'
        ];
    }

    return telegram('sendDocument', [
        'chat_id' => $chat_id,
        'document' => $document,
        'caption' => $caption,
    ]);
}

function forwardMessage($chat_id,$message_id,$chat_id_user){
    return telegram('forwardMessage',[
        'from_chat_id'=> $chat_id,
        'message_id'=> $message_id,
        'chat_id'=> $chat_id_user,
    ]);
}
function sendphoto($chat_id,$photoid,$caption){
    telegram('sendphoto',[
        'chat_id' => $chat_id,
        'photo'=> $photoid,
        'caption'=> $caption,
    ]);
}
function sendvideo($chat_id,$videoid,$caption){
    telegram('sendvideo',[
        'chat_id' => $chat_id,
        'video'=> $videoid,
        'caption'=> $caption,
    ]);
}
function senddocumentsid($chat_id,$documentid,$caption){
    telegram('sendDocument',[
        'chat_id' => $chat_id,
        'document'=> $documentid,
        'caption'=> $caption,
    ]);
}
function Editmessagetext($chat_id, $message_id, $text, $keyboard,$parse_mode = 'HTML'){
    return telegram('editmessagetext', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,

    ]);
}
 function deletemessage($chat_id, $message_id){
  telegram('deletemessage', [
'chat_id' => $chat_id, 
'message_id' => $message_id,
]);
 }
function getFileddire($photoid){
  return telegram('getFile', [
'file_id' => $photoid, 
]);
 }
function pinmessage($from_id,$message_id){
  return telegram('pinChatMessage', [
'chat_id' => $from_id, 
'message_id' => $message_id, 
]);
 }
 function unpinmessage($from_id){
  return telegram('unpinAllChatMessages', [
'chat_id' => $from_id, 
]);
 }
  function answerInlineQuery($inline_query_id,$results){
  return telegram('answerInlineQuery', [
      "inline_query_id" => $inline_query_id,
        "results" => json_encode($results)
]);
 }
function convertPersianNumbersToEnglish($string) {
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return str_replace($persian_numbers, $english_numbers, $string);
}

function isDuplicateUpdate($updateId)
{
    static $memoryCache = [];

    if (!is_numeric($updateId) || $updateId <= 0) {
        return false;
    }

    $now = time();
    $timeToLive = 120; // seconds

    foreach ($memoryCache as $id => $timestamp) {
        if (!is_numeric($timestamp) || ($now - (int)$timestamp) > $timeToLive) {
            unset($memoryCache[$id]);
        }
    }

    if (isset($memoryCache[$updateId])) {
        return true;
    }

    $isDuplicate = false;

    $cacheDir = __DIR__ . '/storage/cache';
    $cacheDirReady = true;

    if (!is_dir($cacheDir)) {
        $cacheDirReady = @mkdir($cacheDir, 0775, true) || is_dir($cacheDir);
    }

    if ($cacheDirReady) {
        $cacheFile = $cacheDir . '/recent_updates.json';
        $handle = @fopen($cacheFile, 'c+');

        if ($handle !== false) {
            try {
                if (flock($handle, LOCK_EX)) {
                    rewind($handle);
                    $contents = stream_get_contents($handle);
                    $recentUpdates = $contents ? json_decode($contents, true) : [];
                    if (!is_array($recentUpdates)) {
                        $recentUpdates = [];
                    }

                    foreach ($recentUpdates as $id => $timestamp) {
                        if (!is_numeric($timestamp) || ($now - (int)$timestamp) > $timeToLive) {
                            unset($recentUpdates[$id]);
                        }
                    }

                    if (array_key_exists((string) $updateId, $recentUpdates) || array_key_exists($updateId, $recentUpdates)) {
                        $isDuplicate = true;
                    } else {
                        $recentUpdates[(string) $updateId] = $now;

                        if (count($recentUpdates) > 200) {
                            asort($recentUpdates);
                            $recentUpdates = array_slice($recentUpdates, -200, null, true);
                        }

                        $encoded = json_encode($recentUpdates);
                        if ($encoded !== false) {
                            rewind($handle);
                            ftruncate($handle, 0);
                            fwrite($handle, $encoded);
                            fflush($handle);
                        }
                    }

                    flock($handle, LOCK_UN);
                }
            } catch (Throwable $e) {
                try {
                    flock($handle, LOCK_UN);
                } catch (Throwable $ignored) {
                }
            }

            fclose($handle);
        }
    }

    if (!$isDuplicate) {
        $pdo = $GLOBALS['pdo'] ?? null;
        if ($pdo instanceof PDO) {
            static $dbInitialised = false;
            static $lastCleanup = 0;

            try {
                if (!$dbInitialised) {
                    $pdo->exec('CREATE TABLE IF NOT EXISTS processed_updates (
                        update_id BIGINT UNSIGNED PRIMARY KEY,
                        processed_at INT UNSIGNED NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                    $dbInitialised = true;
                }

                if ($lastCleanup === 0 || ($now - $lastCleanup) > 60) {
                    $stmtCleanup = $pdo->prepare('DELETE FROM processed_updates WHERE processed_at < :threshold');
                    $stmtCleanup->execute([':threshold' => $now - $timeToLive]);
                    $lastCleanup = $now;
                }

                $stmtInsert = $pdo->prepare('INSERT IGNORE INTO processed_updates (update_id, processed_at) VALUES (:id, :ts)');
                $stmtInsert->execute([':id' => $updateId, ':ts' => $now]);
                if ($stmtInsert->rowCount() === 0) {
                    $isDuplicate = true;
                }
            } catch (Throwable $e) {
                error_log('Duplicate update tracker database fallback error: ' . $e->getMessage());
            }
        }
    }

    if (!$isDuplicate) {
        $memoryCache[$updateId] = $now;
    }

    return $isDuplicate;
}
// #-----------------------------#
$update = json_decode(file_get_contents("php://input"), true);
$update_id = $update['update_id'] ?? 0;
if (isDuplicateUpdate($update_id)) {
    http_response_code(200);
    exit;
}
$from_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? $update["inline_query"]['from']['id'] ?? 0;
$time_message = $update['message']['date'] ?? $update['callback_query']['date'] ?? $update["inline_query"]['date'] ?? 0;
$is_bot = $update['message']['from']['is_bot'] ?? false;
$chat_member = $update['chat_member'] ?? null;
$language_code = strtolower($update['message']['from']['language_code'] ?? $update['callback_query']['from']['language_code'] ?? "fa");
$Chat_type = $update["message"]["chat"]["type"] ?? $update['callback_query']['message']['chat']['type'] ?? '';
$text = $update["message"]["text"]  ?? '';
if(isset($update['pre_checkout_query'])){
    $Chat_type = "private";
    $from_id = $update['pre_checkout_query']['from']['id'];
}
$text =convertPersianNumbersToEnglish($text);
$text_inline = $update["callback_query"]["message"]['text'] ?? '';
$message_id = $update["message"]["message_id"] ?? $update["callback_query"]["message"]["message_id"] ?? 0;
$time_message = $update["message"]["date"] ?? $update["callback_query"]["date"] ?? 0;
$photo = $update["message"]["photo"] ?? 0;
$document = $update["message"]["document"] ?? 0;
$fileid = $update["message"]["document"]["file_id"] ?? 0;
$photoid = $photo ? end($photo)["file_id"] : '';
$caption = $update["message"]["caption"] ?? '';
$video = $update["message"]["video"] ?? 0;
$videoid = $video ? $video["file_id"] : 0;
$forward_from_id = $update["message"]["reply_to_message"]["forward_from"]["id"] ?? 0;
$datain = $update["callback_query"]["data"] ?? '';
$last_name = $update['message']['from']['last_name']  ?? $update["callback_query"]["from"]["last_name"] ?? $update["inline_query"]['from']['last_name'] ?? '';
$first_name = $update['message']['from']['first_name']  ?? $update["callback_query"]["from"]["first_name"] ?? $update["inline_query"]['from']['first_name'] ?? '';
$username = $update['message']['from']['username'] ?? $update['callback_query']['from']['username'] ?? $update["callback_query"]["from"]["username"] ?? 'NOT_USERNAME';
$user_phone =$update["message"]["contact"]["phone_number"] ?? 0;
$contact_id = $update["message"]["contact"]["user_id"] ?? 0;
$callback_query_id = $update["callback_query"]["id"] ?? 0;
$inline_query_id = $update["inline_query"]["id"] ?? 0;
$query = $update["inline_query"]["query"] ?? 0;