<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../botapi.php';

// Set headers and configuration
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
$otherservice = select("topicid", "idreport", "report", "otherservice", "select")['idreport'];
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
$reportnight = select("topicid", "idreport", "report", "reportnight", "select")['idreport'];
$reporttest = select("topicid", "idreport", "report", "reporttest", "select")['idreport'];
$errorreport = select("topicid", "idreport", "report", "errorreport", "select")['idreport'];
$porsantreport = select("topicid", "idreport", "report", "porsantreport", "select")['idreport'];
$reportcron = select("topicid", "idreport", "report", "reportcron", "select")['idreport'];
$reportbackup = select("topicid", "idreport", "report", "backupfile", "select")['idreport'];
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

/**
 * Utility Functions
 */
function sendJsonResponse($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'msg' => $message,
        'obj' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendReport($text, $groupid, $topic_id, $reply_markup = null)
{
    if (strlen($groupid) > 0) {
        telegram('sendmessage', [
            'chat_id' => $groupid,
            'message_thread_id' => $topic_id,
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ]);
    }
}

function validateToken($headers)
{
    global $APIKEY;
    if (!isset($headers['Token'])) {
        return false;
    }
    if (is_file('hash.txt')) {
        $token = file_get_contents('hash.txt');
    } else {
        $token = "";
    }
    $validTokens = [$token, $APIKEY];
    return in_array($headers['Token'], $validTokens, true);
}

function sanitizeRecursive($data)
{
    if (is_array($data)) {
        return array_map('sanitizeRecursive', $data);
    }
    return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
}

function validateMethod($expected, $actual, $responseData = [], $httpCode = 200)
{
    if (strtoupper($expected) !== strtoupper($actual)) {
        sendJsonResponse(false, "method invalid; method must be {$expected}", $responseData, $httpCode);
    }
}

function logApiRequest($headers, $data, $action)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO logs_api (header, data, time, ip, actions) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            json_encode($headers),
            json_encode($data),
            date('Y/m/d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $action
        ]);
    } catch (Exception $e) {
        error_log("API logging error: " . $e->getMessage());
    }
}

/**
 * Main API Logic
 */

// Get and validate headers
$headers = getallheaders();
if (!validateToken($headers)) {
    sendJsonResponse(false, "token invalid", [], 403);
}

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Validate JSON data
if (!is_array($data)) {
    sendJsonResponse(false, "data invalid", []);
}

// Sanitize input data
$data = sanitizeRecursive($data);

// Log API request
logApiRequest($headers, $data, $data['actions'] ?? 'unknown');

// Get settings
$setting = select("setting", "*");

// Route based on action
switch ($data['actions'] ?? '') {

    case 'users':
        validateMethod('GET', $method, [], 403);

        // Validate and set limit
        $limit = 50;
        if (isset($data['limit']) && is_numeric($data['limit'])) {
            $limit = min(max((int) $data['limit'], 1), 1000);
        }

        // Validate and set page
        $page = isset($data['page']) && is_numeric($data['page']) ? max((int) $data['page'], 1) : 1;
        $offset = ($page - 1) * $limit;
        $q = isset($data['q']) ? $data['q'] : '';
        $agent_type = isset($data['agent']) ? " AND agent = '{$data['agent']}'" : '';

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user WHERE (id LIKE :id_user OR username LIKE :username) $agent_type");
            $search = "%$q%";
            $stmt->bindParam(':id_user', $search, PDO::PARAM_STR);
            $stmt->bindParam(':username', $search, PDO::PARAM_STR);
            $stmt->execute();
            $totalUsers = (int) $stmt->fetchColumn();
            $totalPages = ceil($totalUsers / $limit);
            $query = "SELECT id as user_id,username,limit_usertest,roll_Status,number,Balance,User_Status,agent,affiliatescount,affiliates,cardpayment,register as time_join,verify,pricediscount,last_message_time,limit_usertest,score,joinchannel,status_cron,expire,maxbuyagent FROM user WHERE (id  LIKE CONCAT('%', :user_id, '%') OR username  LIKE CONCAT('%', :username, '%')) $agent_type ORDER BY register DESC,Balance DESC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':username', $q, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $q, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendJsonResponse(true, "Successful", [
                'users' => $users,
                'pagination' => [
                    'total_users' => $totalUsers,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);

        } catch (Exception $e) {
            error_log("Database error in users: " . $e->getMessage());
            sendJsonResponse(false, "Database error occurred", [], 500);
        }
        break;

    case 'user':
        validateMethod('GET', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "chat_id empty", []);
        }

        try {
            $stmt = $pdo->prepare("SELECT id as user_id,username,limit_usertest,roll_Status,number,Balance,User_Status,agent,affiliatescount,affiliates,cardpayment,register as time_join,verify,pricediscount,last_message_time,limit_usertest,score,joinchannel,status_cron,expire,maxbuyagent,limitchangeloc,description_blocking FROM user WHERE id = :user_id");
            $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($users)) {
                sendJsonResponse(true, "Successful", [

                    'users' => [],
                    'pagination' => [
                        'total_users' => 1,
                        'total_pages' => 1,
                        'current_page' => 1,
                        'per_page' => 10
                    ]
                ]);
            }
            $stmt = $pdo->prepare("SELECT SUM(price_product) as sum_price,COUNT(username) as count_invoice FROM invoice WHERE name_product != 'سرویس تست' AND  id_user = :user_id AND Status != 'Unpaid'");
            $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
            $stmt->execute();
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            $users[0]['count_invoice'] = $invoice['count_invoice'];
            $users[0]['sum_invoice'] = $invoice['sum_price'];
            $stmt = $pdo->prepare("SELECT SUM(price) as sum_price,COUNT(*) as count_payment FROM Payment_report WHERE id_user = :user_id AND Payment_Method not in ('Unpaid','reject','expire')");
            $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
            $stmt->execute();
            $payment_report = $stmt->fetch(PDO::FETCH_ASSOC);
            $users[0]['count_payment'] = $payment_report['count_payment'];
            $users[0]['sum_payment'] = $payment_report['sum_price'];
            $stmt = $pdo->prepare("SELECT SUM(price) as sum_price,COUNT(*) as count_service FROM service_other WHERE id_user = :user_id AND (status = 'paid' OR status IS NULL)");
            $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
            $stmt->execute();
            $service_report = $stmt->fetch(PDO::FETCH_ASSOC);
            $users[0]['count_service'] = $service_report['count_service'];
            $users[0]['sum_service'] = $service_report['sum_price'];
            $bot_agent = select("botsaz", "*", "id_user", $data['chat_id'], "select");
            $list_panel = [];
            if ($bot_agent) {
                $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active'");
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $list_panel[] = $row['name_panel'];
                }
            }
            $users[0]['agent_bot'] = $bot_agent;
            $users[0]['panels'] = $list_panel;
            $panel = select("marzban_panel", "code_panel,name_panel", null, null, "fetchAll");
            $product = select("product", "code_product,name_product", null, null, "fetchAll");
            sendJsonResponse(true, "Successful", [

                'users' => $users,
                'panel' => $panel,
                'product' => $product,
                'pagination' => [
                    'total_users' => 1,
                    'total_pages' => 1,
                    'current_page' => 1,
                    'per_page' => 10
                ]
            ]);
        } catch (Exception $e) {
            error_log("Database error in user: " . $e->getMessage());
            sendJsonResponse(false, "Database error occurred", [], 500);
        }
        break;

    case 'user_add':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }

        // Get user info from Telegram
        try {
            $userInfo = telegram('getChat', ['chat_id' => $data['chat_id']]);

            if (!$userInfo['ok']) {
                sendJsonResponse(false, $userInfo['description'] ?? 'Telegram API error');
            }

            // Generate random invitation code
            $randomString = bin2hex(random_bytes(6));
            $currentTime = time();

            // Set verification status
            $verifyValue = ($setting['verifystart'] === "onverify") ? 0 : 1;

            // Prepare user data
            $userData = [
                'id' => $data['chat_id'],
                'step' => 'none',
                'limit_usertest' => $setting['limit_usertest_all'],
                'User_Status' => 'Active',
                'number' => 'none',
                'Balance' => '0',
                'pagenumber' => '1',
                'username' => $userInfo['result']['username'] ?? 'none',
                'agent' => 'f',
                'message_count' => '0',
                'last_message_time' => '0',
                'affiliates' => '0',
                'affiliatescount' => '0',
                'cardpayment' => $setting['showcard'],
                'number_username' => '100',
                'namecustom' => 'none',
                'register' => $currentTime,
                'verify' => $verifyValue,
                'codeInvitation' => $randomString,
                'pricediscount' => '0',
                'maxbuyagent' => '0',
                'joinchannel' => '0',
                'score' => '0'
            ];

            // Insert user into database
            $columns = implode(',', array_keys($userData));
            $placeholders = ':' . implode(', :', array_keys($userData));

            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO user ({$columns}) VALUES ({$placeholders})"
            );

            foreach ($userData as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            sendJsonResponse(true, "Successful");

        } catch (Exception $e) {
            error_log("Error in user_add: " . $e->getMessage());
            sendJsonResponse(false, "An error occurred while adding user");
        }
        break;
    case 'block_user':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (empty($data['description'])) {
            sendJsonResponse(false, "description empty", [], 200);
        }
        if ($data['type_block'] == "block") {
            $typeblock = "block";
            $text_report = "کاربر با آیدی عددی {$data['chat_id']} در ربات  مسدود گردید 
ادمین انجام دهنده : api site";
        } else {
            $text_report = "کاربر با آیدی عددی {$data['chat_id']} در ربات از مسدودیت خارج گردید 
ادمین انجام دهنده : api site";
            sendmessage($data['chat_id'], "✳️ حساب کاربری شما از مسدودی خارج شد ✳️
اکنون میتوانید از ربات استفاده کنید ✔️", null, 'HTML');
            $typeblock = "Active";
        }
        update("user", "description_blocking", $data['description'], "id", $data['chat_id']);
        update("user", "User_Status", $typeblock, "id", $data['chat_id']);
        sendReport($text_report, $setting['Channel_Report'], $otherservice, null);
        sendJsonResponse(true, "Successful");
        break;
    case 'verify_user':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        if ($data['type_verify'] == "1") {
            $type_verify = "0";
        } else {
            $type_verify = "1";
            sendmessage($data['chat_id'], "💎 کاربر گرامی حساب کاربری شما با موفقیت احراز هویت گردید و هم اکنون می توانیدخرید خود را انجام دهید", null, 'HTML');
        }
        update("user", "verify", $type_verify, "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'change_status_user':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        $checkexits = select("user", "*", "id", $data['chat_id'], "select");
        if (intval(value: $checkexits['checkstatus']) != 0) {
            sendJsonResponse(false, "actions exits", [], 200);
        }
        if ($data['type'] == "active") {
            update("user", "checkstatus", "1", "id", $data['chat_id']);
        } else {
            update("user", "checkstatus", "2", "id", $data['chat_id']);
        }
        sendJsonResponse(true, "Successful");
        break;
    case 'add_balance':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['amount']) || empty($data['amount'])) {
            sendJsonResponse(false, "amount empty", [], 200);
        }
        $stmt = $pdo->prepare("UPDATE user SET Balance = Balance + :amount WHERE id = :user_id");
        $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
        $stmt->bindValue(':amount', intval($data['amount']), PDO::PARAM_INT);
        $stmt->execute();
        $text_balance = "💎 کاربر عزیز مبلغ {$data['amount']} تومان به موجودی کیف پول تان اضافه گردید.";
        sendmessage($data['chat_id'], $text_balance, null, 'html');
        sendJsonResponse(true, "Successful");
        break;
    case 'withdrawal':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['amount']) || empty($data['amount'])) {
            sendJsonResponse(false, "amount empty", [], 200);
        }
        $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :amount WHERE id = :user_id");
        $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
        $stmt->bindValue(':amount', intval($data['amount']), PDO::PARAM_INT);
        $stmt->execute();
        $text_balance = "❌ کاربر عزیز مبلغ {$data['amount']} تومان از  موجودی کیف پول تان کسر گردید.";
        sendmessage($data['chat_id'], $text_balance, null, 'html');
        sendJsonResponse(true, "Successful");
        break;
    case 'accept_number':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        update("user", "number", "confrim number by admin", "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'send_message':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['text']) || empty($data['text'])) {
            sendJsonResponse(false, "text empty", [], 200);
        }
        if ($data['file'] == null) {
            sendmessage($data['chat_id'], $data['text'], null, 'html');
        } else {
            if (!isset($data['content_type']) || empty($data['content_type'])) {
                sendJsonResponse(false, "content_type empty", [], 200);
            }
            $data['content_type'] = explode('/', $data['content_type'])[0];
            if ($data['content_type'] == "image") {
                file_put_contents("file.jpg", base64_decode($data['file']));
                sendphoto($data['chat_id'], new CURLFile("file.jpg"), $data['text']);
                unlink("file.jpg");
            } elseif ($data['content_type'] == "video") {
                file_put_contents("file.mp4", base64_decode($data['file']));
                sendvideo($data['chat_id'], new CURLFile("file.mp4"), $data['text']);
                unlink('file.mp4');
            } elseif ($data['content_type'] == "application") {
                file_put_contents("file.pdf", base64_decode($data['file']));
                var_dump(sendDocument($data['chat_id'], "file.pdf", $data['text']));
                unlink("file.pdf");
            } elseif ($data['content_type'] == "audio") {
                $file_name = $data[1];
                file_put_contents("file." . $file_name, base64_decode($data['file']));
                telegram('sendAudio', [
                    'chat_id' => $data['chat_id'],
                    'audio' => new CURLFile("file." . $file_name),
                    'caption' => $data['text'],
                ]);
                unlink("file." . $file_name);
            } else {
                sendJsonResponse(false, "content_type invalid", [], 200);
            }
        }
        sendJsonResponse(true, "Successful");
        break;
    case 'set_limit_test':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['limit_test']) || empty($data['limit_test'])) {
            sendJsonResponse(false, "limit_test empty", [], 200);
        }
        $stmt = $pdo->prepare("UPDATE user SET limit_usertest =  :limit_test WHERE id = :user_id");
        $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
        $stmt->bindValue(':limit_test', intval($data['limit_test']), PDO::PARAM_INT);
        $stmt->execute();
        sendJsonResponse(true, "Successful");
        break;
    case 'transfer_account':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!isset($data['new_userid']) || empty($data['new_userid']))
            sendJsonResponse(false, "new_userid empty", [], 200);
        if ($data["chat_id"] == $data["new_userid"])
            sendJsonResponse(false, "inavlid user_id", [], 200);
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = :id_user");
        $stmt->bindParam(':id_user', $data["new_userid"], PDO::PARAM_STR);
        $stmt->execute();
        update("user", "id", $data["new_userid"], "id", $data['chat_id']);
        update("Payment_report", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        update("invoice", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        update("support_message", "iduser", $data["new_userid"], "iduser", $data['chat_id']);
        update("service_other", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        update("Giftcodeconsumed", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        update("botsaz", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        update("service_other", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'join_channel_exception':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        update("user", "joinchannel", "active", "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'cron_notif':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        if ($data['type'] == "1") {
            $type = "0";
        } else {
            $type = "1";
        }
        update("user", "status_cron", $type, "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'manage_show_cart':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        if ($data['type'] == "1") {
            $type = "0";
        } else {
            $type = "1";
        }
        update("user", "cardpayment", $type, "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'zero_balance':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        update("user", "Balance", 0, "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'affiliates_users':
        validateMethod('GET', $method, [], 403);

        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }

        try {
            $stmt = $pdo->prepare("SELECT id as user_id FROM user WHERE affiliates = :affiliates_id");
            $stmt->bindValue(':affiliates_id', $data['chat_id']);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendJsonResponse(true, "Successful", [
                'users' => $users
            ]);

        } catch (Exception $e) {
            error_log("Database error in users: " . $e->getMessage());
            sendJsonResponse(false, "Database error occurred", [], 500);
        }
        break;
    case 'remove_affiliates':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        update("user", "affiliates", "0", "affiliates", $data['chat_id']);
        update("user", "affiliatescount", "0", "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'remove_affiliate_user':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        update("user", "affiliates", "0", "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'set_agent':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 500);
        }
        update("user", "agent", $data['agent_type'], "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'set_expire_agent':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['expire_time'])) {
            sendJsonResponse(false, "expire_time empty", [], 200);
        }
        if ($data['expire_time'] != 0) {
            $timestamp = time() + (intval($data['expire_time']) * 86400);
        } else {
            $timestamp = null;
        }
        update("user", "expire", $timestamp, "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'set_becoming_negative':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['amount'])) {
            sendJsonResponse(false, "amount empty", [], 200);
        }
        update("user", "maxbuyagent", $data['amount'], "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case 'set_percentage_discount':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id'])) {
            sendJsonResponse(false, "user-id empty", [], 200);
        }
        if (!isset($data['percentage'])) {
            sendJsonResponse(false, "percentage empty", [], 200);
        }
        update("user", "pricediscount", $data['percentage'], "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;

    case 'active_bot_agent':
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!isset($data['token']) || empty($data['token']))
            sendJsonResponse(false, "token empty", [], 200);
        $chec_kbot = select("botsaz", "*", "id_user", $data['chat_id'], "count");
        $check_bots = select("botsaz", "*", null, null, "count");
        if ($checkbots >= 15)
            sendJsonResponse(false, "You are allowed to create 15 representative bots in your bot.");
        if ($chec_kbot != 0)
            sendJsonResponse(false, "You are allowed to build a robot.");
        $getInfoToken = json_decode(file_get_contents("https://api.telegram.org/bot{$data['token']}/getme"), true);
        if ($getInfoToken == false or !$getInfoToken['ok'])
            sendJsonResponse(false, "You are allowed! Token inavlid");
        $check_exits_token = select("botsaz", "*", "bot_token", $data['token'], "count");
        if ($check_exits_token != 0)
            sendJsonResponse(false, "You are allowed! Token exits");
        $admin_ids = json_encode(array(
            $data['chat_id']
        ));
        $destination = dirname(getcwd());
        $dirsource = "$destination/vpnbot/{$data['chat_id']}{$getInfoToken['result']['username']}";
        if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
            error_log('Failed to remove existing bot directory: ' . $dirsource);
        }
        if (!copyDirectoryContents($destination . '/vpnbot/Default', $dirsource)) {
            error_log('Failed to copy default bot files into: ' . $dirsource);
        }
        $contentconfig = file_get_contents($dirsource . "/config.php");
        $new_code = str_replace('BotTokenNew', $data['token'], $contentconfig);
        file_put_contents($dirsource . "/config.php", $new_code);
        file_get_contents("https://api.telegram.org/bot{$data['token']}/setwebhook?url=https://$domainhosts/vpnbot/{$data['chat_id']}{$getInfoToken['result']['username']}/index.php");
        file_get_contents("https://api.telegram.org/bot{$data['token']}/sendmessage?chat_id={$data['chat_id']}&text=✅ کاربر عزیز ربات شما با موفقیت نصب گردید.");
        $datasetting = json_encode(array(
            "minpricetime" => 4000,
            "pricetime" => 4000,
            "minpricevolume" => 4000,
            "pricevolume" => 4000,
            "support_username" => "@support",
            "Channel_Report" => 0,
            "cart_info" => "جهت پرداخت مبلغ را به شماره کارت زیر واریز نمایید",
            'show_product' => true,
        ));
        $value = "{}";
        $stmt = $pdo->prepare("INSERT INTO botsaz (id_user,bot_token,admin_ids,username,time,setting,hide_panel) VALUES (:id_user,:bot_token,:admin_ids,:username,:time,:setting,:hide_panel)");
        $stmt->bindParam(':id_user', $data['chat_id'], PDO::PARAM_STR);
        $stmt->bindParam(':bot_token', $data['token'], PDO::PARAM_STR);
        $stmt->bindParam(':admin_ids', $admin_ids);
        $stmt->bindParam(':username', $getInfoToken['result']['username'], PDO::PARAM_STR);
        $stmt->bindParam(':time', date('Y/m/d H:i:s'), PDO::PARAM_STR);
        $stmt->bindParam(':setting', $datasetting, PDO::PARAM_STR);
        $stmt->bindParam(':hide_panel', $value, PDO::PARAM_STR);
        $stmt->execute();
        sendJsonResponse(true, "Successful");
        break;
    case "remove_agent_bot":
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        $contentbot = select("botsaz", "*", "id_user", $data['chat_id'], "select");
        if (!$contentbot)
            sendJsonResponse(false, "User does not have an active bot.", [], 200);
        $destination = dirname(getcwd());
        $dirsource = "$destination/vpnbot/{$data['chat_id']}{$contentbot['username']}";
        if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
            error_log('Failed to remove bot directory: ' . $dirsource);
        }
        if (!empty($contentbot['bot_token'])) {
            file_get_contents("https://api.telegram.org/bot{$contentbot['bot_token']}/deletewebhook");
        }
        $stmt = $pdo->prepare("DELETE FROM botsaz WHERE id_user = :id_user");
        $stmt->bindParam(':id_user', $data['chat_id'], PDO::PARAM_STR);
        $stmt->execute();
        sendJsonResponse(true, "Successful");
        break;
    case "set_price_volume_agent_bot":
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!isset($data['amount']) || empty($data['amount']))
            sendJsonResponse(false, "user-id empty", [], 200);
        $bot_info = json_decode(select("botsaz", "setting", "id_user", $data['chat_id'], "select")['setting'], true);
        $bot_info['minpricevolume'] = $data['amount'];
        update("botsaz", "setting", json_encode($bot_info), "id_user", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case "set_price_time_agent_bot":
        validateMethod('POST', $method);

        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!isset($data['amount']) || empty($data['amount']))
            sendJsonResponse(false, "user-id empty", [], 200);
        $bot_info = json_decode(select("botsaz", "setting", "id_user", $data['chat_id'], "select")['setting'], true);
        $bot_info['minpricetime'] = $data['amount'];
        update("botsaz", "setting", json_encode($bot_info), "id_user", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case "SetPanelAgentShow":
        validateMethod('POST', $method);
        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!is_array($data['panels']))
            sendJsonResponse(false, "json invalid", [], 200);
        update("botsaz", "hide_panel", json_encode($data['panels']), "id_user", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    case "SetLimitChangeLocation":
        validateMethod('POST', $method);
        // Validate chat_id
        if (!isset($data['chat_id']) || empty($data['chat_id']))
            sendJsonResponse(false, "user-id empty", [], 200);
        if (!isset($data['Limit']))
            sendJsonResponse(false, "Limit empty", [], 200);
        update("user", "limitchangeloc", $data['Limit'], "id", $data['chat_id']);
        sendJsonResponse(true, "Successful");
        break;
    default:
        sendJsonResponse(false, "Action Invalid");
        break;
}

?>
