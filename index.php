<?php
$version = file_get_contents('version');
date_default_timezone_set('Asia/Tehran');
$new_marzban = isset($new_marzban) ? $new_marzban : false;
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
ini_set('memory_limit', '-1');
require_once 'config.php';
require_once 'botapi.php';
require_once 'jdf.php';
require_once 'function.php';
require_once 'keyboard.php';
require_once 'vendor/autoload.php';
require_once 'panels.php';
$textbotlang = languagechange('text.json');
if ($is_bot)
    return;
if (isset($update['chat_member'])) {
    $status = $update['chat_member']['new_chat_member']['status'];
    $from_id = $update['chat_member']['new_chat_member']['user']['id'];
    $user = select("user", "id", $from_id);
    $keyboard_channel_left = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "📌 عضویت مجدد", 'url' => "https://t.me/{$update['chat_member']['chat']['username']}"],
            ],
        ]
    ]);
    if (in_array($status, ['left', 'kicked', 'restricted'])) {
        sendmessage($from_id, $textbotlang['users']['channel']['left_channel'], $keyboard_channel_left, 'html');
        return;
    }
}
if (!in_array($Chat_type, ["private", "supergroup"]))
    return;
if (isset($chat_member))
    return;
$first_name = sanitizeUserName($first_name);
$setting = select("setting", "*");
if (!is_array($setting)) {
    error_log('Settings data is unavailable. Ensure the `setting` table exists and contains records.');
    return;
}
$ManagePanel = new ManagePanel();
$keyboard_check = json_decode($setting['keyboardmain'], true);
if (is_array($keyboard_check) && preg_match('/[\x{600}-\x{6FF}\x{FB50}-\x{FDFF}]/u', $keyboard_check['keyboard'][0][0]['text'])) {
    $keyboardmain = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}]]}';
    update("setting", "keyboardmain", $keyboardmain, null, null);
}

#-----------telegram_ip_ranges------------#
if (!checktelegramip())
    die("Unauthorized access");
#-----------end telegram_ip_ranges------------#
if (intval($from_id) == 0)
    return;
#-------------Variable----------#
$user = select("user", "*", "id", $from_id, "select", ['cache' => false]);
$isNewUser = !is_array($user);
$otherreport = select("topicid", "idreport", "report", "otherreport", "select")['idreport'];
$tronadoOldDomain = 'tronseller.storeddownloader.fun';
$tronadoRecommendedUrl = (defined('TRONADO_ORDER_TOKEN_ENDPOINTS') && isset(TRONADO_ORDER_TOKEN_ENDPOINTS[0]))
    ? TRONADO_ORDER_TOKEN_ENDPOINTS[0]
    : 'https://bot.tronado.cloud/api/v1/Order/GetOrderToken';
$tronadoWarningFlag = __DIR__ . '/urlpaymenttron_warning.flag';
if (!file_exists($tronadoWarningFlag)) {
    $storedUrl = getPaySettingValue('urlpaymenttron');
    if (is_string($storedUrl) && stripos($storedUrl, $tronadoOldDomain) !== false) {
        $warningText = "⚠️ دامنه قدیمی ترنادو هنوز در تنظیمات استفاده می‌شود. لطفاً آدرس جدید را جایگزین کنید:\n{$tronadoRecommendedUrl}";
        if (!empty($setting['Channel_Report'])) {
            $payload = [
                'chat_id' => $setting['Channel_Report'],
                'text' => $warningText,
                'parse_mode' => 'HTML'
            ];
            if (!empty($otherreport)) {
                $payload['message_thread_id'] = $otherreport;
            }
            telegram('sendmessage', $payload);
        } else {
            error_log($warningText);
        }
        file_put_contents($tronadoWarningFlag, (string) time());
    }
}
if ($isNewUser && $setting['statusnewuser'] == "onnewuser") {
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $newuser = sprintf($textbotlang['Admin']['ManageUser']['newuser'], $first_name, $username, "<a href = \"tg://user?id=$from_id\">$from_id</a>");
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $newuser,
            'reply_markup' => $Response,
            'parse_mode' => "HTML"
        ]);
    }
}
$date = time();
if ($from_id != 0 && $isNewUser) {
    if ($setting['verifystart'] != "onverify") {
        $valueverify = 1;
    } else {
        $valueverify = 0;
    }
    $randomString = bin2hex(random_bytes(6));
    $initialProcessingValue = '0';
    $initialProcessingValueOne = 'none';
    $initialProcessingValueTow = 'none';
    $initialProcessingValueFour = '0';
    $initialRollStatus = '0';
    $stmt = $pdo->prepare("INSERT IGNORE INTO user (id , step,limit_usertest,User_Status,number,Balance,pagenumber,username,agent,message_count,last_message_time,affiliates,affiliatescount,cardpayment,number_username,namecustom,register,verify,codeInvitation,pricediscount,maxbuyagent,joinchannel,score,status_cron,roll_Status,Processing_value,Processing_value_one,Processing_value_tow,Processing_value_four) VALUES (:from_id, 'none',:limit_usertest_all,'Active','none','0','1',:username,'f','0','0','0','0',:showcard,'100','none',:date,:verifycode,:codeInvitation,'0','0','0','0','1',:roll_status,:processing_value,:processing_value_one,:processing_value_tow,:processing_value_four)");
    $stmt->bindParam(':from_id', $from_id);
    $stmt->bindParam(':limit_usertest_all', $setting['limit_usertest_all']);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':showcard', $setting['showcard']);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':verifycode', $valueverify);
    $stmt->bindParam(':codeInvitation', $randomString);
    $stmt->bindParam(':roll_status', $initialRollStatus);
    $stmt->bindParam(':processing_value', $initialProcessingValue);
    $stmt->bindParam(':processing_value_one', $initialProcessingValueOne);
    $stmt->bindParam(':processing_value_tow', $initialProcessingValueTow);
    $stmt->bindParam(':processing_value_four', $initialProcessingValueFour);
    $stmt->execute();
    clearSelectCache('user');
    $user = select("user", "*", "id", $from_id, "select", ['cache' => false]);
    $isNewUser = !is_array($user);
}
if (!is_array($user)) {
    $user = array();
    $user = array(
        'step' => '',
        'Processing_value' => '',
        'User_Status' => '',
        'agent' => '',
        'username' => '',
        'limit_usertest' => '',
        'message_count' => '',
        'affiliates' => '',
        'last_message_time' => '',
        'cardpayment' => '',
        'roll_Status' => '',
        'number_username' => '',
        'number' => '',
        'register' => '',
        'codeInvitation' => '',
        'pricediscount' => '',
        'joinchannel' => '',
        'score' => "",
        'limitchangeloc' => ''
    );
} else {
    $user['codeInvitation'] = ensureUserInvitationCode($from_id, $user['codeInvitation'] ?? null);
}
$admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN", ['cache' => false]);
if (!is_array($admin_ids)) {
    $admin_ids = [];
}
$users_ids = select('user', 'id', null, null, 'FETCH_COLUMN', ['cache' => false]);
if (!is_array($users_ids)) {
    $users_ids = [];
}
$helpdata = select("help", "*");
$datatextbotget = select("textbot", "*", null, null, "fetchAll");
$id_invoice = select("invoice", "id_invoice", null, null, "FETCH_COLUMN");
$usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
$code_Discount = select("Discount", "code", null, null, "FETCH_COLUMN");
$marzban_list = select("marzban_panel", "name_panel", null, null, "FETCH_COLUMN");
$name_product = select("product", "name_product", null, null, "FETCH_COLUMN");
$SellDiscount = select("DiscountSell", "codeDiscount", null, null, "FETCH_COLUMN");
$channels_id = select("channels", "link", null, null, "FETCH_COLUMN");
$pricepayment = select("Payment_report", "price", null, null, "FETCH_COLUMN");
$listcard = select("card_number", "cardnumber", null, null, "FETCH_COLUMN");
$datatxtbot = array();
$topic_id = select("topicid", "*", null, null, "fetchAll");
$statusnote = false;
foreach ($topic_id as $topic) {
    if ($topic['report'] == "reportnight")
        $reportnight = $topic['idreport'];
    if ($topic['report'] == 'reporttest')
        $reporttest = $topic['idreport'];
    if ($topic['report'] == 'errorreport')
        $errorreport = $topic['idreport'];
    if ($topic['report'] == 'porsantreport')
        $porsantreport = $topic['idreport'];
    if ($topic['report'] == 'reportcron')
        $reportcron = $topic['idreport'];
    if ($topic['report'] == 'backupfile')
        $reportbackup = $topic['idreport'];
    if ($topic['report'] == 'buyreport')
        $buyreport = $topic['idreport'];
    if ($topic['report'] == 'otherservice')
        $otherservice = $topic['idreport'];
    if ($topic['report'] == 'paymentreport')
        $paymentreports = $topic['idreport'];

}
if ($setting['statusnamecustom'] == 'onnamecustom')
    $statusnote = true;
if ($setting['statusnoteforf'] == "0" && $user['agent'] == "f")
    $statusnote = false;
if (!function_exists('createForumTopicIfMissing')) {
    function createForumTopicIfMissing($currentId, $reportKey, $topicName, $channelId)
    {
        $numericId = intval($currentId);
        if ($numericId !== 0) {
            return;
        }

        $channelId = trim((string)$channelId);
        if ($channelId === '' || $channelId === '0') {
            return;
        }

        $response = telegram('createForumTopic', [
            'chat_id' => $channelId,
            'name' => $topicName
        ]);

        if (!is_array($response) || empty($response['ok'])) {
            $context = is_array($response) ? json_encode($response) : 'empty response';
            error_log("Failed to create forum topic {$reportKey}: {$context}");

            if (is_array($response) && isset($response['error_code']) && in_array($response['error_code'], [400, 403], true)) {
                update("topicid", "idreport", -1, "report", $reportKey);
            }

            return;
        }

        $threadId = $response['result']['message_thread_id'] ?? null;
        if ($threadId !== null) {
            update("topicid", "idreport", $threadId, "report", $reportKey);
        }
    }
}

createForumTopicIfMissing($porsantreport, 'porsantreport', $textbotlang['Admin']['affiliates']['titletopic'], $setting['Channel_Report']);
createForumTopicIfMissing($reportnight, 'reportnight', $textbotlang['Admin']['report']['reportnight'], $setting['Channel_Report']);
createForumTopicIfMissing($reportcron, 'reportcron', $textbotlang['Admin']['report']['reportcron'], $setting['Channel_Report']);
createForumTopicIfMissing($reportbackup, 'backupfile', "🤖 بکاپ ربات نماینده", $setting['Channel_Report']);
foreach ($datatextbotget as $row) {
    $datatxtbot[] = array(
        'id_text' => $row['id_text'],
        'text' => $row['text']
    );
}
$datatextbot = array(
    'text_usertest' => '',
    'text_Purchased_services' => '',
    'text_support' => '',
    'text_help' => '',
    'text_start' => '',
    'text_bot_off' => '',
    'text_dec_info' => '',
    'text_roll' => '',
    'text_fq' => '',
    'text_dec_fq' => '',
    'text_sell' => '',
    'text_Add_Balance' => '',
    'text_channel' => '',
    'text_Tariff_list' => '',
    'text_dec_Tariff_list' => '',
    'text_affiliates' => '',
    'text_pishinvoice' => '',
    'accountwallet' => '',
    'textafterpay' => '',
    'textaftertext' => '',
    'textmanual' => '',
    'textselectlocation' => '',
    'crontest' => '',
    'textrequestagent' => '',
    'textpanelagent' => '',
    'text_wheel_luck' => '',
    'text_cart' => '',
    'text_cart_auto' => '',
    'textafterpayibsng' => '',
    'text_request_agent_dec' => '',
    'carttocart' => '',
    'textnowpayment' => '',
    'textnowpaymenttron' => '',
    'iranpay1' => '',
    'iranpay2' => '',
    'iranpay3' => '',
    'aqayepardakht' => '',
    'zarinpey' => '',
    'zarinpal' => '',
    'textpaymentnotverify' => "",
    'text_star_telegram' => '',
    'text_extend' => '',
    'text_wgdashboard' => '',
    'text_Discount' => '',
);
foreach ($datatxtbot as $item) {
    if (isset($datatextbot[$item['id_text']])) {
        $datatextbot[$item['id_text']] = $item['text'];
    }
}
$time_Start = jdate('Y/m/d');
$date_start = jdate('H:i:s', time());
$time_string = "📆 $date_start → ⏰ $time_Start";
$varable_start = [
    '{username}' => $username,
    '{first_name}' => $first_name,
    '{last_name}' => $last_name,
    '{time}' => $time_string,
    '{version}' => $version
];
$datatextbot['text_start'] = strtr($datatextbot['text_start'], $varable_start);
if ($user['username'] == "none" || $user['username'] == null || $user['username'] != $username) {
    update("user", "username", $username, "id", $from_id);
}
if ($user['register'] == "none") {
    update("user", "register", time(), "id", $from_id);
}
if (!in_array($user['agent'], ["n", "n2", "f"]))
    update("user", "agent", "f", "id", $from_id);
#-----------User_Status------------#
if ($user['User_Status'] == "block" && !in_array($from_id, $admin_ids)) {
    $textblock = sprintf($textbotlang['users']['block']['descriptions'], $user['description_blocking']);
    sendmessage($from_id, $textblock, null, 'html');
    return;
}
#---------anti spam--------------#
$timebot = time();
$TimeLastMessage = $timebot - intval($user['last_message_time']);
if (floor($TimeLastMessage / 60) >= 1) {
    update("user", "last_message_time", $timebot, "id", $from_id);
    update("user", "message_count", "1", "id", $from_id);
} else {
    if (!in_array($from_id, $admin_ids)) {
        $addmessage = intval($user['message_count']) + 1;
        update("user", "message_count", $addmessage, "id", $from_id);
        $spamThreshold = 35;
        if ($addmessage >= $spamThreshold) {
            $User_Status = "block";
            $textblok = sprintf($textbotlang['users']['spam']['spamedreport'], $from_id);
            $Response = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $from_id],
                    ],
                ]
            ]);
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherservice,
                    'text' => $textblok,
                    'parse_mode' => "HTML",
                    'reply_markup' => $Response
                ]);
            }
            update("user", "User_Status", $User_Status, "id", $from_id);
            update("user", "description_blocking", $textbotlang['users']['spam']['spamed'], "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['spam']['spamedmessage'], null, 'html');
            return;
        }
    }
}


if (strpos($text, "/start ") !== false && $user['step'] != "gettextSystemMessage") {
    $affiliatesid = explode(" ", $text)[1];
    if (!in_array($affiliatesid, ['start', "usertest", "/start", "buy", "help"])) {
        isValidInvitationCode($setting, $from_id, $user['verify']);
        if ($setting['affiliatesstatus'] == "offaffiliates") {
            sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], $keyboard, 'HTML');
            return;
        }
        if (is_numeric($affiliatesid) && in_array($affiliatesid, $users_ids)) {
            if ($affiliatesid == $from_id) {
                sendmessage($from_id, $textbotlang['users']['affiliates']['invalidaffiliates'], null, 'html');
                return;
            }
            $user = select("user", "*", "id", $from_id, "select", ['cache' => false]);
            if (intval($user['affiliates']) != 0) {
                sendmessage($from_id, $textbotlang['users']['affiliates']['affiliateedago'], null, 'html');
                return;
            }
            update("user", "affiliates", $affiliatesid, "id", $from_id);
            $useraffiliates = select("user", "*", 'id', $affiliatesid, "select");
            sendmessage($from_id, "<b>🎉 خوش آمدی!</b>",
"
شما با دعوت <b>@{$useraffiliates['username']}</b> وارد ربات شدی و به عنوان زیرمجموعه ثبت شدی ✅

برای دریافت هدیه عضویت:
🔘 به منوی <b>زیرمجموعه‌گیری</b> برو
🔘 دکمه <b>🎁 دریافت هدیه عضویت</b> را بزن

با این کار، هم خودت و هم معرفت هدیه می‌گیرید! 💰",
 $keyboard, 'html');
            sendmessage($affiliatesid, "<b>🎉 یک زیرمجموعه جدید!</b>",
"
کاربر <b>@$username</b> با لینک دعوت شما وارد ربات شد ✅

با خریدهای این کاربر، <b>سهم هدیه شما</b> به حسابت واریز می‌شه 🔥", $keyboard, 'html');
            $addcountaffiliates = intval($useraffiliates['affiliatescount']) + 1;
            update("user", "affiliatescount", $addcountaffiliates, "id", $affiliatesid);
            $dateacc = date('Y/m/d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO reagent_report (user_id, get_gift, time, reagent)
                                   VALUES (:user_id, :get_gift, :time, :reagent)
                                   ON DUPLICATE KEY UPDATE reagent = VALUES(reagent), get_gift = VALUES(get_gift), time = VALUES(time)");
            $stmt->execute([
                ':user_id' => $from_id,
                ':get_gift' => 0,
                ':time' => $dateacc,
                ':reagent' => $affiliatesid,
            ]);
            if (function_exists('clearSelectCache')) {
                clearSelectCache('reagent_report');
            }
        } else {
            sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
            update("user", "Processing_value", "0", "id", $from_id);
            update("user", "Processing_value_one", "0", "id", $from_id);
            update("user", "Processing_value_tow", "0", "id", $from_id);
            update("user", "Processing_value_four", "0", "id", $from_id);
            step('home', $from_id);
        }
    } else {
        $text = $affiliatesid;
    }
}
if (intval($user['verify']) == 0 && !in_array($from_id, $admin_ids) && $setting['verifystart'] == "onverify") {
    $textverify = "⚠️ حساب شما احراز هویت نشده است پیام  شما  به ادمین ارسال شده  
    در صورت پیگیری  سریع تر می توانید به آیدی زیر پیام دهید
    @{$setting['id_support']}";
    sendmessage($from_id, $textverify, null, 'html');
    return;
}
;

#-----------roll------------#
if ($setting['roll_Status'] == "rolleon" && $user['roll_Status'] == 0 && ($text != "✅ قوانین را می پذیرم" and $datain != "acceptrule") && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $datatextbot['text_roll'], $confrimrolls, 'html');
    return;
}
if ($text == "✅ قوانین را می پذیرم" or $datain == "acceptrule") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['Rules'], $keyboard, 'html');
    $confrim = true;
    update("user", "roll_Status", $confrim, "id", $from_id);
}

#-----------Bot_Status------------#
if ($setting['Bot_Status'] == "botstatusoff" && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $datatextbot['text_bot_off'], null, 'html');
    return;
}
#-----------/start------------#
if ($user['joinchannel'] != "active") {
    if (count($channels_id) != 0) {
        $channels = channel($channels_id);
        if ($datain == "confirmchannel") {
            if (count($channels) == 0) {
                deletemessage($from_id, $message_id);
                sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
                telegram('answerCallbackQuery', [
                    'callback_query_id' => $callback_query_id,
                    'text' => $textbotlang['users']['channel']['confirmed'],
                    'show_alert' => false,
                    'cache_time' => 5,
                ]);
                return;
            }
            $keyboardchannel = [
                'inline_keyboard' => [],
            ];
            foreach ($channels as $channel) {
                $channelremark = select("channels", "*", 'link', $channel, "select");
                if ($channelremark['remark'] == null)
                    continue;
                if ($channelremark['linkjoin'] == null)
                    continue;
                $keyboardchannel['inline_keyboard'][] = [
                    [
                        'text' => "{$channelremark['remark']}",
                        'url' => $channelremark['linkjoin']
                    ],
                ];
            }
            $keyboardchannel['inline_keyboard'][] = [['text' => $textbotlang['users']['channel']['confirmjoin'], 'callback_data' => "confirmchannel"]];
            $keyboardchannel = json_encode($keyboardchannel);
            Editmessagetext($from_id, $message_id, $datatextbot['text_channel'], $keyboardchannel);
            telegram('answerCallbackQuery', [
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['users']['channel']['notconfirmed'],
                'show_alert' => true,
                'cache_time' => 5,
            ]);
            $partsaffiliates = explode("_", $user['Processing_value_four']);
            if ($partsaffiliates[0] == "affiliates") {
                $affiliatesid = $partsaffiliates[1];
                if (!in_array($affiliatesid, $users_ids)) {
                    sendmessage($from_id, $textbotlang['users']['affiliates']['affiliatesidyou'], null, 'html');
                    return;
                }
                if ($affiliatesid == $from_id) {
                    sendmessage($from_id, $textbotlang['users']['affiliates']['invalidaffiliates'], null, 'html');
                    return;
                }
                $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
                $useraffiliates = select("user", "*", 'id', $affiliatesid, "select");
                if ($marzbanDiscountaffiliates['Discount'] == "onDiscountaffiliates") {
                    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
                    $Balance_add_user = $useraffiliates['Balance'] + $marzbanDiscountaffiliates['price_Discount'];
                    update("user", "Balance", $Balance_add_user, "id", $affiliatesid);
                    $addbalancediscount = number_format($marzbanDiscountaffiliates['price_Discount'], 0);
                    sendmessage($affiliatesid, "🎁 مبلغ $addbalancediscount به موجودی شما از طرف زیر مجموعه با شناسه کاربری $from_id اضافه گردید.", null, 'html');
                }
                sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
                $addcountaffiliates = intval($useraffiliates['affiliatescount']) + 1;
                update("user", "affiliates", $affiliatesid, "id", $from_id);
                update("user", "Processing_value_four", "none", "id", $from_id);
                update("user", "affiliatescount", $addcountaffiliates, "id", $affiliatesid);
            }
            return;
        }
        if (count($channels) != 0 && !in_array($from_id, $admin_ids)) {
            $keyboardchannel = [
                'inline_keyboard' => [],
            ];
            foreach ($channels as $channel) {
                $channelremark = select("channels", "*", 'link', $channel, "select");
                if ($channelremark['remark'] == null)
                    continue;
                if ($channelremark['linkjoin'] == null)
                    continue;
                $keyboardchannel['inline_keyboard'][] = [
                    [
                        'text' => "{$channelremark['remark']}",
                        'url' => $channelremark['linkjoin']
                    ],
                ];
            }
            $keyboardchannel['inline_keyboard'][] = [['text' => $textbotlang['users']['channel']['confirmjoin'], 'callback_data' => "confirmchannel"]];
            $keyboardchannel = json_encode($keyboardchannel);
            sendmessage($from_id, $datatextbot['text_channel'], $keyboardchannel, 'html');
            return;
        }
    }
}
if ($text == "/start" || $datain == "start" || $text == "start") {
    sendmessage($from_id, $datatextbot['text_start'], $keyboard, "html");
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    step('home', $from_id);
    return;
} elseif ($text == "version") {
    sendmessage($from_id, $version, null, 'html');
} elseif ($text == $textbotlang['users']['backbtn'] || $datain == "backuser") {
    if ($datain == "backuser")
        deletemessage($from_id, $message_id);
    $message_id = sendmessage($from_id, $textbotlang['users']['back'], $keyboard, 'html');
    step('home', $from_id);
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    return;
} elseif ($user['step'] == 'get_number') {
    if (empty($user_phone)) {
        sendmessage($from_id, $textbotlang['users']['number']['false'], $request_contact, 'html');
        return;
    }
    if ($contact_id != $from_id) {
        sendmessage($from_id, $textbotlang['users']['number']['Warning'], $request_contact, 'html');
        return;
    }
    if ($setting['iran_number'] == "onAuthenticationiran" && !preg_match("/989[0-9]{9}$/", $user_phone)) {
        sendmessage($from_id, $textbotlang['users']['number']['erroriran'], $request_contact, 'html');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['number']['active'], json_encode(['inline_keyboard' => [], 'remove_keyboard' => true]), 'html');
    sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
    update("user", "number", $user_phone, "id", $from_id);
    step('home', $from_id);
} elseif ($text == $datatextbot['text_Purchased_services'] || $datain == "backorder" || $text == "/services") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $invoices = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_null($invoices) && $setting['NotUser'] == "offnotuser") {
        sendmessage($from_id, $textbotlang['users']['sell']['service_not_available'], null, 'html');
        return;
    }

    $pages = 1;
    update("user", "pagenumber", $pages, "id", $from_id);
    $page = 1;
    $items_per_page = 20;
    $start_index = ($page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $start_index, $items_per_page");
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        ['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    if ($datain == "backorder") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
    } else {
        sendmessage($from_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json, 'html');
    }
} elseif ($datain == 'next_page') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $start_index, $items_per_page");
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
} elseif ($datain == 'previous_page') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $previous_page = 1;
    } else {
        $previous_page = $page - 1;
    }
    $start_index = ($previous_page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $previous_page, $items_per_page");
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $previous_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
} elseif ($datain == "notusernameme") {
    sendmessage($from_id, $textbotlang['users']['stateus']['SendUsername'], $backuser, 'html');
    step('getusernameinfo', $from_id);
} elseif ($user['step'] == "getusernameinfo") {
    if (empty($text))
        return;
    $usernameconfig = "";
    if (strlen($text) > 32) {
        if (!filter_var($text, FILTER_VALIDATE_URL)) {
            sendmessage($from_id, "❌ لینک اشتراک نامعتبر است", $backuser, 'HTML');
            return;
        }
        $date = outputlunksub($text);
        if (!isset($date)) {
            sendmessage($from_id, "❌ لینک اشتراک نامعتبر است", $backuser, 'HTML');
            return;
        }
        $date = json_decode($date, true);
        if (!isset($date['username'])) {
            sendmessage($from_id, "❌ لینک اشتراک نامعتبر است", $backuser, 'HTML');
            return;
        }
        $usernameconfig = $date['username'];
    } else {
        if (!preg_match('/^\w{3,32}$/', $text)) {
            sendmessage($from_id, $textbotlang['users']['stateus']['Invalidusername'], $backuser, 'html');
            return;
        }
        $usernameconfig = $text;
    }
    update("user", "Processing_value", $usernameconfig, "id", $from_id);
    sendmessage($from_id, $datatextbot['textselectlocation'], $list_marzban_panel_user, 'html');
    step('getdata', $from_id);
} elseif (preg_match('/locationnotuser_(.*)/', $datain, $dataget)) {
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $dataget[1]);
    update("user", "Processing_value_four", $marzban_list_get['code_panel'], "id", $from_id);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $user['Processing_value']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        if ($DataUserOut['msg'] == "User not found") {
            sendmessage($from_id, $textbotlang['users']['stateus']['notUsernameget'], $keyboard, 'html');
            step('home', $from_id);
            return;
        }
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    #-------------[ status ]----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'deactivev' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['on_hold'],
        'Unknown' => $textbotlang['users']['stateus']['Unknown']
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    #-----------------------------#


    $keyboardinfo = [
        'inline_keyboard' => [
            [
                ['text' => $DataUserOut['username'], 'callback_data' => "username"],
                ['text' => $textbotlang['users']['stateus']['username'], 'callback_data' => 'username'],
            ],
            [
                ['text' => $status_var, 'callback_data' => 'status_var'],
                ['text' => $textbotlang['users']['stateus']['stateus'], 'callback_data' => 'status_var'],
            ],
            [
                ['text' => $expirationDate, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['stateus']['expirationDate'], 'callback_data' => 'expirationDate'],
            ],
            [],
            [
                ['text' => $day, 'callback_data' => 'روز'],
                ['text' => $textbotlang['users']['stateus']['daysleft'], 'callback_data' => 'day'],
            ],
            [
                ['text' => $LastTraffic, 'callback_data' => 'LastTraffic'],
                ['text' => $textbotlang['users']['stateus']['LastTraffic'], 'callback_data' => 'LastTraffic'],
            ],
            [
                ['text' => $usedTrafficGb, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['stateus']['usedTrafficGb'], 'callback_data' => 'expirationDate'],
            ],
            [
                ['text' => $RemainingVolume, 'callback_data' => 'RemainingVolume'],
                ['text' => $textbotlang['users']['stateus']['RemainingVolume'], 'callback_data' => 'RemainingVolume'],
            ]
        ]
    ];
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    if ($marzbanstatusextra == "onextra") {
        $keyboardinfo['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extends_' . $DataUserOut['username'] . "_" . $dataget[1]],
            ['text' => $textbotlang['users']['Extra_volume']['sellextra'], 'callback_data' => 'Extra_volumes_' . $DataUserOut['username'] . '_' . $dataget[1]],
        ];
    } else {
        $keyboardinfo['inline_keyboard'][] = [['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extends_' . $DataUserOut['username'] . "_" . $dataget[1]]];
    }
    $keyboardinfo = json_encode($keyboardinfo);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['stateus']['info'], $keyboardinfo);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'html');
    step('home', $from_id);
} elseif (preg_match('/^product_(\w+)/', $datain, $dataget) || preg_match('/updateproduct_(\w+)/', $datain, $dataget) || $user['step'] == "getuseragnetservice" || $datain == "productcheckdata") {
    if ($user['step'] == "getuseragnetservice") {
        $username = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $sql = "SELECT * FROM invoice WHERE (username LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :notes, '%') OR Volume LIKE CONCAT('%',:Volume, '%') OR Service_time LIKE CONCAT('%',:Service_time, '%')) AND id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':Service_time', $username, PDO::PARAM_STR);
        $stmt->bindParam(':Volume', $username, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $username, PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    } elseif ($datain == "productcheckdata") {
        $username = $user['Processing_value'];
        $sql = "SELECT * FROM invoice WHERE username = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    } elseif ($datain[0] == "u") {
        $username = $dataget[1];
        $sql = "SELECT * FROM invoice WHERE id_invoice = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => "♻️ اطلاعات بروز شد",
            'show_alert' => false,
            'cache_time' => 5,
        ));
    } else {
        $username = $dataget[1];
        $sql = "SELECT * FROM invoice WHERE id_invoice = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    }
    if ($user['step'] == "getuseragnetservice" && $stmt->rowCount() > 1) {
        $countservice = $stmt->rowCount();
        $pages = 1;
        update("user", "pagenumber", $pages, "id", $from_id);
        $page = 1;
        $items_per_page = 20;
        $start_index = ($page - 1) * $items_per_page;
        $keyboardlists = [
            'inline_keyboard' => [],
        ];
        if ($setting['statusnamecustom'] == 'onnamecustom') {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data = "";
                if ($row != null)
                    $data = " | {$row['note']}";
                $keyboardlists['inline_keyboard'][] = [
                    [
                        'text' => "✨" . $row['username'] . $data . "✨",
                        'callback_data' => "product_" . $row['id_invoice']
                    ],
                ];
            }
        } else {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $keyboardlists['inline_keyboard'][] = [
                    [
                        'text' => "✨" . $row['username'] . "✨",
                        'callback_data' => "product_" . $row['id_invoice']
                    ],
                ];
            }
        }
        $backuser = [
            [
                'text' => "🔙 بازگشت به منوی اصلی",
                'callback_data' => 'backuser'
            ]
        ];
        if ($setting['NotUser'] == "onnotuser") {
            $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
        }
        $keyboardlists['inline_keyboard'][] = $backuser;
        $keyboard_json = json_encode($keyboardlists);
        sendmessage($from_id, "🛍 $countservice عدد سرویس یافت برای مشاهده و مدیریت سرویس روی یکی از سرویس ها کلیک کنید", $keyboard_json, 'html');
        step("home", $from_id);
        return;
    }
    $nameloc = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $nameloc['id_invoice'];
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, "❌ امکان مشاهده اطلاعات اکانت درحال حاضر وجود ندارد", $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    $marzban = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban['name_panel'] != null) {
        update("user", "Processing_value_four", $marzban['name_panel'], "id", $from_id);
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        update("invoice", "Status", "disabledn", "id_invoice", $nameloc['id_invoice']);
        sendmessage($from_id, $textbotlang['users']['stateus']['UserNotFound'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['panelNotConnected'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = 'آنلاین';
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = 'آفلاین';
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateTime = new DateTime($DataUserOut['online_at'], new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
            $lastonline = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
        } else {
            $lastonline = "متصل نشده";
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['on_hold'],
        'Unknown' => $textbotlang['users']['stateus']['Unknown'],
        'deactivev' => $textbotlang['users']['stateus']['disabled'],
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    if ($timeDiff < 0) {
        $day = 0;
    } else {
        $day = "";
        $timemonth = floor($timeDiff / 2592000);
        if ($timemonth > 0) {
            $day .= $timemonth . $textbotlang['users']['stateus']['month'];
            $timeDiffday = $timeDiff - (2592000 * $timemonth);
        } else {
            $timeDiffday = $timeDiff;
        }
        $timereminday = floor($timeDiffday / 86400);
        if ($timereminday > 0) {
            $day .= $timereminday . $textbotlang['users']['stateus']['day'];
        }
        $timehoures = intval(($timeDiffday - ($timereminday * 86400)) / 3600);
        if ($timehoures > 0) {
            $day .= $timehoures . $textbotlang['users']['stateus']['hour'];
        }
        $timehoursall = $timeDiffday - ($timereminday * 86400);
        $timehoursall = $timehoursall - ($timehoures * 3600);
        $timeminuts = intval($timehoursall / 60);
        if ($timeminuts > 0) {
            $day .= $timeminuts . $textbotlang['users']['stateus']['min'];
        }
        $day .= " دیگر";
    }
    #--------------[ subsupdate ]---------------#
    if ($DataUserOut['sub_updated_at'] !== null) {
        $sub_updated = $DataUserOut['sub_updated_at'];
        $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
        $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
    }
    #--------------[ Percent ]---------------#
    if ($DataUserOut['data_limit'] != null && $DataUserOut['used_traffic'] != null) {
        $Percent = ($DataUserOut['data_limit'] - $DataUserOut['used_traffic']) * 100 / $DataUserOut['data_limit'];
    } else {
        $Percent = "100";
    }
    if ($Percent < 0)
        $Percent = -($Percent);
    $Percent = round($Percent, 2);
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder'],
            ]
        ]
    ]);
    if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
        $userpassword = "🔑 رمز عبور سرویس شما : <code>{$DataUserOut['subscription_url']}</code>";
    } else {
        $userpassword = "";
    }
    if ($marzban['type'] == "Manualsale") {
        $userinfo = select("manualsell", "*", "username", $nameloc['username'], "select");
        $textinfo = "وضعیت سرویس : <b>$status_var</b>
نام کاربری سرویس : {$DataUserOut['username']}
📎 کد پیگیری سرویس : {$nameloc['id_invoice']}

📌 اطلاعات سرویس : 
{$userinfo['contentrecord']}";
        if ($user['step'] == "getuseragnetservice") {
            sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
        } elseif ($datain == "productcheckdata") {
            deletemessage($from_id, $message_id);
            sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
        } else {
            Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
        }
        return;
    }
    $nameconfig = "";
    if ($nameloc['note'] != null) {
        $nameconfig = "✍️ یادداشت کانفیگ : {$nameloc['note']}";
    }
    $stmt = $pdo->prepare("SELECT value FROM service_other WHERE username = :username AND type = 'extend_user' AND status = 'paid' ORDER BY time DESC");
    $stmt->execute([
        ':username' => $nameloc['username'],
    ]);
    if ($stmt->rowCount() != 0) {
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!($service_other == false || !(is_string($service_other['value']) && is_array(json_decode($service_other['value'], true))))) {
            $service_other = json_decode($service_other['value'], true);
            $codeproduct = select("product", "*", "code_product", $service_other['code_product'], "select");
            if ($codeproduct != false) {
                $nameloc['name_product'] = $codeproduct['name_product'];
                $nameloc['Volume'] = $codeproduct['Volume_constraint'];
                $nameloc['Service_time'] = $codeproduct['Service_time'];
            }
        }
    }
    #-----------------------------#
    $statustimeextra = select("shopSetting", "*", "Namevalue", "statustimeextra", "select")['value'];
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    $statusdisorder = select("shopSetting", "*", "Namevalue", "statusdisorder", "select")['value'];
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    $statusshowconfig = select("shopSetting", "*", "Namevalue", "configshow", "select")['value'];
    $statusremoveserveice = select("shopSetting", "*", "Namevalue", "backserviecstatus", "select")['value'];
    if (!in_array($status, ["active", "on_hold", "disabled", "Unknown"])) {
        $textinfo = "وضعیت سرویس : <b>$status_var</b>
👤 نام کاربری سرویس : <code>{$DataUserOut['username']}</code>
🌍 موقعیت سرویس :{$nameloc['Service_location']}
نام محصول :{$nameloc['name_product']}

📶 اخرین زمان اتصال شما : $lastonline

🔋 ترافیک : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 تاریخ اتمام :  $expirationDate ($day)

$nameconfig";

        $keyboardsetting = [
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extend_' . $username],
                    ['text' => $textbotlang['users']['Extra_volume']['sellextra'], 'callback_data' => 'Extra_volume_' . $username],
                ],
                [
                    ['text' => "❌ حذف سرویس", 'callback_data' => 'removeauto-' . $username],
                    ['text' => $textbotlang['users']['Extra_time']['title'], 'callback_data' => 'Extra_time_' . $username],
                ],
                [
                    ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder'],
                ]
            ]
        ];
        if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
            unset($keyboardsetting['inline_keyboard'][1][1]);
            unset($keyboardsetting['inline_keyboard'][0]);
        }
        if ($statustimeextra == "offtimeextraa")
            unset($keyboardsetting['inline_keyboard'][1][1]);
        if ($marzbanstatusextra == "offextra")
            unset($keyboardsetting['inline_keyboard'][0][1]);
        $keyboardsetting['inline_keyboard'] = array_values($keyboardsetting['inline_keyboard']);
        $keyboardsetting = json_encode($keyboardsetting);
    } else {
        $marzbancount = select("marzban_panel", "*", "status", "active", "count");
        if ($DataUserOut['status'] == "active") {
            $namestatus = '❌ خاموش کردن اکانت';
        } else {
            $namestatus = '💡 روشن کردن اکانت';
        }
        $keyboarddate = array(
            'updateinfo' => array(
                'text' => "♻️ بروزرسانی اطلاعات",
                'callback_data' => "updateproduct_"
            ),
            'linksub' => array(
                'text' => $textbotlang['users']['stateus']['linksub'],
                'callback_data' => "subscriptionurl_"
            ),
            'config' => array(
                'text' => $textbotlang['users']['stateus']['config'],
                'callback_data' => "config_"
            ),
            'extend' => array(
                'text' => $textbotlang['users']['extend']['title'],
                'callback_data' => "extend_"
            ),
            'changelink' => array(
                'text' => $textbotlang['users']['changelink']['btntitle'],
                'callback_data' => "changelink_"
            ),
            'removeservice' => array(
                'text' => $textbotlang['users']['stateus']['removeservice'],
                'callback_data' => "removeserviceuser_"
            ),
            'changenameconfig' => array(
                'text' => '📝 تغییر یادداشت',
                'callback_data' => "changenote_"
            ),
            'Extra_volume' => array(
                'text' => $textbotlang['users']['Extra_volume']['sellextra'],
                'callback_data' => "Extra_volume_"
            ),
            'Extra_time' => array(
                'text' => $textbotlang['users']['Extra_time']['title'],
                'callback_data' => "Extra_time_"
            ),
            'changestatus' => array(
                'text' => $namestatus,
                'callback_data' => "changestatus_"
            ),
            'transfor' => array(
                'text' => $textbotlang['Admin']['transfor']['title'],
                'callback_data' => "transfer_"
            ),
            'change-location' => array(
                'text' => $textbotlang['Admin']['change-location']['title'],
                'callback_data' => "changeloc_"
            ),
            'ekhtelal' => array(
                'text' => "⚠️ ارسال گزارش اختلال",
                'callback_data' => "disorder-"
            )
        );
        if ($nameloc['name_product'] == "سرویس تست") {
            unset($keyboarddate['transfor']);
            unset($keyboarddate['Extra_time']);
            unset($keyboarddate['removeservice']);
        }
        if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
            unset($keyboarddate['linksub']);
            unset($keyboarddate['config']);
            unset($keyboarddate['extend']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['change-location']);
            unset($keyboarddate['changelink']);
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['Extra_time']);
        }
        if ($marzban['type'] == "eylanpanel") {
            unset($keyboarddate['config']);
            unset($keyboarddate['changelink']);
        }
        if ($marzban['type'] == "WGDashboard") {
            unset($keyboarddate['config']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['change-location']);
            unset($keyboarddate['changelink']);
        }
        if ($marzban['status_extend'] == "off_extend") {
            unset($keyboarddate['Extra_time']);
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['extend']);
        }
        if ($statusremoveserveice == "off")
            unset($keyboarddate['removeservice']);
        if ($statusshowconfig == "offconfig")
            unset($keyboarddate['config']);
        if ($marzban['type'] == "hiddify") {
            unset($keyboarddate['changelink']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['config']);
        }
        if ($statusdisorder == "offdisorder")
            unset($keyboarddate['ekhtelal']);
        if ($nameloc['Service_time'] == "0")
            unset($keyboarddate['Extra_time']);
        if ($nameloc['Volume'] == "0") {
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['Extra_time']);
        }
        if ($statuschangeservice == "offstatus")
            unset($keyboarddate['changestatus']);
        if ($setting['statusnamecustom'] == 'offnamecustom')
            unset($keyboarddate['changenameconfig']);
        if ($marzbancount == 1)
            unset($keyboarddate['change-location']);
        if ($marzban['changeloc'] == "offchangeloc")
            unset($keyboarddate['change-location']);
        if ($statustimeextra == "offtimeextraa")
            unset($keyboarddate['Extra_time']);
        if ($marzbanstatusextra == "offextra")
            unset($keyboarddate['Extra_volume']);
        $tempArray = [];
        $keyboardsetting = ['inline_keyboard' => []];
        foreach ($keyboarddate as $keyboardtext) {
            $tempArray[] = ['text' => $keyboardtext['text'], 'callback_data' => $keyboardtext['callback_data'] . $username];
            if (count($tempArray) == 2 or $keyboardtext['text'] == "♻️ بروزرسانی اطلاعات") {
                $keyboardsetting['inline_keyboard'][] = $tempArray;
                $tempArray = [];
            }
        }
        if (count($tempArray) > 0) {
            $keyboardsetting['inline_keyboard'][] = $tempArray;
        }
        $keyboardsetting['inline_keyboard'][] = [['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder']];
        $keyboardsetting = json_encode($keyboardsetting);
        if ($DataUserOut['sub_updated_at'] !== null) {
            $textconnect = "
📶 اخرین زمان اتصال  : $lastonline
🔄 اخرین زمان آپدیت لینک اشتراک  : $lastupdate
#️⃣ کلاینت متصل شده :<code>{$DataUserOut['sub_last_user_agent']}</code>";
        } elseif ($marzban['type'] == "WGDashboard") {
            $textconnect = "";
        } else {
            $textconnect = "📶 اخرین زمان اتصال شما : $lastonline";
        }
        $textinfo = "📊وضعیت سرویس : $status_var
👤 نام سرویس : <code>{$DataUserOut['username']}</code>
$userpassword
$nameconfig
🌍 موقعیت سرویس :{$nameloc['Service_location']}
🗂 نام محصول :{$nameloc['name_product']}

🔋 ترافیک : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 تاریخ اتمام : $expirationDate ($day)

$textconnect

💡 برای قطع دسترسی دیگران کافیست روی گزینه \"تغییر لینک\" کلیک کنید.";
    }
    if ($user['step'] == "getuseragnetservice") {
        sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
    } elseif ($datain == "productcheckdata") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
    } else {
        Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
    }
    step('home', $from_id);
    return;
} elseif (preg_match('/subscriptionurl_(\w+)/', $datain, $dataget) || (is_string($text) && strpos($text, "/sub ") !== false)) {
    if (is_string($text) && $text !== '' && $text[0] == "/") {
        $id_invoice = explode(' ', $text)[1];
        $nameloc = select("invoice", "*", "username", $id_invoice, "select");
        if ($nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    } else {
        $id_invoice = $dataget[1];
        $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    }
    if ($nameloc == false)
        return;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $Check_token = token_panel($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $subscriptionurl = $DataUserOut['subscription_url'];
    if ($marzban_list_get['type'] == "WGDashboard") {
        $textsub = "qrcode اشتراک شما";
    } else {
        $textsub = "
{$textbotlang['users']['stateus']['linksub']}
            
<code>$subscriptionurl</code>";
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "productcheckdata"],
            ]
        ]
    ]);
    update("user", "Processing_value", $nameloc['username'], "id", $from_id);
    $subscriptionurl = $DataUserOut['subscription_url'];
    $randomString = bin2hex(random_bytes(3));
    $urlimage = "$from_id$randomString.png";
    $qrCode = createqrcode($subscriptionurl);
    file_put_contents($urlimage, $qrCode->getString());
    if (!addBackgroundImage($urlimage, $qrCode, 'images.jpg')) {
        error_log("Unable to apply background image for QR code using path 'images.jpg'");
    }
    telegram('sendphoto', [
        'chat_id' => $from_id,
        'photo' => new CURLFile($urlimage),
        'reply_markup' => $bakinfos,
        'caption' => $textsub,
        'parse_mode' => "HTML",
    ]);
    unlink($urlimage);
    if ($marzban_list_get['type'] == "WGDashboard") {
        $urlimage = "{$marzban_list_get['inboundid']}_{$nameloc['username']}.conf";
        file_put_contents($urlimage, $DataUserOut['subscription_url']);
        sendDocument($from_id, $urlimage, "⚙️ کانفیگ شما");
        unlink($urlimage);
    }
} elseif (preg_match('/removeauto-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    update('invoice', 'status', 'removebyuser', 'id_invoice', $id_invoice);
    $tetremove = "ادمین عزیز یک کاربر سرویس خود را پس از پایان حجم یا زمان حدف کرده است
نام کاربری کانفیک : {$nameloc['username']}";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $tetremove,
            'parse_mode' => "HTML"
        ]);
    }
    sendmessage($from_id, "📌 سرویس با موفقیت حذف شد", null, 'html');
} elseif (preg_match('/config_(\w+)/', $datain, $dataget) || (is_string($text) && strpos($text, "/link ") !== false)) {
    $textCommand = is_string($text) ? $text : '';
    if ($textCommand !== '' && $textCommand[0] === "/") {
        $parts = explode(' ', $textCommand, 2);
        $id_invoice = $parts[1] ?? null;
        if ($id_invoice === null) {
            sendmessage($from_id, $textbotlang['users']['stateus']['UserNotFound'], null, 'html');
            return;
        }
        $nameloc = select("invoice", "*", "username", $id_invoice, "select");
        if ($nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    } else {
        $id_invoice = $dataget[1];
        $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    }
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['stateus']['UserNotFound'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if (!is_array($DataUserOut['links'])) {
        sendmessage($from_id, "❌  خطا در خواندن اطلاعات کانفیگ با پشتیبانی در ارتباط باشید.", null, 'html');
        return;
    }
    Editmessagetext($from_id, $message_id, "📌 از لیست زیر یک کانفیگ را انتخاب استفاده نمایید.", keyboard_config($DataUserOut['links'], $nameloc['id_invoice']));
} elseif (preg_match('/configget_(.*)_(.*)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['stateus']['UserNotFound'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "productcheckdata"],
            ]
        ]
    ]);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $config = "";
    if ($dataget[2] == "1520") {
        for ($i = 0; $i < count($DataUserOut['links']); ++$i) {
            $randomString = bin2hex(random_bytes(3));
            $urlimage = "$from_id$randomString.png";
            $qrCode = createqrcode($DataUserOut['links'][$i]);
            file_put_contents($urlimage, $qrCode->getString());
            if (!addBackgroundImage($urlimage, $qrCode, 'images.jpg')) {
                error_log("Unable to apply background image for QR code using path 'images.jpg'");
            }
            telegram('sendphoto', [
                'chat_id' => $from_id,
                'photo' => new CURLFile($urlimage),
                'caption' => "<code>{$DataUserOut['links'][$i]}</code>",
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        }
        return;
    }
    $randomString = bin2hex(random_bytes(3));
    $urlimage = "$from_id$randomString.png";
    $qrCode = createqrcode($DataUserOut['links'][$dataget[2]]);
    file_put_contents($urlimage, $qrCode->getString());
    if (!addBackgroundImage($urlimage, $qrCode, 'images.jpg')) {
        error_log("Unable to apply background image for QR code using path 'images.jpg'");
    }
    telegram('sendphoto', [
        'chat_id' => $from_id,
        'photo' => new CURLFile($urlimage),
        'caption' => "<code>{$DataUserOut['links'][$dataget[2]]}</code>",
        'parse_mode' => "HTML",
    ]);
    unlink($urlimage);
} elseif (preg_match('/changestatus_(\w+)/', $datain, $dataget)) {
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    if ($statuschangeservice == "offstatus") {
        sendmessage($from_id, "❌ این قابلیت درحال حاضر در دسترس نیست", null, 'html');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc['Status'] == "disablebyadmin") {
        sendmessage($from_id, "❌ این قابلیت درحال حاضر در دسترس نیست", null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ هنوز به کانفیگ متصل نشده اید و امکان تغییر وضعیت سرویس وجود ندارد. بعد از متصل شدن به کانفیگ می توانید از این قابلیت استفاده نمایید.", null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "active") {
        $confirmdisableaccount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '✅ تایید و غیرفعال کردن کانفیگ', 'callback_data' => "confirmaccountdisable_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, "📌 با تایید گزینه زیر کانفیگ شما خاموش و دیگر امکان اتصال به کانفیگ وجود ندارد.
⚠️ در صورتی که میخواهید مجدد کانفیگ فعال شود باید از بخش مدیریت سرویس دکمه <u>💡 روشن کردن اکانت</u> را کلیک کنید", $confirmdisableaccount);
    } else {
        $confirmdisableaccount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '✅ تایید و فعال کردن کانفیگ', 'callback_data' => "confirmaccountdisable_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, "📌 با تایید گزینه زیر کانفیگ شما روشن خواهد شد. و می توانید به کانفیگ خود متصل شوید
⚠️ در صورتی که میخواهید مجدد کانفیگ غیرفعال شود باید از بخش مدیریت سرویس دکمه <u>❌ خاموش کردن اکانت</u>را کلیک کنید", $confirmdisableaccount);
    }
} elseif (preg_match('/confirmaccountdisable_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $dataoutput = $ManagePanel->Change_status($nameloc['username'], $nameloc['Service_location']);
    if ($dataoutput['status'] == "Unsuccessful") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['stateus']['notchanged'], $bakinfos);
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "active") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['stateus']['activedconfig'], $bakinfos);
    } else {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['stateus']['disabledconfig'], $bakinfos);
    }
} elseif (preg_match('/extend_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, "❌ تمدید با خطا مواجه گردید مراحل تمدید را مجددا انجام دهید.", null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان تمدید در این پنل وجود ندارد", null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ هنوز به سرویس متصل نشده اید برای تمدید سرویس ابتدا به سرویس متصل شوید سپس اقدام به تمدید کنید", null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all')");
    $stmt->execute([
        ':service_location' => $marzban_list_get['name_panel'],
    ]);
    $product = $stmt->rowCount();
    savedata("clear", "id_invoice", $nameloc['id_invoice']);
    if ($product == 0) {
        $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
        sendmessage($from_id, $textcustom, $backuser, 'html');
        deletemessage($from_id, $message_id);
        step('gettimecustomvolomforextend', $from_id);
        return;
    }
    if ($nameloc['name_product'] == "🛍 حجم دلخواه" || $nameloc['name_product'] == "⚙️ سرویس دلخواه") {
        $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
        sendmessage($from_id, $textcustom, $backuser, 'html');
        deletemessage($from_id, $message_id);
        step('gettimecustomvolomforextend', $from_id);
        return;
    }
    if ($setting['statuscategory'] == "offcategory") {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all')");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
        ]);
        $productextend = ['inline_keyboard' => []];
        $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hide_panel = json_decode($result['hide_panel'], true);
            if (is_array($hide_panel) && in_array($nameloc['Service_location'], $hide_panel)) {
                error_log("Product {$result['code_product']} is marked hidden for {$nameloc['Service_location']} but was kept visible for extend.");
            }
            if (intval($user['pricediscount']) != 0) {
                $resultper = ($result['price_product'] * $user['pricediscount']) / 100;
                $result['price_product'] = $result['price_product'] - $resultper;
            }
            if ($statusshowprice == "offshowprice") {
                $namekeyboard = $result['name_product'];
            } else {
                $result['price_product'] = number_format($result['price_product']);
                $namekeyboard = $result['name_product'] . " - " . $result['price_product'] . "تومان";
            }
            $productextend['inline_keyboard'][] = [
                ['text' => $namekeyboard, 'callback_data' => "serviceextendselect_" . $result['code_product']]
            ];
        }
        $productextend['inline_keyboard'][] = [
            ['text' => "♻️ تمدید پلن فعلی", 'callback_data' => "exntedagei"]
        ];
        $productextend['inline_keyboard'][] = [
            ['text' => "🏠 بازگشت به اطلاعات سرویس", 'callback_data' => "product_" . $nameloc['id_invoice']]
        ];

        $json_list_product_lists = json_encode($productextend);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], $json_list_product_lists);
    } else {
        $monthkeyboard = keyboardTimeCategory($nameloc['Service_location'], $user['agent'], "productextendmonths_", "product_$id_invoice", false, true);
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['month']['title'], $monthkeyboard);
    }
} elseif ($user['step'] == "gettimecustomvolomforextend") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = "❌ حجم نامعتبر است.\n🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد";
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    savedata("save", "volume", $text);
    $textcustom = "⌛️ زمان سرویس خود را انتخاب نمایید 
📌 تعرفه هر روز  : $customtimevalueprice  تومان
⚠️ حداقل زمان $maintime روز  و حداکثر $maxtime روز  می توانید تهیه کنید";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('getvolumecustomuserforextend', $from_id);
} elseif (preg_match('/productextendmonths_(\w+)/', $datain, $dataget)) {
    $monthenumber = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND Service_time = :monthe");
    $stmt->execute([
        ':service_location' => $nameloc['Service_location'],
        'monthe' => $monthenumber
    ]);
    $productextend = ['inline_keyboard' => []];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (intval($user['pricediscount']) != 0) {
            $resultper = ($result['price_product'] * $user['pricediscount']) / 100;
            $result['price_product'] = $result['price_product'] - $resultper;
        }
        if ($statusshowprice == "offshowprice") {
            $namekeyboard = $result['name_product'];
        } else {
            $result['price_product'] = number_format($result['price_product']);
            $namekeyboard = $result['name_product'] . " - " . $result['price_product'] . "تومان";
        }
        $productextend['inline_keyboard'][] = [
            ['text' => $namekeyboard, 'callback_data' => "serviceextendselect_" . $result['code_product']]
        ];
    }
    if ($nameloc['name_product'] == "🛍 حجم دلخواه" || $nameloc['name_product'] == "⚙️ سرویس دلخواه") {
        $productextend['inline_keyboard'][] = [
            ['text' => "📍 انتخاب سرویس فعلی", 'callback_data' => "serviceextendselect_pre"]
        ];
    }
    $productextend['inline_keyboard'][] = [
        ['text' => "🏠 بازگشت به اطلاعات سرویس", 'callback_data' => "product_" . $nameloc['id_invoice']]
    ];

    $json_list_product_lists = json_encode($productextend);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], $json_list_product_lists);
} elseif (preg_match('/^serviceextendselect_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuserforextend" || $datain == "exntedagei") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($user['step'] == "getvolumecustomuserforextend") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidtime'], $backuser, 'HTML');
            return;
        }
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
    } elseif ($datain == "exntedagei") {
        $stmt = $pdo->prepare("SELECT value FROM service_other WHERE username = :username AND type = 'extend_user' AND status = 'paid' ORDER BY time DESC");
        $stmt->execute([
            ':username' => $nameloc['username'],
        ]);
        if ($stmt->rowCount() == 0) {
            $codeproduct = select("product", "*", "name_product", $nameloc['name_product']);
        } else {
            $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($service_other == false || !(is_string($service_other['value']) && is_array(json_decode($service_other['value'], true)))) {
                sendmessage($from_id, "❌ امکان تمدید با پلن فعلی وجود ندارد  مراحل را از اول طی کرده و یک پلن دیگر انتخاب نمایید.", $keyboard, 'HTML');
                return;
            }
            $service_other = json_decode($service_other['value'], true);
            $codeproduct = select("product", "code_product", "code_product", $service_other['code_product'], "select");
        }
        if ($codeproduct == false) {
            sendmessage($from_id, "❌ امکان تمدید با پلن فعلی وجود ندارد  مراحل را از اول طی کرده و یک پلن دیگر انتخاب نمایید.", $keyboard, 'HTML');
            return;
        }
        $codeproduct = $codeproduct['code_product'];
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    } else {
        $codeproduct = $dataget[1];
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($user['step'] == "getvolumecustomuserforextend") {
        $product['name_product'] = $nameloc['name_product'];
        $product['code_product'] = "customvolume";
        $product['note'] = "";
        $product['price_product'] = (intval($userdate['volume']) * $custompricevalue) + ($text * $customtimevalueprice);
        $product['Service_time'] = $text;
        $product['Volume_constraint'] = $userdate['volume'];
        step("home", $from_id);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND code_product = :code_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':code_product' => $codeproduct,
        ]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($product == false) {
        sendmessage($from_id, "❌ خطایی رخ داده است مراحل تمدید را از اول انجام دهید.", $keyboard, 'HTML');
        return;
    }
    savedata("save", "time", $product['Service_time']);
    savedata("save", "data_limit", $product['Volume_constraint']);
    savedata("save", "price_product", $product['price_product']);
    savedata("save", "code_product", $product['code_product']);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivce"],
                ['text' => $textbotlang['users']['extend']['discount'], 'callback_data' => "discountextend"],
            ],
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]
            ]
        ]
    ]);
    if (intval($user['pricediscount']) != 0) {
        $result = ($product['price_product'] * $user['pricediscount']) / 100;
        $pricelastextend = number_format(round($product['price_product'] - $result, 0));
    } else {
        $pricelastextend = $product['price_product'];
    }
    $textextend = "📜 فاکتور تمدید شما برای نام کاربری {$nameloc['username']} ایجاد شد.
        
🛍 نام محصول :{$product['name_product']}
💸 مبلغ تمدید : $pricelastextend تومان
⏱ مدت زمان تمدید :{$product['Service_time']} روز
🔋 حجم تمدید :{$product['Volume_constraint']} گیگ
✍️ توضیحات : {$product['note']}
💸 موجودی کیف پول : {$user['Balance']}
✅ برای تایید و تمدید سرویس روی دکمه زیر کلیک کنید";
    if ($user['step'] == "getvolumecustomuserforextend") {
        sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    } else {
        Editmessagetext($from_id, $message_id, $textextend, $keyboardextend);
    }
} elseif ($datain == "discountextend") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcodesell'], $backuser, 'HTML');
    step('getcodesellDiscountextend', $from_id);
    deletemessage($from_id, $message_id);
} elseif ($user['step'] == "getcodesellDiscountextend") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if (!in_array($text, $SellDiscount)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM DiscountSell WHERE (code_product = :code_product OR code_product = 'all') AND (code_panel = :code_panel OR code_panel = '/all') AND codeDiscount = :codeDiscount AND (agent = :agent OR agent = 'allusers') AND (type = 'all' OR type = 'extend')");
    $stmt->bindParam(':code_product', $userdate['code_product'], PDO::PARAM_STR);
    $stmt->bindParam(':code_panel', $marzban_list_get['code_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $user['agent'], PDO::PARAM_STR);
    $stmt->bindParam(':codeDiscount', $text, PDO::PARAM_STR);
    $stmt->execute();
    $SellDiscountlimit = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if (intval($SellDiscountlimit['time']) != 0 and time() >= intval($SellDiscountlimit['time'])) {
        sendmessage($from_id, "❌ زمان کد تخفیف به پایان رسیده است.", null, 'HTML');
        return;
    }
    if ($SellDiscountlimit == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidcodedis'], null, 'HTML');
        return;
    }
    if (($SellDiscountlimit['limitDiscount'] <= $SellDiscountlimit['usedDiscount'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['erorrlimit'], null, 'HTML');
        return;
    }
    if (intval($Checkcodesql) >= $SellDiscountlimit['useuser']) {
        $textoncode = "⭕️ این کد تنها {$SellDiscountlimit['useuser']}  بار قابل استفاده است";
        sendmessage($from_id, $textoncode, $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($SellDiscountlimit['usefirst'] == "1") {
        $countinvoice = select("invoice", "*", "id_user", $from_id, "count");
        if ($countinvoice != 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['firstdiscount'], null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, "🤩 کد تخفیف شما درست بود و  {$SellDiscountlimit['price']} درصد تخفیف روی فاکتور شما اعمال شد.", $keyboard, 'HTML');
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($nameloc['name_product'] == "🛍 حجم دلخواه" || $nameloc['name_product'] == "⚙️ سرویس دلخواه") {
        $info_product['code_product'] = "pre";
        $info_product['name_product'] = $nameloc['name_product'];
        $info_product['price_product'] = ($userdate['data_limit'] * $custompricevalue) + ($userdate['time'] * $customtimevalueprice);
        $info_product['Service_time'] = $userdate['time'];
        $info_product['Volume_constraint'] = $userdate['data_limit'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :Location or Location = '/all') LIMIT 1");
        $stmt->bindParam(':code_product', $userdate['code_product'], PDO::PARAM_STR);
        $stmt->bindParam(':Location', $marzban_list_get['name_panel'], PDO::PARAM_STR);
        $stmt->execute();
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $result = ($SellDiscountlimit['price'] / 100) * $info_product['price_product'];
    $info_product['price_product'] = $info_product['price_product'] - $result;
    $info_product['price_product'] = round($info_product['price_product']);
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
    if ($info_product['price_product'] < 0)
        $info_product['price_product'] = 0;
    $textextend = "📜 فاکتور تمدید شما برای نام کاربری {$nameloc['username']} ایجاد شد.
        
🛍 نام محصول :{$info_product['name_product']}
💸 مبلغ تمدید :{$info_product['price_product']}
⏱ مدت زمان تمدید :{$info_product['Service_time']} روز
🔋 حجم تمدید :{$info_product['Volume_constraint']} گیگ
✍️ توضیحات : {$info_product['note']}
💸 موجودی کیف پول : {$user['Balance']}

✅ برای تایید و تمدید سرویس روی دکمه زیر کلیک کنید";
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserdiscount"],
            ]
        ]
    ]);
    sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    $parametrsendvalue = "dis_" . $text . "_" . $info_product['price_product'];
    update("user", "Processing_value_four", $parametrsendvalue, "id", $from_id);
    step("home", $from_id);
} elseif ($datain == "confirmserivce" || $datain == "confirmserdiscount") {
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $partsdic = explode("_", $user['Processing_value_four']);
    $userdata = json_decode($user['Processing_value'], true);
    $id_invoice = $userdata['id_invoice'];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, "❌ تمدید با خطا مواجه گردید مراحل تمدید را مجددا انجام دهید.", null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان تمدید در این پنل وجود ندارد", null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $randomString = bin2hex(random_bytes(2));
    if ($nameloc['name_product'] == "🛍 حجم دلخواه" || $nameloc['name_product'] == "⚙️ سرویس دلخواه") {
        $prodcut['code_product'] = "custom_volume";
        $prodcut['name_product'] = $nameloc['name_product'];
        $prodcut['price_product'] = ($userdata['data_limit'] * $custompricevalue) + ($userdata['time'] * $customtimevalueprice);
        $prodcut['Service_time'] = $userdata['time'];
        $prodcut['Volume_constraint'] = $userdata['data_limit'];
        $prodcut['inbounds'] = $marzban_list_get['inboundid'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND code_product = :code_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':code_product' => $userdata['code_product'],
        ]);
        $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $pricelastextend = $prodcut['price_product'];
    if ($prodcut == false || !in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, "❌ تمدید با خطا مواجه گردید مراحل تمدید را مجددا انجام دهید.", null, 'HTML');
        return;
    }
    if ($datain == "confirmserdiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1], "select");
        if ($SellDiscountlimit != false) {
            $pricelastextend = $partsdic[2];
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($pricelastextend * $user['pricediscount']) / 100;
        $pricelastextend = $pricelastextend - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($user['Balance'] < $pricelastextend && $user['agent'] != "n2" && intval($pricelastextend) != 0) {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']];
            $maxbalance = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']];
            $minbalance = number_format($minbalance);
            $maxbalance = number_format($maxbalance);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $Balance_prim = $pricelastextend - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            $stmt = $connect->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price,output,status) VALUES (?, ?,?, ?, ?,?,?,?)");
            $dateacc = date('Y/m/d H:i:s');
            $value = json_encode(array(
                "volumebuy" => $prodcut['Volume_constraint'],
                "Service_time" => $prodcut['Service_time'],
                "oldvolume" => $DataUserOut['data_limit'],
                "oldtime" => $DataUserOut['expire'],
                'code_product' => $prodcut['code_product'],
                'id_order' => $randomString
            ));
            $type = "extend_user";
            $status = "unpaid";
            $extend = '';
            $stmt->bind_param("ssssssss", $from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product'], $extend, $status);
            $stmt->execute();
            $stmt->close();
            update("user", "Processing_value_one", "{$nameloc['username']}%$randomString", "id", $from_id);
            update("user", "Processing_value_tow", "getextenduser", "id", $from_id);
            return;
        }
    }
    if ($datain == "confirmserdiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1], "select");
        if ($SellDiscountlimit != false) {
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[1]);
            $stmt = $connect->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (?,?)");
            $stmt->bind_param("ss", $from_id, $partsdic[1]);
            $stmt->execute();
            $text_report = "⭕️ یک کاربر با نام کاربری @$username  و آیدی عددی $from_id از کد تخفیف {$partsdic[1]} استفاده کرد. و سرویس خود را تمدید کررد.";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherreport,
                    'text' => $text_report,
                    'parse_mode' => "HTML"
                ]);
            }
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $pricelastextend) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    if ($nameloc['name_product'] == "سرویس تست") {
        update("invoice", "name_product", $prodcut['name_product'], "id_invoice", $nameloc['id_invoice']);
        update("invoice", "price_product", $prodcut['price_product'], "id_invoice", $nameloc['id_invoice']);
    }
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $nameloc['username'], $prodcut['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = "خطای تمدید سرویس
نام پنل : {$marzban_list_get['name_panel']}
نام کاربری سرویس : {$nameloc['username']}
دلیل خطا : {$extend['msg']}";
        sendmessage($from_id, "❌خطایی در تمدید سرویس رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    if ($user['agent'] == "f") {
        $valurcashbackextend = select("shopSetting", "*", "Namevalue", "chashbackextend", "select")['value'];
    } else {
        $valurcashbackextend = json_decode(select("shopSetting", "*", "Namevalue", "chashbackextend_agent", "select")['value'], true)[$user['agent']];
    }
    if (intval($valurcashbackextend) != 0 and intval($pricelastextend) != 0) {
        $result = ($prodcut['price_product'] * $valurcashbackextend) / 100;
        $pricelastextend = $pricelastextend - $result;
        sendmessage($from_id, "تبریک 🎉
📌 به عنوان هدیه تمدید مبلغ $result تومان حساب شما شارژ گردید", null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $pricelastextend;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $stmt = $connect->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price,output,status) VALUES (?, ?, ?, ?,?,?,?,?)");
    $dateacc = date('Y/m/d H:i:s');
    $value = json_encode(array(
        "volumebuy" => $prodcut['Volume_constraint'],
        "Service_time" => $prodcut['Service_time'],
        "oldvolume" => $DataUserOut['data_limit'],
        "oldtime" => $DataUserOut['expire'],
        'code_product' => $prodcut['code_product'],
        'id_order' => $randomString
    ));
    $type = "extend_user";
    $status = "paid";
    $extend_json = json_encode($extend);
    $stmt->bind_param("ssssssss", $from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product'],$extend_json, $status);
    $stmt->execute();
    $stmt->close();
    update("invoice", "Status", "active", "id_invoice", $id_invoice);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, "📌شما 2 امتیاز جدید کسب کردید.", null, 'html');
        $scorenew = $user['score'] + 2;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $keyboardextendfnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => "backorder"],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $priceproductformat = number_format($pricelastextend);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $textextend = "✅ تمدید برای سرویس شما با موفقیت صورت گرفت
 
▫️نام سرویس : {$nameloc['username']}
▫️نام محصول : {$prodcut['name_product']}
▫️مبلغ تمدید $priceproductformat تومان
";
    sendmessage($from_id, $textextend, $keyboardextendfnished, 'HTML');
    $timejalali = jdate('Y/m/d H:i:s');
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $text_report = "📣 جزئیات تمدید اکانت در ربات شما ثبت شد .
    
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :{$nameloc['username']}
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$nameloc['Service_location']}
▫️نام محصول : {$prodcut['name_product']}
▫️حجم محصول : {$prodcut['Volume_constraint']}
▫️زمان محصول : {$prodcut['Service_time']}
▫️مبلغ تمدید : {$prodcut['price_product']} تومان
▫️موجودی قبل از خرید : $balanceformatsellbefore تومان
▫️موجودی بعد از خرید : $balanceformatsell تومان
▫️زمان خرید : $timejalali";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
} elseif (preg_match('/changelink_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "disabled" || $DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ سرویس غیرفعال است و امکان تعویض لینک برای سرویس وجود ندارد.", null, 'html');
        return;
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['changelink']['confirm'], 'callback_data' => "confirmchange_" . $nameloc['id_invoice']],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['changelink']['warnchange'], $keyboardextend);
} elseif (preg_match('/confirmchange_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->Revoke_sub($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, '❌ خطایی در تغییر لینک رخ داده است.', null, 'HTML');
        return;
    }
    $textconfig = "✅ کانفیگ شما با موفقیت بروزرسانی گردید.";
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $DataUserOut['subscription_url'];
        $textconfig .= "اشتراک شما : <code>$output_config_link</code>";
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if ($marzban_list_get['config'] == "onconfig") {
        Editmessagetext($from_id, $message_id, $textconfig, keyboard_config($DataUserOut['configs'], $nameloc['id_invoice'], true));
    } else {
        Editmessagetext($from_id, $message_id, $textconfig, $bakinfos);
    }
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات تغییر لینک در ربات شما ثبت شد .
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :{$nameloc['username']}
▫️نام کاربر : $first_name
▫️موقعیت سرویس : {$marzban_list_get['name_panel']}
▫️نوع کاربر : {$user['agent']}
▫️زمان تغییر لینک : $timejalali";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML",
        ]);
    }
} elseif (preg_match('/Extra_volume_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان خرید حجم اضافه در این پنل وجود ندارد", null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $textextra = " ⭕️ مقدار حجمی که میخواهید خریداری کنید را ارسال کنید.
❌ مبلغ را به انگلیسی ارسال نمایید.
        ⚠️ هر گیگ  حجم اضافه $extrapricevalue تومان  است.";
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textextra, $bakinfos);
    step('getvolumeextra', $from_id);
} elseif ($user['step'] == "getvolumeextra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    if ($text < 1) {
        sendmessage($from_id, $textbotlang['users']['Extra_volume']['invalidprice'], $backuser, 'HTML');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextra = $extrapricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Extra_volume']['extracheck'], 'callback_data' => 'confirmaextra-' . $extrapricevalue * $text],
            ]
        ]
    ]);
    $priceextra = number_format($priceextra, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = "📜 فاکتور خرید حجم اضافه برای شما ایجاد شد.
        
📌 تعرفه هر گیگابایت حجم اضافه : $extrapricevalues تومان
🔋 حجم اضافه درخواستی : $text گیگابایت
💰 مبلغ فاکتور شما : $priceextra تومان
        
✅ جهت پرداخت و اضافه شدن حجم، روی دکمه زیر کلیک کنید";
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextra-(\w+)/', $datain, $dataget)) {
    $volume = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, "❌ خرید با خطا مواجه گردید مراحل را مجدد انجام  دهید.", null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    if ($user['Balance'] < $volume && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $valuevolume = intval($volume) / intval($extrapricevalue);
            if (intval($user['pricediscount']) != 0) {
                $result = ($volume * $user['pricediscount']) / 100;
                $volume = $volume - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $volume - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['None-credit'], $step_payment);
            step('get_step_payment', $from_id);
            update("user", "Processing_value_one", "{$nameloc['username']}%{$valuevolume}", "id", $from_id);
            update("user", "Processing_value_tow", "getextravolumeuser", "id", $from_id);
            return;
        }
    }
    deletemessage($from_id, $message_id);
    $volumepricelast = $volume;
    if (intval($user['pricediscount']) != 0) {
        $result = ($volume * $user['pricediscount']) / 100;
        $volumepricelast = $volume - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $volumepricelast) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $Balance_Low_user = $user['Balance'] - $volumepricelast;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $data_for_database = json_encode(array(
        'volume_value' => intval($volume) / intval($extrapricevalue),
        'priceـper_gig' => $extrapricevalue,
        'old_volume' => $DataUserOut['data_limit'],
        'expire_old' => $DataUserOut['expire']
    ));
    $data_limit = intval($volume) / intval($extrapricevalue);
    $extra_volume = $ManagePanel->extra_volume($nameloc['username'], $marzban_list_get['code_panel'], $data_limit);
    if ($extra_volume['status'] == false) {
        $extra_volume['msg'] = json_encode($extra_volume['msg']);
        $textreports = "خطای خرید حجم اضافه
نام پنل : {$marzban_list_get['name_panel']}
نام کاربری سرویس : {$nameloc['username']}
دلیل خطا : {$extra_volume['msg']}";
        sendmessage($from_id, "❌خطایی در خرید حجم اضافه سرویس رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $nameloc['username'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $volumepricelast,
        ':output' => json_encode($extra_volume),
    ]);
    $keyboardextrafnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, "📌شما 1 امتیاز جدید کسب کردید.", null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $volumesformat = number_format($volumepricelast, 0);
    $volumes = $volume / $extrapricevalue;
    $textvolume = "✅ افزایش حجم برای سرویس شما با موفقیت صورت گرفت
 
▫️نام سرویس  : {$nameloc['username']}
▫️حجم اضافه : $volumes گیگ

▫️مبلغ افزایش حجم : $volumesformat تومان";
    sendmessage($from_id, $textvolume, $keyboardextrafnished, 'HTML');
    $text_report = "⭕️ یک کاربر حجم اضافه خریده است
        
اطلاعات کاربر : 
🪪 آیدی عددی : $from_id
🛍 حجم خریداری شده  : $volumes گیگ
💰 مبلغ پرداختی : $volumesformat تومان
👤 نام کاربری کانفیگ : {$nameloc['username']}
موجودی کاربر قبل خرید : {$user['Balance']}
";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/changeloc_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    if ($user['limitchangeloc'] > $limitchangeloc['all'] and intval($setting['statuslimitchangeloc']) == 1) {
        sendmessage($from_id, "❌ محدودیت تغییر لوکیشن شما به پایان رسیده  است", null, 'html');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['changeloc'] == "offchangeloc") {
        sendmessage($from_id, "❌ این قابلیت درحال حاضر دردسترس نیست.", null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful" || $DataUserOut['status'] == "disabled") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    Editmessagetext($from_id, $message_id, $datatextbot['textselectlocation'], $list_marzban_panel_userschange);
} elseif (preg_match('/changelocselectlo-(\w+)/', $datain, $dataget)) {
    update("user", "Processing_value_one", $dataget[1], "id", $from_id);
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    $userlimitlast = $limitchangeloc['all'] - $user['limitchangeloc'];
    $userlimitlastfree = $limitchangeloc['free'] - $user['limitchangeloc'];
    if ($userlimitlastfree < 0)
        $userlimitlastfree = 0;
    $Pricechange = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['priceChangeloc'];
    $textchange = "📍 با  تایید کردن انتقال موقعیت سرویس شما در این موقعیت حذف و به موقعیت جدید منتقل خواهد شد.
💰 هزینه انتقال $Pricechange تومان می باشد
📌 محدودیت باقی مانده شما : $userlimitlast عدد (تعداد محدودیت رایگان باقی مانده :‌$userlimitlastfree عدد)

✅ برای تایید انتقال روی دکمه زیر کلیک کنید";
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['change-location']['confirm'], 'callback_data' => "confirmchangeloccha_" . $user['Processing_value']],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $user['Processing_value']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textchange, $keyboardextend);
} elseif (preg_match('/confirmchangeloccha_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $marzban_list_get_new = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    $limitfree = true;
    if ($user['limitchangeloc'] < $limitchangeloc['free'] and intval($setting['statuslimitchangeloc']) == 1) {
        $limitfree = false;
    }
    if ($user['limitchangeloc'] >= $limitchangeloc['all'] and intval($setting['statuslimitchangeloc']) == 1) {
        sendmessage($from_id, "❌ محدودیت تغییر لوکیشن شما به پایان رسیده  است", null, 'html');
        return;
    }
    if ($marzban_list_get_new['changeloc'] == "offchangeloc") {
        sendmessage($from_id, "❌ این قابلیت درحال حاضر دردسترس نیست.", null, 'html');
        return;
    }
    if ($marzban_list_get_new == false) {
        sendmessage($from_id, "❌ خطایی رخ داده است لطفا مراحل مجددا انجام دهید", null, 'html');
        return;
    }
    $Pricechange = $marzban_list_get_new['priceChangeloc'];
    if ($nameloc['name_product'] == "🛍 حجم دلخواه" || $nameloc['name_product'] == "⚙️ سرویس دلخواه") {
        $prodcut['code_product'] = "🛍 حجم دلخواه";
        $product['inbounds'] = null;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND name_product = :name_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            'name_product' => $nameloc['name_product']
        ]);
        $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($product['inbounds'] != null) {
        $marzban_list_get_new['inboundid'] = $prodcut['inbounds'];
    }
    if ($marzban_list_get_new['type'] == "Manualsale" && $marzban_list_get['url_panel'] == $marzban_list_get_new['url_panel']) {
        sendmessage($from_id, "❌ امکان انتقال به پنل وجود ندارد.", null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ کانفیگ شما در وضعیت استفاده نشده است و امکان انتقال موقعیت سرویس وجود ندارد.", null, 'html');
        return;
    }
    if ($DataUserOut['status'] != "active") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($limitfree == false) {
        $Pricechange = 0;
    }
    if ($user['Balance'] < $Pricechange && $user['agent'] != "n2" && $limitfree) {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($Pricechange * $user['pricediscount']) / 100;
                $Pricechange = $Pricechange - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            if (intval($Pricechange) != 0) {
                $Balance_prim = $Pricechange - $user['Balance'];
                update("user", "Processing_value", $Balance_prim, "id", $from_id);
                Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['None-credit'], $step_payment);
                step('get_step_payment', $from_id);
                return;
            }
        }
    }
    if (intval($user['pricediscount']) != 0 and intval($Pricechange) != 0) {
        $result = ($Pricechange * $user['pricediscount']) / 100;
        $Pricechange = $Pricechange - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $Pricechange) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $value = json_encode(array(
        "old_panel" => $marzban_list_get['code_panel'],
        "new_panel" => $marzban_list_get_new['code_panel'],
        "volume" => $DataUserOut['data_limit'],
        "used_traffic" => $DataUserOut['used_traffic'],
        "expire" => $DataUserOut['expire'],
        "stateus" => $DataUserOut['status']
    ));
    $stmt = $connect->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price) VALUES (?, ?, ?, ?,?,?)");
    $dateacc = date('Y/m/d H:i:s');
    $type = "change_location";
    $stmt->bind_param("ssssss", $from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product']);
    $stmt->execute();
    $stmt->close();
    if ($DataUserOut['data_limit'] == 0 || $DataUserOut['data_limit'] == null) {
        $data_limit = 0;
    } else {
        $data_limit = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    }
    $datac = array(
        'expire' => $DataUserOut['expire'],
        'data_limit' => $data_limit,
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'usertest'
    );
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    if ($marzban_list_get['url_panel'] == $marzban_list_get_new['url_panel']) {
        $remove = $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
        $dataoutput = $ManagePanel->createUser($marzban_list_get_new['name_panel'], "usertest", $DataUserOut['username'], $datac);
    } else {
        $dataoutput = $ManagePanel->createUser($marzban_list_get_new['name_panel'], "usertest", $DataUserOut['username'], $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['ErrorConfig'], $keyboard, 'HTML');
            $texterros = "خطا هنگام تغییر موقعیت سرویس
دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}
نام پنل مقصد : {$marzban_list_get_new['name_panel']}";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $remove = $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    }
    $output_config_link = "";
    if ($marzban_list_get_new['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get_new['config'] == "onconfig") {
        if (is_array($dataoutput['configs'])) {
            foreach ($dataoutput['configs'] as $configs) {
                $output_config_link .= "\n" . $configs;
            }
        }
    }
    $limitnew = $user['limitchangeloc'] + 1;
    update("user", "limitchangeloc", $limitnew, "id", $from_id);
    $textchangeloc = "✅ کانفیگ شما باموفقیت به سرور ({$marzban_list_get_new['name_panel']}) انتقال یافت.

🖥 نام سرویس : {$nameloc['username']}
💠 حجم سرویس : $RemainingVolume
⏳ زمان انقضا :  $expirationDate | $day 


🔗 لینک اشتراک شما: 

<code>$output_config_link</code>";
    if (intval($Pricechange) != 0) {
        $Balance_Low_user = $user['Balance'] - $Pricechange;
        update("user", "Balance", $Balance_Low_user, "id", $from_id);
    }
    update("invoice", "Service_location", $marzban_list_get_new['name_panel'], "username", $nameloc['username']);
    if ($marzban_list_get_new['inboundid'] != null) {
        update("invoice", "inboundid", $marzban_list_get_new['inboundid'], "username", $nameloc['username']);
    }
    Editmessagetext($from_id, $message_id, $textchangeloc, $keyboardextend);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $format_byte = formatBytes($data_limit);
    $textreport = "  
تغییر موقعیت سرویس 

🔻آیدی عددی : <code>$from_id</code>
🔻نام کاربری : @$username
🔻نام پنل قدیم : {$marzban_list_get['name_panel']}
🔻نام پنل جدید : {$marzban_list_get_new['name_panel']}
🔻 نام کاربری مشتری در پنل  :{$nameloc['username']}
🔻حجم نهایی سرویس : $format_byte
🔻موجودی کاربر : $balanceformatsell تومان";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $textreport,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/disorder-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    update("user", "Processing_value", $id_invoice, "id", $from_id);
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $textdisorder = "❓ علت اختلال خود را بنویسید

🔹 قبل از اینکه گزارشی ارسال بکنید آموزش های اتصال را مشاهده کنید. ( /help )";
    $keyboarddisorder = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textdisorder, $keyboarddisorder);
    step("getdesdisorder", $from_id);
} elseif ($user['step'] == "getdesdisorder") {
    update("user", "Processing_value", $text, "id", $from_id);
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $textdisorder = "❓ آیا از ارسال گزارش اختلال اطمینان دارید

🔹 قبل از اینکه گزارشی ارسال بکنید آموزش های اتصال را مشاهده کنید. ( /help )";
    $keyboarddisorder = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✅ تایید و ارسال گزارش اختلال", 'callback_data' => "confirmdisorders-" . $user['Processing_value']],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $user['Processing_value']],
            ]
        ]
    ]);
    sendmessage($from_id, $textdisorder, $keyboarddisorder, 'html');
    step("home", $from_id);
} elseif (preg_match('/confirmdisorders-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    $textdisorder = "
    ⚠️ یک کاربر با اطلاعات زیر یک گزارش اختلال در سرویس ثبت کرده است .

- نام کاربری : @$username
- آیدی عددی : $from_id
- نام کاربری کانفیگ : {$nameloc['username']}
- نام پلن تهیه شده : {$nameloc['name_product']}
- موقعیت سرویس : {$nameloc['Service_location']}
- توضیحات اختلال : {$user['Processing_value']}";
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = 'آنلاین';
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = 'آفلاین';
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
        } else {
            $lastonline = "متصل نشده";
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['on_hold'],
        'Unknown' => $textbotlang['users']['stateus']['Unknown'],
        'deactivev' => $textbotlang['users']['stateus']['disabled'],
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    #--------------[ subsupdate ]---------------#
    if ($DataUserOut['sub_updated_at'] !== null) {
        $sub_updated = $DataUserOut['sub_updated_at'];
        $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
        $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
    }
    if ($DataUserOut['data_limit'] != null && $DataUserOut['used_traffic'] != null) {
        $Percent = ($DataUserOut['data_limit'] - $DataUserOut['used_traffic']) * 100 / $DataUserOut['data_limit'];
    } else {
        $Percent = "100";
    }
    if ($Percent < 0)
        $Percent = -($Percent);
    $Percent = round($Percent, 2);
    $textdisorder .= "
  
 وضعیت سرویس : $status_var
        
🔋 حجم سرویس : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 فعال تا تاریخ : $expirationDate ($day)

لینک اشتراک کاربر : 
<code>{$DataUserOut['subscription_url']}</code>

📶 اخرین زمان اتصال  : $lastonline
🔄 اخرین زمان آپدیت لینک اشتراک  : $lastupdate
#️⃣ کلاینت متصل شده :<code>{$DataUserOut['sub_last_user_agent']}</code>";
    foreach ($admin_ids as $admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $admin, "select");
        if ($adminrulecheck['rule'] == "Seller")
            continue;
        sendmessage($admin, $textdisorder, $Response, 'html');
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_$id_invoice"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, "✅ با تشکر از ثبت درخواست ،درخواست شما  ارسال شده و درحال بررسی توسط پشتیبانی می باشد.", $bakinfos, 'html');
} elseif (preg_match('/Extra_time_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان خرید زمان اضافه در این پنل وجود ندارد", null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ هنوز به سرویس متصل نشده اید برای تمدید سرویس ابتدا به سرویس متصل شوید سپس اقدام به تمدید کنید", null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $textextra = "📆 تعداد روز اضافه مورد نظر را وارد کنید ( برحسب روز ) :
        
📌 تعرفه هر روز:  $extratimepricevalue";
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textextra, $bakinfos);
    step('gettimeextra', $from_id);
} elseif ($user['step'] == "gettimeextra") {
    if (!ctype_digit($text) || $text < 1) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidtime'], $backuser, 'HTML');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextratime = $extratimepricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Extra_time']['extratimecheck'], 'callback_data' => 'confirmaextratime-' . $extratimepricevalue * $text],
            ]
        ]
    ]);
    $priceextratime = number_format($priceextratime, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = "📜 فاکتور خرید زمان اضافه برای شما ایجاد شد.
        
📌 تعرفه هر روز زمان اضافه : $extratimepricevalue تومان
📆 تعداد روز اضافه درخواستی : $text روز
💰 مبلغ فاکتور شما : $priceextratime تومان
        
✅ جهت پرداخت و اضافه شدن زمان، روی دکمه زیر کلیک کنید";
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextratime-(\w+)/', $datain, $dataget)) {
    $tmieextra = $dataget[1];
    $pricelasttime = $tmieextra;
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, "❌ خرید با خطا مواجه گردید مراحل را مجدد انجام  دهید.", null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    if ($user['Balance'] < $tmieextra && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $valuetime = $tmieextra / $extratimepricevalue;
            if (intval($user['pricediscount']) != 0) {
                $result = ($tmieextra * $user['pricediscount']) / 100;
                $pricelasttime = $tmieextra - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            if (intval($pricelasttime) != 0) {
                $Balance_prim = $pricelasttime - $user['Balance'];
                update("user", "Processing_value", $Balance_prim, "id", $from_id);
                Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['None-credit'], $step_payment);
                step('get_step_payment', $from_id);
                update("user", "Processing_value_one", "{$nameloc['username']}%{$valuetime}", "id", $from_id);
                update("user", "Processing_value_tow", "getextratimeuser", "id", $from_id);
                return;
            }
        }
    }
    deletemessage($from_id, $message_id);
    if (intval($user['pricediscount']) != 0 and intval($pricelasttime) != 0) {
        $result = ($tmieextra * $user['pricediscount']) / 100;
        $pricelasttime = $tmieextra - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $pricelasttime;
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if ($Balance_Low_user < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    update("invoice", "Status", "active", "id_invoice", $nameloc['id_invoice']);
    $extratimeday = $tmieextra / $extratimepricevalue;
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $data_for_database = json_encode(array(
        'day' => $extratimeday,
        'priceـper_day' => $extratimeday,
        'old_volume' => $DataUserOut['data_limit'],
        'expire_old' => $DataUserOut['expire']
    ));
    $timeservice = $DataUserOut['expire'] - time();
    $day = floor($timeservice / 86400);
    $extra_time = $ManagePanel->extra_time($nameloc['username'], $marzban_list_get['code_panel'], $extratimeday);
    if ($extra_time['status'] == false) {
        $extra_time['msg'] = json_encode($extra_time['msg']);
        $textreports = "خطای خرید حجم اضافه
نام پنل : {$marzban_list_get['name_panel']}
نام کاربری سرویس : {$nameloc['username']}
دلیل خطا : {$extra_time['msg']}";
        sendmessage($from_id, "❌خطایی در خرید حجم اضافه سرویس رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_time_user";
    $output = json_encode($extra_time);
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $nameloc['username'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $pricelasttime,
        ':output' => $output,
    ]);
    $keyboardextrafnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, "📌شما 1 امتیاز جدید کسب کردید.", null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $volumesformat = number_format($tmieextra);
    $textextratime = "✅ افزایش زمان برای سرویس شما با موفقیت صورت گرفت
 
▫️نام سرویس : {$nameloc['username']}
▫️زمان اضافه : $extratimeday روز

▫️مبلغ افزایش زمان : $volumesformat تومان";
    sendmessage($from_id, $textextratime, $keyboardextrafnished, 'HTML');
    $volumes = $tmieextra / $extratimepricevalue;
    $text_report = "⭕️ یک کاربر زمان اضافه خریده است
        
اطلاعات کاربر : 
🪪 آیدی عددی : $from_id
🛍 زمان خریداری شده  : $volumes روز
💰 مبلغ پرداختی : $volumesformat تومان
👤 نام کاربری کانفیگ : {$nameloc['username']}";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/removeserviceuser_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    savedata("clear", "id_invoice", $id_invoice);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, "📌 دلیل حذف سرویس خود را ارسال کنید.", $bakinfos);
    step("getdisdeleteconfig", $from_id);
} elseif ($user['step'] == "getdisdeleteconfig") {
    $userdata = json_decode($user['Processing_value'], true);
    $id_invoice = $userdata['id_invoice'];
    savedata("save", "descritionsremove", $text);
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc['name_product'] == "سرویس تست") {
        sendmessage($from_id, $textbotlang['users']['stateus']['errorusertest'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['status']) && in_array($DataUserOut['status'], ["expired", "limited", "disabled"])) {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        step("home", $from_id);
        return;
    }
    $requestcheck = select("cancel_service", "*", "username", $nameloc['username'], "count");
    if ($requestcheck != 0) {
        sendmessage($from_id, $textbotlang['users']['stateus']['errorexits'], null, 'html');
        return;
    }
    $confirmremove = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✅  درخواست حذف سرویس را دارم", 'callback_data' => "confirmremoveservices-$id_invoice"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['stateus']['descriptions_removeservice'], $confirmremove, "html");
    step("home", $from_id);
} elseif (preg_match('/confirmremoveservices-(\w+)/', $datain, $dataget)) {
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("SELECT * FROM cancel_service WHERE id_user = :from_id AND status = 'waiting'");
    $stmt->execute([
        ':from_id' => $from_id
    ]);
    $checkcancelservicecount = $stmt->rowCount();
    if ($checkcancelservicecount != 0) {
        sendmessage($from_id, $textbotlang['users']['stateus']['exitsrequsts'], null, 'HTML');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $stmt = $connect->prepare("INSERT IGNORE INTO cancel_service (id_user, username,description,status) VALUES (?, ?, ?, ?)");
    $descriptions = "0";
    $Status = "waiting";
    $stmt->bind_param("ssss", $from_id, $nameloc['username'], $descriptions, $Status);
    $stmt->execute();
    $stmt->close();
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        sendmessage($from_id, $textbotlang['users']['stateus']['UserNotFound'], null, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['panelNotConnected'], null, 'html');
        step('home', $from_id);
        return;
    }
    #-------------status----------------#
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = 'آنلاین';
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = 'آفلاین';
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
        } else {
            $lastonline = "متصل نشده";
        }
    }
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['on_hold'],
        'Unknown' => $textbotlang['users']['stateus']['Unknown'],
        'deactivev' => $textbotlang['users']['stateus']['disabled'],

    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    #-----------------------------#
    $textinfoadmin = "سلام ادمین 👋
        
📌 یک درخواست حذف سرویس  توسط کاربر برای شما ارسال شده است. لطفا بررسی کرده و در صورت درست بودن و موافقت تایید کنید. 
        
        
📊 اطلاعات سرویس کاربر :
آیدی عددی کاربر : $from_id
نام کاربری کاربر : @$username
نام کاربری کانفیگ : {$nameloc['username']}
وضعیت سرویس : $status_var
موقعیت سرویس : {$nameloc['Service_location']}
کد سرویس:{$nameloc['id_invoice']}

🟢 اخرین زمان اتصال شما : $lastonline

📥 حجم مصرفی : $usedTrafficGb
♾ حجم سرویس : $LastTraffic
🪫 حجم باقی مانده : $RemainingVolume
📅 فعال تا تاریخ : $expirationDate ($day)


<b>❌ ادمین گرامی توجه داشته باشید دکمه حذف سرویس که میزنید ربات خودکار حساب میکند و احتمال اشتباه وجود دارد پیشنهاد می شود از  حذف دستی  استفاده نمایید</b>

دلیل حذف سرویس : {$userdata['descritionsremove']}";
    $confirmremoveadmin = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "❌حذف دستی", 'callback_data' => "remoceserviceadminmanual-{$nameloc['id_invoice']}"],
                ['text' => "❌حذف سرویس", 'callback_data' => "remoceserviceadmin-{$nameloc['id_invoice']}"],
                ['text' => "❌عدم تایید حذف", 'callback_data' => "rejectremoceserviceadmin-{$nameloc['id_invoice']}"],
            ],
        ]
    ]);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $textinfoadmin, $confirmremoveadmin, 'html');
        step("home", $admin);
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['stateus']['sendrequestsremove'], $keyboard, 'html');
} elseif (preg_match('/transfer_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc['name_product'] == "سرویس تست") {
        sendmessage($from_id, $textbotlang['Admin']['transfor']['transfornotvalid'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['status']) && in_array($DataUserOut['status'], ["expired", "limited", "disabled"])) {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['transfor']['discription'], $bakinfos);
    step("getidfortransfer", $from_id);
    update("user", "Processing_value_one", $nameloc['username'], "id", $from_id);
    update("user", "Processing_value_tow", $nameloc['id_invoice'], "id", $from_id);
} elseif ($user['step'] == "getidfortransfer") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['transfor']['notusertrns'], $backuser, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    $confirmtransfer = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✅ تایید انتقال سرویس", 'callback_data' => "confrimtransfers_{$user['Processing_value_tow']}"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['transfor']['confirm'], $confirmtransfer, 'HTML');
    step("home", $from_id);
} elseif (preg_match('/confrimtransfers_(\w+)/', $datain, $dataget)) {
    if ($from_id == $user['Processing_value_one']) {
        sendmessage($from_id, $textbotlang['Admin']['transfor']['notsendserviceyou'], $keyboard, 'HTML');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    update("invoice", "id_user", $user['Processing_value_one'], "id_invoice", $id_invoice);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "backorder"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['transfor']['confirmed'], $bakinfos);
    $texttransfer = "✅ کاربر گرامی  سرویس با نام کاربری {$nameloc['username']} از طرف کاربر با شناسه کاربری $from_id  به حساب کاربری شما منتقل گردید.";
    sendmessage($user['Processing_value_one'], $texttransfer, $keyboard, 'HTML');
    $stmt = $connect->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price) VALUES (?, ?, ?, ?,?,?)");
    $value = $user['Processing_value_one'];
    $dateacc = date('Y/m/d H:i:s');
    $type = "transfertouser";
    $price = "0";
    $stmt->bind_param("ssssss", $from_id, $nameloc['username'], $value, $type, $dateacc, $price);
    $stmt->execute();
    $stmt->close();
} elseif ($text == $datatextbot['text_usertest'] || $datain == "usertestbtn" || $text == "usertest") {
    if (!check_active_btn($setting['keyboardmain'], "text_usertest")) {
        sendmessage($from_id, "📌 سرویس تست در حال حاضر در دسترس نیست .", null, 'HTML');
        return;
    }
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
        return;
    }
    if ($locationproduct != 1) {
        if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
            sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
            step('get_number', $from_id);
        }
        if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
            return;
        if ($user['limit_usertest'] <= 0 && !in_array($from_id, $admin_ids)) {
            sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard_buy, 'html');
            return;
        }
        sendmessage($from_id, $datatextbot['textselectlocation'], $list_marzban_usertest, 'html');
    }
}
if ($user['step'] == "createusertest" || preg_match('/locationtest_(.*)/', $datain, $dataget) || ($text == $datatextbot['text_usertest'] || $datain == "usertestbtn" || $text == "usertest")) {
    if (!check_active_btn($setting['keyboardmain'], "text_usertest")) {
        sendmessage($from_id, "📌 سرویس تست در حال حاضر در دسترس نیست .", null, 'HTML');
        return;
    }
    $userlimit = select("user", "*", "id", $from_id, "select");
    if ($userlimit['limit_usertest'] <= 0 && !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard_buy, 'html');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 1) {
        $panel = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "select");
        if ($panel['hide_user'] != null) {
            $list_user = json_decode($panel['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
                return;
            }
        }
        $location = $panel['code_panel'];
    } else {
        if (isset($dataget[1])) {
            $location = $dataget[1];
        } else {
            if ($user['step'] != "createusertest") {
                return;
            } else {
                $location = $user['Processing_value_one'];
            }
        }
    }
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $location, "select");
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        if ($user['step'] != "createusertest") {
            step('createusertest', $from_id);
            update("user", "Processing_value_one", $location, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
            return;
        }
    } else {
        $name_panel = $location;
    }
    if ($user['step'] == "createusertest") {
        $name_panel = $user['Processing_value_one'];
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
    } else {
        deletemessage($from_id, $message_id);
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :codepanel AND codeproduct = :codeproduct AND status = 'active'");
        $value = "usertest";
        $stmt->bindParam(':codepanel', $marzban_list_get['code_panel']);
        $stmt->bindParam(':codeproduct', $value);
        $stmt->execute();
        $configexits = $stmt->rowCount();
        if (intval($configexits) == 0) {
            sendmessage($from_id, "❌ موجودی این سرویس به پایان رسیده.", null, 'HTML');
            return;
        }
    }
    $limit_usertest = $userlimit['limit_usertest'] - 1;
    update("user", "limit_usertest", $limit_usertest, "id", $from_id);
    $randomString = bin2hex(random_bytes(4));
    $text = strtolower($text);
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $name_panel, "select");
    $text = strtolower($text);
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $user['username'], $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", strtotime("+" . $marzban_list_get['time_usertest'] . "hours"))),
        'data_limit' => $marzban_list_get['val_usertest'] * 1048576,
        'from_id' => $from_id,
        'username' => $username_ac,
        'type' => 'usertest'
    );
    $date = time();
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $connect->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,notifctions) VALUES (?, ?,  ?, ?, ?, ?, ?,?,?,?,?)");
    $Status = "active";
    $info_product['name_product'] = "سرویس تست";
    $info_product['price_product'] = "0";
    $Status = "active";
    $stmt->bind_param("sssssssssss", $from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $info_product['name_product'], $info_product['price_product'], $marzban_list_get['val_usertest'], $marzban_list_get['time_usertest'], $Status, $notifctions);
    $stmt->execute();
    $stmt->close();
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], "usertest", $username_ac, $datac);
    if ($dataoutput['username'] == null) {
        $dataoutput['msg'] = json_encode($dataoutput['msg']);
        sendmessage($from_id, $textbotlang['users']['usertest']['errorcreat'], $keyboard, 'html');
        $texterros = "
⭕️ یک کاربر قصد دریافت اکانت  تست داشت که ساخت کانفیگ با خطا مواجه شده و به کاربر کانفیگ داده نشد
✍️ دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}";
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ]);
        }
        step('home', $from_id);
        update("invoice", "Status", "Unsuccessful", "id_invoice", $randomString);
        return;
    }
    $output_config_link = "";
    $config = "";
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
        for ($i = 0; $i < count($dataoutput['configs']); ++$i) {
            $config .= "\n" . $dataoutput['configs'][$i];
        }
    }

    $usertestinfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    if ($marzban_list_get['type'] == "WGDashboard") {
        $datatextbot['textaftertext'] = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  ساعت
🗜 حجم سرویس:  {volume} مگابایت

🧑‍🦯 شما میتوانید شیوه اتصال را  با فشردن دکمه زیر و انتخاب سیستم عامل خود را دریافت کنید";
    }
    $datatextbot['textaftertext'] = $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik" ? $datatextbot['textafterpayibsng'] : $datatextbot['textaftertext'];
    $textcreatuser = str_replace('{username}', $dataoutput['username'], $datatextbot['textaftertext']);
    $textcreatuser = str_replace('{name_service}', "تست", $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $marzban_list_get['time_usertest'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $marzban_list_get['val_usertest'], $textcreatuser);
    $textcreatuser = applyConnectionPlaceholders($textcreatuser, $output_config_link, $config);
    if ($marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $usertestinfo, $textcreatuser, $randomString);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    step('home', $from_id);
    if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "نام کاربری + عدد به ترتیب" || $marzban_list_get['MethodUsername'] == "آیدی عددی+عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات ساخت اکانت تست در ربات شما ثبت شد .
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :$username_ac
▫️نام کاربر : $first_name
▫️موقعیت سرویس : {$marzban_list_get['name_panel']}
▫️زمان خریداری شده : {$marzban_list_get['time_usertest']} ساعت
▫️حجم خریداری شده : {$marzban_list_get['val_usertest']} MB
▫️کد پیگیری: $randomString
▫️نوع کاربر : {$user['agent']}
▫️شماره تلفن کاربر : {$user['number']}
▫️زمان خرید : $timejalali";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reporttest,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
} elseif ($text == $datatextbot['text_help'] || $datain == "helpbtn" || $datain == "helpbtns" || $text == "/help" || $text == "help") {
    if (!check_active_btn($setting['keyboardmain'], "text_help")) {
        sendmessage($from_id, $textbotlang['users']['help']['disablehelp'], null, 'HTML');
        return;
    }
    if ($setting['categoryhelp'] == "1") {
        if ($datain == "helpbtns") {
            Editmessagetext($from_id, $message_id, "📌 یک دسته را انتخاب نمایید", $json_list_helpـcategory, 'HTML');
        } else {
            sendmessage($from_id, "📌 یک دسته را انتخاب نمایید", $json_list_helpـcategory, 'HTML');
        }
    } else {
        $helplist = select("help", "*", null, null, "fetchAll");
        $helpidos = ['inline_keyboard' => []];
        foreach ($helplist as $result) {
            $helpidos['inline_keyboard'][] = [
                ['text' => $result['name_os'], 'callback_data' => "helpos_{$result['id']}"]
            ];
        }
        if ($setting['linkappstatus'] == "1") {
            $helpidos['inline_keyboard'][] = [
                ['text' => "🔗 لینک دانلود برنامه", 'callback_data' => "linkappdownlod"],
            ];
        }
        $helpidos['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"],
        ];
        $json_list_help = json_encode($helpidos);
        if ($datain == "helpbtns") {
            Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
        } else {
            sendmessage($from_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
        }
    }
} elseif (preg_match('/^helpctgoryـ(.*)/', $datain, $dataget)) {
    $helplist = select("help", "*", "category", $dataget[1], "fetchAll");
    $helpidos = ['inline_keyboard' => []];
    foreach ($helplist as $result) {
        $helpidos['inline_keyboard'][] = [
            ['text' => $result['name_os'], 'callback_data' => "helpos_{$result['id']}"]
        ];
    }
    $helpidos['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "helpbtns"],
    ];
    $json_list_help = json_encode($helpidos);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
} elseif (preg_match('/^helpos_(.*)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "helpbtns"],
            ]
        ]
    ]);
    $helpid = $dataget[1];
    $helpdata = select("help", "*", "id", $helpid, "select");
    if ($helpdata !== false) {
        if (strlen($helpdata['Media_os']) != 0) {
            if ($helpdata['type_Media_os'] == "video") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendvideo', [
                    'chat_id' => $from_id,
                    'video' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            } elseif ($helpdata['type_Media_os'] == "document") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendDocument', [
                    'chat_id' => $from_id,
                    'document' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            } elseif ($helpdata['type_Media_os'] == "photo") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendphoto', [
                    'chat_id' => $from_id,
                    'photo' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            }
        } else {
            sendmessage($from_id, $helpdata['Description_os'], $backinfoss, 'HTML');
        }
    }
} elseif ($text == $datatextbot['text_support'] || $datain == "supportbtns" || $text == "/support") {
    if (!check_active_btn($setting['keyboardmain'], "text_support")) {
        sendmessage($from_id, "❌ این دکمه غیرفعال می باشد", null, 'HTML');
        return;
    }
    if ($datain == "supportbtns") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['support']['btnsupport'], $supportoption);
    } else {
        sendmessage($from_id, $textbotlang['users']['support']['btnsupport'], $supportoption, 'HTML');
    }
} elseif ($datain == "support") {
    Editmessagetext($from_id, $message_id, "📌 بخش پشتیبانی که میخواهید پیام دهید را انتخاب نمایید.", $list_departman, 'HTML');
} elseif (preg_match('/^departman_(.*)/', $datain, $dataget)) {
    $iddeparteman = $dataget[1];
    savedata("clear", "iddeparteman", $iddeparteman);
    deletemessage($from_id, $message_id);
    sendmessage($from_id, "📌 پیام خود را ارسال نمایید", $backuser, 'HTML');
    step("gettextticket", $from_id);
} elseif ($user['step'] == "gettextticket" && $text) {
    $userdata = json_decode($user['Processing_value'], true);
    $departeman = select("departman", "*", "id", $userdata['iddeparteman'], "select");
    $time = date('Y/m/d H:i:s');
    $timejalali = jdate('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT IGNORE INTO support_message (Tracking,idsupport,iduser,name_departman,text,time,status) VALUES (:Tracking,:idsupport,:iduser,:name_departman,:text,:time,:status)");
    $status = "Unseen";
    $stmt->bindParam(':Tracking', $randomString);
    $stmt->bindParam(':idsupport', $departeman['idsupport']);
    $stmt->bindParam(':iduser', $from_id);
    $stmt->bindParam(':name_departman', $departeman['name_departman']);
    $stmt->bindParam(':text', $text, PDO::PARAM_STR);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    if ($photo) {
        sendphoto($departeman['idsupport'], $photoid, null);
    }
    if ($video) {
        sendvideo($departeman['idsupport'], $videoid, null);
    }
    $textsuppoer = "
    📣 پشتیبان عزیز یک پیام از سمت کاربر برای شما ارسال گردید.

آیدی عددی کاربر : <a href = \"tg://user?id=$from_id\">$from_id</a>
زمان ارسال : $timejalali
وضعیت پیام : پاسخ داده نشده
نام کاربری کاربر : @$username    
نام دپارتمان : {$departeman['name_departman']}

متن پیام : $text $caption";
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesupport_' . $randomString],
            ],
        ]
    ]);
    sendmessage($departeman['idsupport'], $textsuppoer, $Response, 'HTML');
    sendmessage($from_id, "✅ پیام شما با موفقیت ارسال و پس از بررسی به شما پاسخ داده خواهد شد.", $keyboard, 'HTML');
    step("home", $from_id);
    step("home", $departeman['idsupport']);
} elseif (preg_match('/Responsesupport_(\w+)/', $datain, $dataget)) {
    $idtraking = $dataget[1];
    $trakingdetail = select("support_message", "*", "Tracking", $idtraking);
    if ($trakingdetail['status'] == "Answered") {
        sendmessage($from_id, "❌ پیام توسط ادمین دیگری پاسخ داده شده.", null, 'HTML');
        return;
    }
    sendmessage($from_id, "📌 متن پیام خود را ارسال نمایید", $backuser, 'HTML');
    update("user", "Processing_value", $idtraking, "id", $from_id);
    step("getextsupport", $from_id);
} elseif ($user['step'] == "getextsupport") {
    $trakingdetail = select("support_message", "*", "Tracking", $user['Processing_value']);
    $time = date('Y/m/d H:i:s');
    update("support_message", "status", "Answered", "Tracking", $user['Processing_value']);
    update("support_message", "result", $text, "Tracking", $user['Processing_value']);
    $textSendAdminToUser = "
📩 یک پیام از سمت مدیریت برای شما ارسال گردید.
                    
متن پیام : 
$text";
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesusera_' . $trakingdetail['Tracking']],
            ],
        ]
    ]);
    sendmessage($trakingdetail['iduser'], $textSendAdminToUser, $Response, 'HTML');
    sendmessage($from_id, "پیام با موفقیت ارسال گردید", null, 'HTML');
    step("home", $from_id);
} elseif (preg_match('/Responsesusera_(\w+)/', $datain, $dataget)) {
    $idtraking = $dataget[1];
    sendmessage($from_id, "📌 متن پیام خود را ارسال نمایید", $backuser, 'HTML');
    update("user", "Processing_value", $idtraking, "id", $from_id);
    step("getextuserfors", $from_id);
} elseif ($user['step'] == "getextuserfors") {
    $trakingdetail = select("support_message", "*", "Tracking", $user['Processing_value']);
    step("home", $from_id);
    $time = date('Y/m/d H:i:s');
    $timejalali = jdate('Y/m/d H:i:s');
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $randomString = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT IGNORE INTO support_message (Tracking,idsupport,iduser,name_departman,text,time,status) VALUES (:Tracking,:idsupport,:iduser,:name_departman,:text,:time,:status)");
    $status = "Customerresponse";
    $stmt->bindParam(':Tracking', $randomString);
    $stmt->bindParam(':idsupport', $trakingdetail['idsupport']);
    $stmt->bindParam(':iduser', $trakingdetail['iduser']);
    $stmt->bindParam(':name_departman', $trakingdetail['name_departman']);
    $stmt->bindParam(':text', $text, PDO::PARAM_STR);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $textsuppoer = "
    📣 پشتیبان عزیز یک پیام از سمت کاربر برای شما ارسال گردید.

آیدی عددی کاربر : <a href = \"tg://user?id=$from_id\">$from_id</a>
زمان ارسال : $timejalali
وضعیت پیام : پاسخ مشتری
نام کاربری کاربر : @$username    
نام دپارتمان : {$trakingdetail['name_departman']}

متن پیام : $text";
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesupport_' . $randomString],
            ],
        ]
    ]);
    if ($photo) {
        sendphoto($trakingdetail['idsupport'], $photoid, null);
    }
    if ($video) {
        sendvideo($trakingdetail['idsupport'], $videoid, null);
    }
    sendmessage($trakingdetail['idsupport'], $textsuppoer, $Response, 'HTML');
    sendmessage($from_id, "✅  پیام شما برای این درخواست با موفقیت ارسال گردید پس از بررسی پاسخ داده خواهد شد.", null, 'HTML');
} elseif ($datain == "fqQuestions") {
    sendmessage($from_id, $datatextbot['text_dec_fq'], null, 'HTML');
} elseif ($text == $datatextbot['accountwallet'] || $datain == "account" || $text == "/wallet") {
    $dateacc = jdate('Y/m/d');
    $current_time = time();
    $timeacc = jdate('H:i:s', $current_time);
    if (!is_string($user['codeInvitation']) || trim($user['codeInvitation']) === '') {
        $user['codeInvitation'] = ensureUserInvitationCode($from_id, $user['codeInvitation'] ?? null);
    }
    $first_name = htmlspecialchars($first_name);
    $Balanceuser = number_format($user['Balance'], 0);
    if ($user['number'] == "none") {
        $numberphone = "🔴 ارسال نشده است 🔴";
    } else {
        $numberphone = $user['number'];
    }
    if ($user['number'] == "confrim number by admin") {
        $numberphone = "✅ تایید شده توسط ادمین";
    } else {
        $numberphone = $numberphone;
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND name_product != 'سرویس تست' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->execute([
        ':id_user' => $from_id
    ]);
    $countorder = $stmt->rowCount();
    $stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid'");
    $stmt->execute([
        ':from_id' => $from_id
    ]);
    $countpayment = $stmt->rowCount();
    $groupuser = [
        'f' => "عادی",
        'n' => "نماینده",
        'n2' => "نمایندگی پیشرفته",
    ][$user['agent']];
    $userjoin = jdate('Y/m/d H:i:s', $user['register']);
    if (intval($setting['scorestatus']) == 1) {
        $textscore = "🥅 امتیاز حساب کاربری شما : {$user['score']}";
    } else {
        $textscore = "";
    }
    $textinvite = "";
    if ($setting['verifybucodeuser'] == "onverify" and $setting['verifystart'] == "onverify") {
        $textscore = "

🔗 لینک ریفرال جهت احراز زیر مجموعه :
https://t.me/$usernamebot?start={$user['codeInvitation']}";
    }
    $text_account = "
🗂 اطلاعات حساب کاربری شما :


🪪 شناسه کاربری: <code>$from_id</code>
👤 نام: <code>$first_name</code>
👨‍👩‍👦 کد معرف شما : <code>{$user['codeInvitation']}</code>
📱 شماره تماس :$numberphone
⌚️زمان ثبت نام : $userjoin
💰 موجودی: $Balanceuser تومان
🛒 تعداد سرویس های خریداری شده : $countorder عدد
📑 تعداد فاکتور های پرداخت شده :  : $countpayment عدد
🤝 تعداد زیر مجموعه های شما : {$user['affiliatescount']} نفر
🔖 گروه کاربری : $groupuser
$textscore
$textinvite

📆 $dateacc → ⏰ $timeacc
                    ";
    if ($datain == "account") {
        Editmessagetext($from_id, $message_id, $text_account, $keyboardPanel);
    } else {
        sendmessage($from_id, $text_account, $keyboardPanel, 'HTML');
    }
    step('home', $from_id);
    return;
} elseif (($text == $datatextbot['text_sell'] || $datain == "buy" || $datain == "buyback" || $text == "/buy" || $text == "buy") && $statusnote) {
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    if (!check_active_btn($setting['keyboardmain'], "text_sell")) {
        sendmessage($from_id, "❌ این دکمه غیرفعال می باشد", null, 'HTML');
        return;
    }
    if ($datain == "buy") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['notestep'], $backuser);
    } elseif ($datain == "buyback") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['sell']['notestep'], $backuser, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['sell']['notestep'], $backuser, 'HTML');
    }
    step("statusnamecustom", $from_id);
    return;
} elseif ($text == $datatextbot['text_sell'] || $datain == "buy" || $datain == "buybacktow" || $datain == "buyback" || $text == "/buy" || $text == "buy" || $user['step'] == "statusnamecustom") {
    if (!check_active_btn($setting['keyboardmain'], "text_sell")) {
        sendmessage($from_id, "❌ این دکمه غیرفعال می باشد", null, 'HTML');
        return;
    }
    $locationproduct = mysqli_query($connect, "SELECT * FROM marzban_panel  WHERE status = 'active'");
    if (mysqli_num_rows($locationproduct) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    #-----------------------#
    if (mysqli_num_rows($locationproduct) == 1) {
        $location = mysqli_fetch_assoc($locationproduct)['name_panel'];
        $locationproduct = select("marzban_panel", "*", "name_panel", $location, "select");
        if ($locationproduct['hide_user'] != null) {
            $list_user = json_decode($locationproduct['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
                return;
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE status = 'active' AND (status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
        $stmt->execute();
        $countinovoice = $stmt->rowCount();
        if ($locationproduct['limit_panel'] != "unlimited") {
            if ($countinovoice >= $locationproduct['limit_panel']) {
                sendmessage($from_id, $textbotlang['Admin']['managepanel']['limitedpanelfirst'], null, 'HTML');
                return;
            }
        }
        if ($user['step'] == "statusnamecustom") {
            savedata('clear', "nameconfig", $text);
            savedata('save', "name_panel", $location);
            step("home", $from_id);
        } else {
            savedata('clear', "name_panel", $location);
        }
        if ($setting['statuscategory'] == "offcategory") {
            $marzban_list_get = $locationproduct;
            $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
            $custompricevalue = $eextraprice[$user['agent']];
            $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
            $mainvolume = $mainvolume[$user['agent']];
            $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
            $maxvolume = $maxvolume[$user['agent']];
            $nullproduct = select("product", "*", null, null, "count");
            if ($nullproduct == 0) {
                $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
                sendmessage($from_id, $textcustom, $backuser, 'html');
                step('gettimecustomvol', $from_id);
                return;
            }
            if ($setting['statuscategorygenral'] == "oncategorys") {
                $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
                if ($setting['statusnamecustom'] == 'onnamecustom') {
                    $backuser = "buyback";
                } else {
                    $backuser = "backuser";
                }
                if ($datain == "buy") {
                    Editmessagetext($from_id, $message_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($location, $user['agent'], $backuser));
                } else {
                    sendmessage($from_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($location, $user['agent'], $backuser), 'HTML');
                }
            } else {
                $query = "SELECT * FROM product WHERE (Location = '$location' OR Location = '/all')";
                $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
                $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
                if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
                    $datakeyboard = "prodcutservices_";
                } else {
                    $datakeyboard = "prodcutservice_";
                }
                if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
                    $statuscustom = true;
                } else {
                    $statuscustom = false;
                }
                $textproduct = $textbotlang['users']['sell']['Service-select-first'];
                if ($datain == "buy") {
                    Editmessagetext($from_id, $message_id, $textproduct, KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom));
                } else {
                    sendmessage($from_id, $textproduct, KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom), 'HTML');
                }
            }
        } else {
            $nullproduct = select("product", "*", null, null, "count");
            if ($nullproduct == 0) {
                sendmessage($from_id, $textbotlang['Admin']['Product']['nullpProduct'], null, 'HTML');
                return;
            }
            $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
            $statuscustom = false;
            $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
            if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale")
                $statuscustom = true;
            if ($statusnote) {
                $back = "buyback";
            } else {
                $back = "backuser";
            }
            $monthkeyboard = keyboardTimeCategory($marzban_list_get['name_panel'], $user['agent'], "productmonth_", $back, $statuscustom, false);
            if ($datain == "buy" || $datain == "buybacktow") {
                Editmessagetext($from_id, $message_id, $textbotlang['Admin']['month']['title'], $monthkeyboard);
            } else {
                sendmessage($from_id, $textbotlang['Admin']['month']['title'], $monthkeyboard, 'HTML');
            }
        }
        return;
    }
    if ($user['step'] == "statusnamecustom") {
        savedata('clear', "nameconfig", $text);
        step("home", $from_id);
    }
    error_log($text);
    if ($datain == "buy" || $datain == "buybacktow" || $datain == "buyback") {
        Editmessagetext($from_id, $message_id, $datatextbot['textselectlocation'], $list_marzban_panel_user);
    } else {
        sendmessage($from_id, $datatextbot['textselectlocation'], $list_marzban_panel_user, 'HTML');
    }
} elseif (preg_match('/^location_(.*)/', $datain, $dataget) || $datain == "backproduct") {
    $userdate = json_decode($user['Processing_value'], true);
    if ($datain != "backproduct") {
        $location = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['name_panel'];
    } else {
        $location = $userdate['name_panel'];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $locationproductcount = select("marzban_panel", "*", "name_panel", $location, "count");
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND  Service_location = '{$marzban_list_get['name_panel']}'");
    $stmt->execute();
    $countinovoice = $stmt->rowCount();
    if ($marzban_list_get['limit_panel'] != "unlimited") {
        if ($countinovoice >= $marzban_list_get['limit_panel']) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['limitedpanel'], null, 'HTML');
            return;
        }
    }
    if ($statusnote) {
        savedata('save', "name_panel", $location);
    } else {
        savedata('clear', "name_panel", $location);
    }
    $nullproduct = select("product", "*", null, null, "count");
    if ($nullproduct == 0) {
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
        $mainvolume = $mainvolume[$user['agent']];
        $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
        $maxvolume = $maxvolume[$user['agent']];
        $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
        sendmessage($from_id, $textcustom, $backuser, 'html');
        step('gettimecustomvol', $from_id);
        return;
    }
    if ($setting['statuscategory'] == "offcategory") {
        if ($setting['statuscategorygenral'] == "oncategorys") {
            $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
            Editmessagetext($from_id, $message_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($location, $user['agent'], "buybacktow"));
        } else {
            $query = "SELECT * FROM product WHERE (Location = '$location' OR Location = '/all')";
            $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
            if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
                $datakeyboard = "prodcutservices_";
            } else {
                $datakeyboard = "prodcutservice_";
            }
            if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
                $statuscustom = true;
            } else {
                $statuscustom = false;
            }
            if (isset($userdate['nameconfig'])) {
                $back = "buybacktow";
            } else {
                $back = "buyback";
            }
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['Service-select'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, $back));
        }
    } else {
        $nullproduct = select("product", "*", null, null, "count");
        if ($nullproduct == 0) {
            sendmessage($from_id, $textbotlang['Admin']['Product']['nullpProduct'], null, 'HTML');
            return;
        }
        $statuscustom = false;
        $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
        if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale")
            $statuscustom = true;
        $monthkeyboard = keyboardTimeCategory($marzban_list_get['name_panel'], $user['agent'], "productmonth_", "buybacktow", $statuscustom, false);
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['month']['title'], $monthkeyboard);
    }
} elseif (preg_match('/^categorynames_(.*)/', $datain, $dataget)) {
    $categorynames = $dataget[1];
    $categorynames = select("category", "remark", "id", $categorynames, "select")['remark'];
    $userdate = json_decode($user['Processing_value'], true);
    if (isset($userdate['monthproduct'])) {
        $query = "SELECT * FROM product WHERE (Location = '{$userdate['name_panel']}' OR Location = '/all') AND category = '$categorynames' AND Service_time = '{$userdate['monthproduct']}'";
    } else {
        $query = "SELECT * FROM product WHERE (Location = '{$userdate['name_panel']}' OR Location = '/all') AND category = '$categorynames'";
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        $datakeyboard = "prodcutservices_";
    } else {
        $datakeyboard = "prodcutservice_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['Service-select-first'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom));
} elseif (preg_match('/^productmonth_(\w+)/', $datain, $dataget)) {
    $monthenumber = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel']);
    if ($setting['statuscategorygenral'] == "oncategorys") {
        savedata("save", "monthproduct", $monthenumber);
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel  WHERE status = 'active'");
        $stmt->execute();
        $count_panel = $stmt->rowCount();
        if ($count_panel == 1) {
            $back = "buybacktow";
        } else {
            $back = "location_{$marzban_list_get['code_panel']}";
        }
        Editmessagetext($from_id, $message_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($marzban_list_get['name_panel'], $user['agent'], $back));
    } else {
        $query = "SELECT * FROM product WHERE (Location = '{$userdate['name_panel']}' OR Location = '/all') AND Service_time = '$monthenumber'";
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
        if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
            $datakeyboard = "prodcutservices_";
        } else {
            $datakeyboard = "prodcutservice_";
        }
        if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
            $statuscustom = true;
        } else {
            $statuscustom = false;
        }
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['Service-select-first'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom));
    }
} elseif ($datain == "customsellvolume") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    deletemessage($from_id, $message_id);
    step('gettimecustomvol', $from_id);
} elseif ($user['step'] == "gettimecustomvol") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = "❌ حجم نامعتبر است.\n🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد";
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    update("user", "Processing_value_one", $text, "id", $from_id);
    $textcustom = "⌛️ زمان سرویس خود را انتخاب نمایید 
📌 تعرفه هر روز  : $customtimevalueprice  تومان
⚠️ حداقل زمان $maintime روز  و حداکثر $maxtime روز  می توانید تهیه کنید";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        step('getvolumecustomusername', $from_id);
    } else {
        step('getvolumecustomuser', $from_id);
    }
} elseif ($user['step'] == "getvolumecustomusername" || preg_match('/^prodcutservices_(.*)/', $datain, $dataget)) {
    $prodcut = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    if ($user['step'] == "getvolumecustomusername") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidtime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $customvalue = "customvolume_" . $text . "_" . $user['Processing_value_one'];
        update("user", "Processing_value_one", $customvalue, "id", $from_id);
        step('endstepusers', $from_id);
    } else {
        update("user", "Processing_value_one", $prodcut, "id", $from_id);
        step('endstepuser', $from_id);
        deletemessage($from_id, $message_id);
    }
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
} elseif ($user['step'] == "endstepuser" || $user['step'] == "endstepusers" || preg_match('/prodcutservice_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuser") {
    $userdate = json_decode($user['Processing_value'], true);
    if ($user['step'] == "getvolumecustomuser") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['Admin']['customvolume']['invalidtime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $prodcut = "customvolume_" . $text . "_" . $user['Processing_value_one'];
    } elseif ($user['step'] == "endstepusers" || $user['step'] == "endstepuser") {
        $prodcut = $user['Processing_value_one'];
    } else {
        $prodcut = $dataget[1];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, "❌ این پنل در دسترس نیست لطفا از پنل دیگری خرید را انجام دهید.", $backuser, 'html');
        step("home", $from_id);
        return;
    }
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
        $loc = $user['Processing_value_one'];
    } else {
        $loc = $prodcut;
    }
    update("user", "Processing_value_one", $loc, "id", $from_id);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $loc);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $info_product = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE code_product = '$loc' AND (Location = '{$userdate['name_panel']}'or Location = '/all') LIMIT 1"));
    }
    if (!isset($info_product['price_product'])) {
        sendmessage($from_id, "❌ خطایی در تایید  انجام شده است لطفا مراحل پرداخت را مجددا انجام دهید", $keyboard, 'HTML');
        return;
    }
    if (intval($user['pricediscount']) != 0) {
        $resultper = ($info_product['price_product'] * $user['pricediscount']) / 100;
        $info_product['price_product'] = $info_product['price_product'] - $resultper;
    }
    $randomString = bin2hex(random_bytes(2));
    $text = strtolower($text);
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username, $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    if (isset($username_ac))
        update("user", "Processing_value_tow", $username_ac, "id", $from_id);
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
    $info_product_price_product = number_format($info_product['price_product']);
    $userBalance = number_format($user['Balance']);
    $replacements = [
        '{username}' => $username_ac,
        '{name_product}' => $info_product['name_product'],
        '{Service_time}' => $info_product['Service_time'],
        '{note}' => $info_product['note'],
        '{price}' => $info_product_price_product,
        '{Volume}' => $info_product['Volume_constraint'],
        '{userBalance}' => $userBalance
    ];
    $textin = strtr($datatextbot['text_pishinvoice'], $replacements);
    if (intval($info_product['Volume_constraint']) == 0) {
        $textin = str_replace('گیگ', "", $textin);
    }
    if ($user['step'] != "getvolumecustomuser" && !in_array($marzban_list_get['MethodUsername'], ["نام کاربری دلخواه", "نام کاربری دلخواه + عدد رندوم"])) {
        Editmessagetext($from_id, $message_id, $textin, $payment);
    } else {
        sendmessage($from_id, $textin, $payment, 'HTML');
    }
    step('payment', $from_id);
} elseif ($user['step'] == "payment" && $datain == "confirmandgetservice" || $datain == "confirmandgetserviceDiscount") {
    $userdate = json_decode($user['Processing_value'], true);
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    // $pats for customm service
    $parts = explode("_", $user['Processing_value_one']);
    // $partsdic for discount value
    $partsdic = explode("_", $user['Processing_value_four']);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, "❌ این پنل در دسترس نیست لطفا از پنل دیگری خرید را انجام دهید.", $backuser, 'html');
        step("home", $from_id);
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['code_product'] = "customvolume";
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
        $info_product['data_limit_reset'] = "no_reset";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :location OR Location = '/all') LIMIT 1");
        $stmt->execute([
            ':code_product' => $user['Processing_value_one'],
            ':location' => $userdate['name_panel']
        ]);
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!isset($info_product['price_product']))
        return;
    if ($datain == "confirmandgetserviceDiscount") {
        $discountcode = select("DiscountSell", "*", "codeDiscount", $partsdic[0], "count");
        if ($discountcode == 0) {
            sendmessage($from_id, "❌ امکان خرید با این کد کد تخفیف وجود ندارد", null, 'HTML');
            return;
        }
        $priceproduct = $partsdic[1];
    } else {
        $priceproduct = $info_product['price_product'];
    }
    $username_ac = strtolower($user['Processing_value_tow']);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        sendmessage($from_id, "❌ لطفا مراحل خرید را مجددا انجام دهید", null, 'HTML');
        return;
    }
    $date = time();
    $randomString = bin2hex(random_bytes(4));
    $random_number = rand(1000000, 9999999);
    if (in_array($randomString, $id_invoice)) {
        $randomString = $random_number . $randomString;
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :codepanel AND codeproduct = :codeproduct AND status = 'active'");
        $stmt->bindParam(':codepanel', $marzban_list_get['code_panel']);
        $stmt->bindParam(':codeproduct', $info_product['code_product']);
        $stmt->execute();
        $configexits = $stmt->rowCount();
        if (intval($configexits) == 0) {
            sendmessage($from_id, "❌ موجودی این سرویس به پایان رسیده لطفا سرویسی دیگر را خریداری کنید.", null, 'HTML');
            return;
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($priceproduct * $user['pricediscount']) / 100;
        $priceproduct = $priceproduct - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $connect->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,note,refral,notifctions) VALUES (?,  ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)");
    $Status = "unpaid";
    $stmt->bind_param("sssssssssssss", $from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $info_product['name_product'], $priceproduct, $info_product['Volume_constraint'], $info_product['Service_time'], $Status, $userdate['nameconfig'], $user['affiliates'], $notifctions);
    $stmt->execute();
    $stmt->close();
    if ($priceproduct > $user['Balance'] && $user['agent'] != "n2" && intval($priceproduct) != 0) {
        $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        $Balance_prim = $priceproduct - $user['Balance'];
        if ($Balance_prim <= 1)
            $Balance_prim = 0;
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
        } else {
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            update("user", "Processing_value_one", $username_ac, "id", $from_id);
            update("user", "Processing_value_tow", "getconfigafterpay", "id", $from_id);
            if ($datain == "confirmandgetserviceDiscount")
                update("user", "Processing_value_four", "dis_{$partsdic[0]}", "id", $from_id);
        }
        return;
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (intval($user['Balance'] - $priceproduct) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    Editmessagetext($from_id, $message_id, "♻️ در حال ساختن سرویس شما...", null);
    if ($datain == "confirmandgetserviceDiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[0], "select");
        if ($SellDiscountlimit != false) {
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            $stmt = $connect->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (?,?)");
            $stmt->bind_param("ss", $from_id, $partsdic[0]);
            $stmt->execute();
            update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[0]);
            $text_report = "⭕️ یک کاربر با نام کاربری @$username  و آیدی عددی $from_id از کد تخفیف {$partsdic[0]} استفاده کرد.";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherreport,
                    'text' => $text_report,
                    'parse_mode' => "HTML"
                ]);
            }
        }
    }
    $datetimestep = strtotime("+" . $info_product['Service_time'] . "days");
    if ($info_product['Service_time'] == 0) {
        $datetimestep = 0;
    } else {
        $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
    }
    $datac = array(
        'expire' => $datetimestep,
        'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'buy'
    );
    $Shoppinginfo = [
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ];
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $info_product['code_product'], $username_ac, $datac);
    if (!isset($dataoutput['username']) || $dataoutput['username'] === null || $dataoutput['username'] === '') {
        $errorMessage = $dataoutput['msg'] ?? 'unknown error';
        if (is_array($errorMessage) || is_object($errorMessage)) {
            $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $errorMessage = (string) $errorMessage;
        }
        $dataoutput['msg'] = $errorMessage;
        sendmessage($from_id, $textbotlang['users']['sell']['ErrorConfig'], $keyboard, 'HTML');
        $texterros = "⭕️ خطای ساخت اشتراک
✍️ دلیل خطا :
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}";
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ]);
        }
        step('home', $from_id);
        return;
    }
    update("invoice", "Status", "active", "username", $username_ac);
    $output_config_link = "";
    $config = "";
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
        for ($i = 0; $i < count($dataoutput['configs']); ++$i) {
            $config .= "\n" . $dataoutput['configs'][$i];
        }
    }
    $Shoppinginfo = json_encode($Shoppinginfo);
    $datatextbot['textafterpay'] = $marzban_list_get['type'] == "Manualsale" ? $datatextbot['textmanual'] : $datatextbot['textafterpay'];
    $datatextbot['textafterpay'] = $marzban_list_get['type'] == "WGDashboard" ? $datatextbot['text_wgdashboard'] : $datatextbot['textafterpay'];
    $datatextbot['textafterpay'] = $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik" ? $datatextbot['textafterpayibsng'] : $datatextbot['textafterpay'];
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $datatextbot['textafterpay']);
    $textcreatuser = str_replace('{name_service}', $info_product['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $info_product['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $info_product['Volume_constraint'], $textcreatuser);
    $textcreatuser = applyConnectionPlaceholders($textcreatuser, $output_config_link, $config);
    if (intval($info_product['Volume_constraint']) == 0) {
        $textcreatuser = str_replace('گیگابایت', "", $textcreatuser);
    }
    if ($marzban_list_get['type'] == "Manualsale" || $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $Shoppinginfo, $textcreatuser, $randomString);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    if (intval($priceproduct) != 0) {
        $Balance_prim = $user['Balance'] - $priceproduct;
        update("user", "Balance", $Balance_prim, "id", $from_id);
    }
    if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "نام کاربری + عدد به ترتیب" || $marzban_list_get['MethodUsername'] == "آیدی عددی+عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != 'سرویس تست'  AND id_user = :id_user AND Status != 'Unpaid'");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $countinvoice = $stmt->rowCount();
    if ($affiliatescommission['status_commission'] == "oncommission" && ($user['affiliates'] != null && intval($user['affiliates']) != 0)) {
        if ($marzbanporsant_one_buy['porsant_one_buy'] == "on_buy_porsant") {
            if ($countinvoice == 1) {
                $result = ($priceproduct * $setting['affiliatespercentage']) / 100;
                $user_Balance = select("user", "*", "id", $user['affiliates'], "select");
                $Balance_prim = $user_Balance['Balance'] + $result;
                if (intval($setting['scorestatus']) == 1 and !in_array($user['affiliates'], $admin_ids)) {
                    sendmessage($user['affiliates'], "📌شما 2 امتیاز جدید کسب کردید.", null, 'html');
                    $scorenew = $user_Balance['score'] + 2;
                    update("user", "score", $scorenew, "id", $user['affiliates']);
                }
                update("user", "Balance", $Balance_prim, "id", $user['affiliates']);
                $result = number_format($result);
                $dateacc = date('Y/m/d H:i:s');
                $textadd = "🎁  پرداخت پورسانت 
        
        مبلغ $result تومان به حساب شما از طرف  زیر مجموعه تان به کیف پول شما واریز گردید";
                $textreportport = "
مبلغ $result به کاربر {$user['affiliates']} برای پورسانت از کاربر $from_id واریز گردید 
تایم : $dateacc";
                if (strlen($setting['Channel_Report'] ?? '') > 0) {
                    telegram('sendmessage', [
                        'chat_id' => $setting['Channel_Report'],
                        'message_thread_id' => $porsantreport,
                        'text' => $textreportport,
                        'parse_mode' => "HTML"
                    ]);
                }
                sendmessage($user['affiliates'], $textadd, null, 'HTML');
            }
        } else {

            $result = ($priceproduct * $setting['affiliatespercentage']) / 100;
            $user_Balance = select("user", "*", "id", $user['affiliates'], "select");
            $Balance_prim = $user_Balance['Balance'] + $result;
            if (intval($setting['scorestatus']) == 1 and !in_array($user['affiliates'], $admin_ids)) {
                sendmessage($user['affiliates'], "📌شما 2 امتیاز جدید کسب کردید.", null, 'html');
                $scorenew = $user_Balance['score'] + 2;
                update("user", "score", $scorenew, "id", $user['affiliates']);
            }
            update("user", "Balance", $Balance_prim, "id", $user['affiliates']);
            $result = number_format($result);
            $dateacc = date('Y/m/d H:i:s');
            $textadd = "🎁  پرداخت پورسانت 
        
        مبلغ $result تومان به حساب شما از طرف  زیر مجموعه تان به کیف پول شما واریز گردید";
            $textreportport = "
مبلغ $result به کاربر {$user['affiliates']} برای پورسانت از کاربر $from_id واریز گردید 
تایم : $dateacc";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $porsantreport,
                    'text' => $textreportport,
                    'parse_mode' => "HTML"
                ]);
            }
            sendmessage($user['affiliates'], $textadd, null, 'HTML');
        }
    }
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, "📌شما 1 امتیاز جدید کسب کردید.", null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $textonebuy = "";
    if ($countinvoice == 1) {
        $textonebuy = "📌 خرید اول کاربر";
    }
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات ساخت اکانت در ربات شما ثبت شد .

$textonebuy
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :$username_ac
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$userdate['name_panel']}
▫️نام محصول :{$info_product['name_product']}
▫️زمان خریداری شده :{$info_product['Service_time']} روز
▫️حجم خریداری شده : {$info_product['Volume_constraint']} GB
▫️موجودی قبل خرید : $balanceformatsellbefore تومان
▫️موجودی بعد خرید : $balanceformatsell تومان
▫️کد پیگیری: $randomString
▫️نوع کاربر : {$user['agent']}
▫️شماره تلفن کاربر : {$user['number']}
▫️دسته بندی محصول : {$info_product['category']}
▫️قیمت محصول : {$info_product['price_product']} تومان
▫️قیمت نهایی : $priceproduct تومان
▫️زمان خرید : $timejalali";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
    update("user", "Processing_value_four", "none", "id", $from_id);
    step('home', $from_id);
} elseif ($datain == "aptdc") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcodesell'], $backuser, 'HTML');
    step('getcodesellDiscount', $from_id);
    deletemessage($from_id, $message_id);
} elseif ($user['step'] == "getcodesellDiscount") {
    $userdate = json_decode($user['Processing_value'], true);
    if (!isset($userdate['name_panel'])) {
        sendmessage($from_id, "❌ مراحل خرید را مجددا از اول انجام دهید", $keyboard, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :Location or Location = '/all') LIMIT 1");
    $stmt->bindParam(':code_product', $user['Processing_value_one'], PDO::PARAM_STR);
    $stmt->bindParam(':Location', $userdate['name_panel'], PDO::PARAM_STR);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if (!in_array($text, $SellDiscount)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM DiscountSell WHERE (code_product = :code_product OR code_product = 'all') AND (code_panel = :code_panel OR code_panel = '/all') AND codeDiscount = :codeDiscount AND (agent = :agent OR agent = 'allusers') AND (type = 'all' OR type = 'buy')");
    $stmt->bindParam(':code_product', $info_product['code_product'], PDO::PARAM_STR);
    $stmt->bindParam(':code_panel', $marzban_list_get['code_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $user['agent'], PDO::PARAM_STR);
    $stmt->bindParam(':codeDiscount', $text, PDO::PARAM_STR);
    $stmt->execute();
    $SellDiscountlimit = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if ($SellDiscountlimit == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidcodedis'], null, 'HTML');
        return;
    }
    if (intval($SellDiscountlimit['time']) != 0 and time() >= intval($SellDiscountlimit['time'])) {
        sendmessage($from_id, "❌ زمان کد تخفیف به پایان رسیده است.", null, 'HTML');
        return;
    }
    if (($SellDiscountlimit['limitDiscount'] <= $SellDiscountlimit['usedDiscount'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['erorrlimit'], null, 'HTML');
        return;
    }
    if ($Checkcodesql >= $SellDiscountlimit['useuser']) {
        $textoncode = "⭕️ این کد تنها {$SellDiscountlimit['useuser']}  بار قابل استفاده است";
        sendmessage($from_id, $textoncode, $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($SellDiscountlimit['usefirst'] == "1") {
        $countinvoice = mysqli_query($connect, "SELECT * FROM invoice WHERE id_user = '$from_id' AND name_product != 'سرویس تست' AND  (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
        if (mysqli_num_rows($countinvoice) != 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['firstdiscount'], null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, "🤩 کد تخفیف شما درست بود و  {$SellDiscountlimit['price']} درصد تخفیف روی فاکتور شما اعمال شد.", null, 'HTML');
    step('payment', $from_id);
    $parts = explode("_", $user['Processing_value_one']);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $info_product = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE code_product = '{$user['Processing_value_one']}' AND (Location = '{$userdate['name_panel']}'or Location = '/all') LIMIT 1"));
    }
    $result = ($SellDiscountlimit['price'] / 100) * $info_product['price_product'];

    $info_productmain = $info_product['price_product'];
    $info_product['price_product'] = $info_product['price_product'] - $result;
    $info_product['price_product'] = round($info_product['price_product']);
    if ($info_product['Service_time'] == 0)
        $info_product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
    if ($info_product['price_product'] < 0)
        $info_product['price_product'] = 0;
    $textin = "
📇 پیش فاکتور شما:
👤 نام کاربری: <code>{$user['Processing_value_tow']}</code>
🔐 نام سرویس: {$info_product['name_product']}
📆 مدت اعتبار: {$info_product['Service_time']} روز
💶 قیمت اصلی : <del>$info_productmain تومان</del>
💶 قیمت با تخفیف: {$info_product['price_product']}  تومان
👥 حجم اکانت: {$info_product['Volume_constraint']} گیگ
💵 موجودی کیف پول شما : {$user['Balance']}
                  
        💰 سفارش شما آماده پرداخت است.  ";
    $paymentDiscount = json_encode([
        'inline_keyboard' => [
            [['text' => "💰 پرداخت و دریافت سرویس", 'callback_data' => "confirmandgetserviceDiscount"]],
            [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]]
        ]
    ]);
    $parametrsendvalue = $text . "_" . $info_product['price_product'];
    update("user", "Processing_value_four", $parametrsendvalue, "id", $from_id);
    sendmessage($from_id, $textin, $paymentDiscount, 'HTML');
} elseif ($text == "🗂 خرید انبوه" || $datain == "kharidanbuh") {
    if ($setting['bulkbuy'] == "offbulk") {
        sendmessage($from_id, "❌ این بخش در حال غیرفعال می باشد", null, 'HTML');
        return;
    }
    $PaySetting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM shopSetting WHERE Namevalue = 'minbalancebuybulk'"))['value'];
    if ($user['Balance'] < $PaySetting) {
        sendmessage($from_id, "❌ برای خرید انبوه باید حداقل $PaySetting تومان موجودی داشته باشید.", null, 'HTML');
        return;
    }
    $locationproduct = mysqli_query($connect, "SELECT * FROM marzban_panel");
    if (mysqli_num_rows($locationproduct) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    #-----------------------#
    if ($datain == "kharidanbuh") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Major']['title'], $backuser, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['Major']['title'], $backuser, 'HTML');
    }
    step('getcountconfig', $from_id);
} elseif ($user['step'] == "getcountconfig") {
    if (intval($text) > 15 || intval($text) < 1)
        return sendmessage($from_id, $textbotlang['Admin']['agent']['invalidvlue'], $backuser, 'HTML');
    if (!is_numeric($text))
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorprice'], null, 'HTML');
    sendmessage($from_id, $datatextbot['textselectlocation'], $list_marzban_panel_userom, 'HTML');
    update("user", "Processing_value_four", $text, "id", $from_id);
    step('home', $from_id);
} elseif (preg_match('/^locationom_(.*)/', $datain, $dataget)) {
    $location = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['name_panel'];
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $dataget[1], "select");
    $nullproduct = select("product", "*", null, null, "count");
    if ($nullproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['nullpProduct'], null, 'HTML');
        return;
    }
    update("user", "Processing_value", $location, "id", $from_id);
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        $datakeyboard = "prodcutservicesom_";
    } else {
        $datakeyboard = "prodcutserviceom_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    $query = "SELECT * FROM product WHERE (Location = '$location' OR Location = '/all')";
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['Service-select'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolumeom"));
} elseif ($datain == "customsellvolumeom") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $textcustom = "🔋 لطفا مقدار حجم سرویس مورد نظر را وارد کنید ( برحسب گیگابایت ) :
📌 تعرفه هر گیگ :  $custompricevalue 
🔔 حداقل حجم 1 گیگابایت و حداکثر 1000 گیگابایت می باشد.";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    deletemessage($from_id, $message_id);
    step('gettimecustomvolom', $from_id);
} elseif ($user['step'] == "gettimecustomvolom") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = "❌ حجم نامعتبر است.\n🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد";
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    $textcustom = "⌛️ زمان سرویس خود را انتخاب نمایید 
📌 تعرفه هر روز  : $customtimevalueprice  تومان
⚠️ حداقل زمان $maintime روز  و حداکثر $maxtime روز  می توانید تهیه کنید";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        step('getvolumecustomusernameom', $from_id);
    } else {
        step('getvolumecustomuserom', $from_id);
    }
} elseif ($user['step'] == "getvolumecustomusernameom" || preg_match('/^prodcutservicesom_(.*)/', $datain, $dataget)) {
    $prodcut = $dataget[1];
    if ($user['step'] == "getvolumecustomusernameom") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['Admin']['customvolume']['invalidtime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $customvalue = "customvolume_" . $text . "_" . $user['Processing_value_one'];
        update("user", "Processing_value_one", $customvalue, "id", $from_id);
        step('endstepusersom', $from_id);
    } else {
        update("user", "Processing_value_one", $prodcut, "id", $from_id);
        step('endstepuserom', $from_id);
    }
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
} elseif ($user['step'] == "endstepuserom" || $user['step'] == "endstepusersom" || preg_match('/prodcutserviceom_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuserom") {
    if ($user['step'] == "getvolumecustomuserom") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['Admin']['customvolume']['invalidtime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > $maxtime || intval($text) < $maintime) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $prodcut = "customvolume_" . $text . "_" . $user['Processing_value_one'];
    } elseif ($user['step'] == "endstepusersom" || $user['step'] == "endstepuserom") {
        $prodcut = $user['Processing_value_one'];
    } else {
        $prodcut = $dataget[1];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
        $loc = $user['Processing_value_one'];
    } else {
        $loc = $prodcut;
    }
    update("user", "Processing_value_one", $loc, "id", $from_id);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $loc);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $info_product = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE code_product = '$loc' AND (Location = '{$user['Processing_value']}'or Location = '/all') LIMIT 1"));
    }
    $randomString = bin2hex(random_bytes(2));
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username, $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    update("user", "Processing_value_tow", $username_ac, "id", $from_id);
    if ($info_product['Volume_constraint'] == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
    if ($info_product['Service_time'] == 0)
        $info_product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
    $info_product['price_product'] = intval($info_product['price_product']) * intval($user['Processing_value_four']);
    $price_product_format = number_format($info_product['price_product']);
    $userbalancepish = number_format($user['Balance']);
    $textin = "
📇 پیش فاکتور شما:
👤 نام کاربری: <code>$username_ac</code>
🔐 نام سرویس: {$info_product['name_product']}
📆 مدت اعتبار: {$info_product['Service_time']} روز
💶 قیمت: $price_product_format  تومان
👥 حجم اکانت: {$info_product['Volume_constraint']} گیگ
💵 موجودی کیف پول شما : $userbalancepish
⭕️تعداد کانفیگ : {$user['Processing_value_four']}
                  
💰 سفارش شما آماده پرداخت است.  ";
    sendmessage($from_id, $textin, $paymentom, 'HTML');
    step('payments', $from_id);
} elseif ($user['step'] == "payments" && $datain == "confirmandgetservice") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $user['Processing_value_one']);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customsellvolume']['title'];
        $info_product['code_product'] = "customvolume";
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
        $info_product['data_limit_reset'] = "no_reset";
    } else {
        $info_product = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE code_product = '{$user['Processing_value_one']}' AND (Location = '{$user['Processing_value']}'  or Location = '/all') LIMIT 1"));
    }
    if (empty($info_product['price_product']) || empty($info_product['price_product']))
        return;
    $priceproduct = $info_product['price_product'] * $user['Processing_value_four'];
    Editmessagetext($from_id, $message_id, $text_inline, null);
    $username_ac = $user['Processing_value_tow'];
    $date = time();
    if (intval($user['pricediscount']) != 0) {
        $result = ($priceproduct * $user['pricediscount']) / 100;
        $priceproduct = $priceproduct - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    if ($priceproduct > $user['Balance'] && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $Balance_prim = $priceproduct - $user['Balance'];
            $Balance_prims = $user['Balance'] - $priceproduct;
            if ($Balance_prims <= 1)
                $Balance_prims = 0;
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $priceproduct) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $datep = strtotime("+" . $info_product['Service_time'] . "days");
    if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "نام کاربری + عدد به ترتیب" || $marzban_list_get['MethodUsername'] == "آیدی عددی+عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
        $value = intval($user['number_username']) + $user['Processing_value_four'];
        update("user", "number_username", $value, "id", $from_id);
        if ($marzban_list_get['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $marzban_list_get['MethodUsername'] == "متن دلخواه نماینده + عدد ترتیبی") {
            $value = intval($setting['numbercount']) + $user['Processing_value_four'];
            update("setting", "numbercount", $value);
        }
    }
    if ($info_product['Service_time'] == 0) {
        $datep = 0;
    } else {
        $datep = strtotime(date("Y-m-d H:i:s", $datep));
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", $datep)),
        'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'buyomdh'
    );
    if ($info_product['inbounds'] != null) {
        $marzban_list_get['inboundid'] = $info_product['inbounds'];
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    for ($i = 0; $i < $user['Processing_value_four']; $i++) {
        $random_number = rand(1000000, 9999999);
        $username_acc = $username_ac . "_" . $i;
        $get_username_Check = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_acc);
        if (isset($get_username_Check['username']) || in_array($username_acc, $usernameinvoice)) {
            $username_acc = $random_number . "_" . $username_acc;
        }
        $randomString = bin2hex(random_bytes(4));
        if (in_array($randomString, $id_invoice)) {
            $randomString = $random_number . $randomString;
        }
        $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $info_product['code_product'], $username_acc, $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['ErrorConfig'], $keyboard, 'HTML');
            $texterros = "
⭕️ خطا در ساخت اکانت در بخش انبوه
✍️ دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            step('home', $from_id);
            return;
        }
        $stmt = $connect->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?,?,?,?,?)");
        $Status = "active";
        $stmt->bind_param("sssssssssss", $from_id, $randomString, $username_acc, $date, $user['Processing_value'], $info_product['name_product'], $info_product['price_product'], $info_product['Volume_constraint'], $info_product['Service_time'], $Status, $notifctions);
        $stmt->execute();
        $stmt->close();
        $config = "";
        $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
        if ($marzban_list_get['config'] == "onconfig") {
            if (is_array($dataoutput['configs'])) {
                foreach ($dataoutput['configs'] as $configs) {
                    $config .= "\n" . $configs;
                }
            }
        }
        $datatextbot['textafterpay'] = $marzban_list_get['type'] == "Manualsale" ? $datatextbot['textmanual'] : $datatextbot['textafterpay'];
        if ($marzban_list_get['type'] == "WGDashboard") {
            $datatextbot['textafterpay'] = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  روز
🗜 حجم سرویس:  {volume} گیگابایت

🧑‍🦯 شما میتوانید شیوه اتصال را  با فشردن دکمه زیر و انتخاب سیستم عامل خود را دریافت کنید";
        }
        $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $datatextbot['textafterpay']);
        $textcreatuser = str_replace('{name_service}', $info_product['name_product'], $textcreatuser);
        $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
        $textcreatuser = str_replace('{day}', $info_product['Service_time'], $textcreatuser);
        $textcreatuser = str_replace('{volume}', $info_product['Volume_constraint'], $textcreatuser);
        $textcreatuser = applyConnectionPlaceholders($textcreatuser, $output_config_link, $config);
        sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $Shoppinginfo, $textcreatuser, $randomString);
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    $user_Balance = select("user", "*", "id", $from_id, "select");
    $Balance_prim = $user_Balance['Balance'] - $priceproduct;
    update("user", "Balance", $Balance_prim, "id", $from_id);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $pricebulk = $info_product['price_product'] * intval($user['Processing_value_four']);
    $count_service = $user['Processing_value_four'];
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات ساخت اکانت انبوه در ربات شما ثبت شد .
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :{$username_ac}_0-$count_service
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$user['Processing_value']}
▫️نام محصول :{$info_product['name_product']}
▫️زمان خریداری شده :{$info_product['Service_time']} روز
▫️حجم خریداری شده : {$info_product['Volume_constraint']} GB
▫️موجودی قبل خرید : $balanceformatsellbefore تومان
▫️موجودی بعد خرید : $balanceformatsell تومان
▫️کد پیگیری: $randomString
▫️نوع کاربر : {$user['agent']}
▫️شماره تلفن کاربر : {$user['number']}
▫️قیمت محصول : {$info_product['price_product']} تومان
▫️قیمت نهایی : {$info_product['price_product']} تومان
▫️تعداد کانفیگ : {$user['Processing_value_four']} عدد
▫️زمان خرید : $timejalali";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    step('home', $from_id);
} elseif ($datain == "Add_Balance") {
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    step('home', $from_id);
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
    $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, "💸 مبلغ را  به تومان وارد کنید:
✅  حداقل مبلغ $minbalance حداکثر مبلغ $maxbalance تومان می باشد", $bakinfos, 'HTML');
    step('getprice', $from_id);
    update("user", 'Processing_value', $message_id, "id", $from_id);
} elseif ($user['step'] == "getprice") {
    deletemessage($from_id, $user['Processing_value']);
    if (!is_numeric($text))
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorprice'], null, 'HTML');
    $minbalance = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']];
    $maxbalance = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']];
    $balancelast = $text;
    if ($text > $maxbalance or $text < $minbalance) {
        $minbalance = number_format($minbalance);
        $maxbalance = number_format($maxbalance);
        sendmessage($from_id, "❌ خطا 
💬 مبلغ باید حداقل $minbalance تومان و حداکثر $maxbalance تومان باشد", null, 'HTML');
        return;
    }
    if ($user['Balance'] < 0 and intval($setting['Debtsettlement']) == 1) {
        $balancruser = abs($user['Balance']);
        if ($text < $balancruser) {
            sendmessage($from_id, "❌ شما بدهی دارید، باید حداقل $balancruser تومان پرداخت کنید.
         میبغ خود را مجددا ارسال نمایید", null, 'HTML');
            return;
        }
    }
    update("user", "Processing_value", $balancelast, "id", $from_id);
    sendmessage($from_id, $textbotlang['users']['Balance']['selectPatment'], $step_payment, 'HTML');
    step('get_step_payment', $from_id);
} elseif ($user['step'] == "get_step_payment") {
    if ($datain == "cart_to_offline") {
        $PaySetting = select("PaySetting", "ValuePay", "NamePay", "statuscardautoconfirm", "select")['ValuePay'];
        $checkpay = mysqli_query($connect, "SELECT * FROM Payment_report WHERE id = '$from_id' AND payment_Status = 'Unpaid'");
        if (mysqli_num_rows($checkpay) != 0) {
            sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['issetpay'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancecart", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancecart", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalance و حداکثر $maxbalance تومان باشد", null, 'HTML');
            return;
        }
        $cardQuery = mysqli_query($connect, "SELECT * FROM card_number  ORDER BY RAND() LIMIT 1");
        if ($cardQuery === false) {
            error_log('Failed to fetch card_number data: ' . mysqli_error($connect));
            sendmessage($from_id, "❌ خطای داخلی در بازیابی کارت بانکی رخ داد. لطفاً بعداً تلاش کنید.", null, 'HTML');
            return;
        }

        $card_info = mysqli_fetch_assoc($cardQuery);
        if (!$card_info || empty($card_info['cardnumber']) || empty($card_info['namecard'])) {
            sendmessage($from_id, "❌ کارت بانکی فعالی برای این روش پرداخت یافت نشد. لطفاً بعداً تلاش کنید یا با پشتیبانی تماس بگیرید.", null, 'HTML');
            mysqli_free_result($cardQuery);
            return;
        }

        $card_number = $card_info['cardnumber'];
        $PaySettingname = $card_info['namecard'];
        mysqli_free_result($cardQuery);
        $price_copy = $user['Processing_value'];
        if ($PaySetting == "onautoconfirm") {
            $random_number = rand(0, 2000);
            $user['Processing_value'] = intval($user['Processing_value']) + $random_number;
            if (in_array($user['Processing_value'], $pricepayment)) {
                $random_number = rand(0, 2000);
                $user['Processing_value'] = intval($user['Processing_value']) + $random_number;
            }
            $valueshow = "{$user['Processing_value']}0";
            $replacements = [
                '{price}' => $valueshow,
                '{card_number}' => $card_number,
                '{name_card}' => $PaySettingname,
            ];
            $price_copy = $valueshow;
            $textcart = strtr($datatextbot['text_cart_auto'], $replacements);
            update("user", "Processing_value", $user['Processing_value'], "id", $from_id);
        } else {
            $valueprice = number_format($user['Processing_value']);
            $replacements = [
                '{price}' => $valueprice,
                '{card_number}' => $card_number,
                '{name_card}' => $PaySettingname,
            ];
            $price_copy = intval($user['Processing_value'] . "0");
            $textcart = strtr($datatextbot['text_cart'], $replacements);
        }
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "cart to cart";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        deletemessage($from_id, $message_id);
        if ($setting['statuscopycart'] == "1") {
            $sendresidcart = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "کپی شماره کارت", 'copy_text' => ["text" => $card_number]],
                        ['text' => "کپی مبلغ", 'copy_text' => ["text" => $price_copy]]
                    ],
                    [
                        ['text' => "✅ پرداخت کردم | ارسال رسید.", 'callback_data' => "sendresidcart-" . $randomString]
                    ]
                ]
            ]);
        } else {
            $sendresidcart = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "✅ پرداخت کردم | ارسال رسید.", 'callback_data' => "sendresidcart-" . $randomString]
                    ]
                ]
            ]);
        }
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpcart", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], $data['text']);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], $data['text']);
            }
        }
        $message_id = telegram('sendmessage', [
            'chat_id' => $from_id,
            'text' => $textcart,
            'reply_markup' => $sendresidcart,
            'parse_mode' => "html",
        ]);
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "aqayepardakht") {
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalanceaqayepardakht", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalanceaqayepardakht", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalance و حداکثر $maxbalance تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayaqayepardakht($user['Processing_value'], $randomString);
        if ($pay['status'] != "success") {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "⭕️ خطا در ساخت لینک اقای پردات
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "aqayepardakht";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://panel.aqayepardakht.ir/startpay/" . $pay['transid']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = "✅ فاکتور پرداخت ایجاد شد.\n\n🔢 شماره فاکتور : $randomString
💰 مبلغ فاکتور : $price_format تومان

❌ این تراکنش به مدت ۳۰ دقیقه (نیم ساعت) اعتبار دارد و پس از آن امکان پرداخت این تراکنش امکان‌پذیر نیست.

📌لطفاً پس از پرداخت و موفق بودن تراکنش ، کمی صبر کنید تا پیام پرداخت موفق در سایت ما دریافت کنید. در غیراینصورت اکانت شما شارژ نخواهد شد.

جهت پرداخت از دکمه زیر استفاده کنید👇🏻";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpaqayepardakht", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "zarinpey") {
        $minbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancezarinpey", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancezarinpey", "select")['ValuePay'];

        if ($user['Processing_value'] < $minbalance || $user['Processing_value'] > $maxbalance) {
            $minbalance = number_format($minbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, sprintf($textbotlang['users']['Balance']['zarinpey'], $minbalance, $maxbalance), null, 'HTML');
            return;
        }

        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayZarinpey($user['Processing_value'], $randomString, $from_id);

        if (empty($pay['success'])) {
            $error_text = $pay['message'] ?? $textbotlang['users']['Balance']['errorLinkPayment'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                $ErrorsLinkPayment = "⭕️ خطا در ساخت لینک زرین پی\n✍️ دلیل خطا : {$error_text}\n\nآیدی کابر : $from_id\nنام کاربری کاربر : @$username";
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => 'HTML'
                ]);
            }
            return;
        }

        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "zarinpay";
        $pendingMetadata = [
            'gateway' => 'zarinpay',
            'authority' => $pay['authority'] ?? null,
            'amount_rial' => $pay['amount_rial'] ?? null,
        ];
        $pendingMetadata = array_filter(
            $pendingMetadata,
            static function ($value, $key) {
                if ($key === 'gateway') {
                    return true;
                }

                return !($value === null || $value === '');
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (!empty($pendingMetadata)) {
            $pendingNote = json_encode($pendingMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $pendingNote = (string) ($pay['authority'] ?? '');
        }

        $stmt->bind_param("ssssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pendingNote);
        $stmt->execute();

        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['payment_link'] ?? 'https://zarinpay.me'],
                ]
            ]
        ]);

        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = "✅ فاکتور پرداخت ایجاد شد.\n\n🔢 شماره فاکتور : $randomString\n💰 مبلغ فاکتور : $price_format تومان\n\n❌ این تراکنش به مدت ۳۰ دقیقه (نیم ساعت) اعتبار دارد و پس از آن امکان پرداخت این تراکنش امکان‌پذیر نیست.\n\n📌لطفاً پس از پرداخت و موفق بودن تراکنش ، کمی صبر کنید تا پیام پرداخت موفق در سایت ما دریافت کنید. در غیراینصورت اکانت شما شارژ نخواهد شد.\n\nجهت پرداخت از دکمه زیر استفاده کنید👇🏻";

        $gethelp = getPaySettingValue('helpzarinpey', '2');
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if (is_array($data)) {
                if ($data['type'] == "text") {
                    sendmessage($from_id, $data['text'], null, 'HTML');
                } elseif ($data['type'] == "photo") {
                    sendphoto($from_id, $data['photoid'], null);
                } elseif ($data['type'] == "video") {
                    sendvideo($from_id, $data['videoid'], null);
                }
            }
        }

        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "zarinpal") {
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancezarinpal", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancezarinpal", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalance و حداکثر $maxbalance تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayZarinpal($user['Processing_value'], $randomString);
        if ($pay['data']['code'] != 100) {
            $text_error = json_encode($pay['errors']);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "⭕️ خطا در ساخت لینک زرین پال
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "zarinpal";
        $stmt->bind_param("ssssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['data']['authority']);
        $stmt->execute();
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://www.zarinpal.com/pg/StartPay/" . $pay['data']['authority']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = "
✅ فاکتور پرداخت ایجاد شد.
            
🔢 شماره فاکتور : $randomString
💰 مبلغ فاکتور : $price_format تومان

❌ این تراکنش به مدت یک روز اعتبار دارد پس از آن امکان پرداخت این تراکنش امکان ندارد.        

📌لطفاً پس از پرداخت و موفق بودن تراکنش ، کمی صبر کنید تا پیام پرداخت موفق در سایت ما دریافت کنید. در غیراینصورت اکانت شما شارژ نخواهد شد.

جهت پرداخت از دکمه زیر استفاده کنید👇🏻";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpzarinpal", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "plisio") {
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        if ($usdprice <= 1) {
            sendmessage($from_id, $textbotlang['users']['Balance']['nowpayments'], null, 'HTML');
            return;
        }
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalanceplisio", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalanceplisio", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $pay = plisio($randomString, $trxprice);
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "plisio";
        $stmt->bind_param("ssssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['txn_id']);
        $stmt->execute();
        if (isset($pay['message'])) {
            $text_error = $pay['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
                        ⭕️ یک کاربر قصد پرداخت با درگاه ارزی داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['invoice_url']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $USD = number_format($usd);
        $textnowpayments = "
<b>💲 جهت افزایش اعتبار کیف پول خود از طریق ارز دیجیتال روی دکمه پرداخت در انتهای پیام کلیک کنید</b>

⚠️ توجه:  زمان پرداخت 30 دقیقه می باشد پس از 30 دقیقه تراکنش لغو خواهد شد

🌐 برخی از سایت های داخلی جهت خرید ارز دیجیتال 👇
🔸 nikpardakht.com
🔹 webpurse.org
🔸 bitpin.ir
🔹 sarmayex.com
🔸 ok-ex.io
🔹 nobitex.ir
🔸 bitbarg.com
🔹 cafearz.com
🔸 pay98.app
🔢 شماره فاکتور : $randomString
💰 مبلغ فاکتور : $price_format تومان
📊 قیمت دلار: $USD تومان تا این لحظه

جهت پرداخت از دکمه زیر استفاده👇🏻";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpplisio", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "nowpayment") {
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalancenowpayment", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalancenowpayment", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $pay = nowPayments('invoice', $usdprice, $randomString, "order");
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "nowpayment";
        $stmt->bind_param("ssssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['id']);
        $stmt->execute();
        if (!isset($pay['id'])) {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
                        ⭕️ یک کاربر قصد پرداخت با درگاه ارزی داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['invoice_url']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $USD = number_format($usd);
        $textnowpayments = "
<b>💲 جهت افزایش اعتبار کیف پول خود از طریق ارز دیجیتال روی دکمه پرداخت در انتهای پیام کلیک کنید</b>

⚠️ توجه:  زمان پرداخت 30 دقیقه می باشد پس از 30 دقیقه تراکنش لغو خواهد شد

🌐 برخی از سایت های داخلی جهت خرید ارز دیجیتال 👇
🔸 nikpardakht.com
🔹 webpurse.org
🔸 bitpin.ir
🔹 sarmayex.com
🔸 ok-ex.io
🔹 nobitex.ir
🔸 bitbarg.com
🔹 cafearz.com
🔸 pay98.app
🔢 شماره فاکتور : $randomString
💰 مبلغ فاکتور : $price_format تومان
📊 قیمت دلار: $USD تومان تا این لحظه


<blockquote>⚠️ پس از پرداخت، در صورتی که مبلغ تراکنش به‌درستی واریز شده باشد، موجودی شما حداکثر تا ۱۵ دقیقه آینده به‌صورت خودکار شارژ خواهد شد.</blockquote>


جهت پرداخت از دکمه زیر استفاده👇🏻";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpnowpayment", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay1") {
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = round($user['Processing_value'] / $trx, 2);
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay1", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay1", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 1";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $pay = createInvoiceiranpay1($user['Processing_value'], $randomString);
        if ($pay['status'] != "100") {
            $text_error = $pay['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
⭕️ یک کاربر قصد پرداخت داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : $text_error

آیدی کابر : $from_id
روش پرداخت : $Payment_Method
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        update("Payment_report", "dec_not_confirmed", $pay['Authority'], "id_order", $randomString);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "پرداخت", 'url' => $pay['payment_url_bot']]
                ]
            ]
        ]);
        $pricetoman = number_format($user['Processing_value'], 0);
        $textnowpayments = "✅ تراکنش شما ایجاد شد
        
🛒 کد پیگیری:  <code>$randomString</code> 
💲 مبلغ تراکنش به تومان  : <code>$pricetoman</code>


💢 لطفا به این نکات قبل از پرداخت توجه کنید 👇
        
❌ این تراکنش به مدت ۲۴ ساعت اعتبار دارد پس از آن امکان پرداخت این تراکنش امکان ندارد.        


✅ در صورت مشکل میتوانید با پشتیبانی در ارتباط باشید";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay1", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay2") {
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay2", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay2", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 2";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $payment = trnado($randomString, $trxprice);

        $paymentErrorData = null;
        if (!is_array($payment)) {
            $paymentErrorData = ['error' => 'پاسخ نامعتبر از سرویس ترنادو'];
        } elseif ((isset($payment['success']) && $payment['success'] === false) || (isset($payment['error']) && !isset($payment['IsSuccessful']))) {
            $paymentErrorData = $payment;
        }

        if ($paymentErrorData !== null) {
            $errorLines = [];
            if (isset($paymentErrorData['status_code'])) {
                $errorLines[] = "کد وضعیت HTTP: " . $paymentErrorData['status_code'];
            }
            if (isset($paymentErrorData['errno'])) {
                $errorLines[] = "کد خطای cURL: " . $paymentErrorData['errno'];
            }
            if (isset($paymentErrorData['error'])) {
                $errorLines[] = "پیام خطا: " . $paymentErrorData['error'];
            }
            if (isset($paymentErrorData['raw_response'])) {
                $errorLines[] = "پاسخ خام: " . $paymentErrorData['raw_response'];
            }

            if (empty($errorLines)) {
                $errorLines[] = json_encode($paymentErrorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $text_error = implode("\n", $errorLines);
            $safeErrorText = htmlspecialchars($text_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
                        ⭕️ یک کاربر قصد پرداخت داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : <pre>$safeErrorText</pre>

آیدی کابر : $from_id
روش پرداخت : $Payment_Method
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }

        if (!isset($payment['IsSuccessful']) || $payment['IsSuccessful'] != "true") {
            $text_error = json_encode($payment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $safeErrorText = htmlspecialchars($text_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
                        ⭕️ یک کاربر قصد پرداخت داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : <pre>$safeErrorText</pre>

آیدی کابر : $from_id
روش پرداخت : $Payment_Method
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://t.me/tronado_robot/customerpayment?startapp={$payment['Data']['Token']}"]
                ]
            ]
        ]);
        $pricetoman = number_format($user['Processing_value'], 0);
        $textnowpayments = "✅ تراکنش شما ایجاد شد
        
🛒 کد پیگیری:  <code>$randomString</code> 
💲 مبلغ تراکنش به تومان  : <code>$pricetoman</code>

💢 لطفا به این نکات قبل از پرداخت توجه کنید 👇
        
🔹 تراکنش تا یک روز اعتبار و پس از آن در صورت پرداخت تایید نخواهد شد .
❌ پس از تراکنش 15 تا یک ساعت زمان میبرد تا تراکنش تایید شود

✅ در صورت مشکل میتوانید با پشتیبانی در ارتباط باشید";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay2", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay3") {
        $dateacc = date('Y/m/d');
        $query = "SELECT SUM(price) as price FROM Payment_report WHERE  Payment_Method = 'Currency Rial 1' AND  time LIKE '%$dateacc%'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $sumpayment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (intval($sumpayment['price']) > 1000000) {
            sendmessage($from_id, "تعداد افراد در صف درخواست درگاه پرداخت بشدت زیاد است 📊

‼️درحال حاظر از روش پرداخت دیگری استفاده کنید", null, 'HTML');
            return;
        }
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], null, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 3";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $paylink = createInvoice($trxprice);
        if (!$paylink['success']) {
            $text_error = $paylink['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
⭕️ یک کاربر قصد پرداخت داشت که ساخت لینک پرداخت  با خطا مواجه شده و به کاربر لینک داده نشد
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
روش پرداخت : $Payment_Method
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        update("Payment_report", "dec_not_confirmed", $paylink['data']['id'], "id_order", $randomString);
        $pricetoman = number_format($user['Processing_value'], 0);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "💎 پرداخت", 'url' => "t.me/AvidTrx_Bot?start=" . $paylink['data']['id']]
                ],
            ]
        ]);
        $textnowpayments = "✅ تراکنش شما ایجاد شد
        
🛒 کد پیگیری:  <code>$randomString</code> 
💲 مبلغ تراکنش به تومان  : <code>$pricetoman</code> تومان


💢 لطفا به این نکات قبل از پرداخت توجه کنید 👇
        
❌ این تراکنش به مدت یک روز اعتبار دارد پس از آن امکان پرداخت این تراکنش امکان ندارد.        

✅ در صورت مشکل میتوانید با پشتیبانی در ارتباط باشید";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay3", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        step("getvoocherx", $from_id);
        savedata("clear", "id_payment", $randomString);
    } elseif ($datain == "digitaltron") {
        $rates = requireTronRates(['TRX', 'USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = round($user['Processing_value'] / $trx, 2);
        $usdprice = round($user['Processing_value'] / $usd, 2);
        if ($trxprice <= 1) {
            sendmessage($from_id, $textbotlang['users']['Balance']['changeto'], null, 'HTML');
            return;
        }
        $mainbalancedigitaltron = select("PaySetting", "ValuePay", "NamePay", "minbalancedigitaltron", "select")['ValuePay'];
        $maxbalancedigitaltron = select("PaySetting", "ValuePay", "NamePay", "maxbalancedigitaltron", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalancedigitaltron || $user['Processing_value'] > $maxbalancedigitaltron) {
            $mainbalanceplisio = number_format($mainbalancedigitaltron);
            $maxbalanceplisio = number_format($maxbalancedigitaltron);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalanceplisio و حداکثر $maxbalanceplisio تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "arze digital offline";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $affilnecurrency = select("PaySetting", "*", "NamePay", "walletaddress", "select")['ValuePay'];
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "✅ ارسال لینک واریز یا تصویر واریزی", 'callback_data' => "sendresidarze-{$randomString}"]
                ]
            ]
        ]);
        $formatprice = number_format($user['Processing_value'], 0);
        $textnowpayments = "✅ تراکنش شما ایجاد شد

🛒 کد پیگیری: <code>$randomString</code>
🌐 شبکه: TRX
💳 آدرس ولت: <code>$affilnecurrency</code>
💲 مبلغ تراکنش: $trxprice TRX

📌 مبلغ $formatprice تومان را واریز پس از واریز دکمه زیر را  کلیک و رسید را ارسال نمایید

💢 لطفا به این نکات قبل از پرداخت توجه کنید 👇

🔸 در صورت اشتباه وارد کردن آدرس کیف پول، تراکنش تایید نمیشود و بازگشت وجه امکان پذیر نیست
🔹 مبلغ ارسالی نباید کمتر و یا بیشتر از مبلغ اعلام شده باشد
🔹 در صورت واریز بیش از مقدار گفته شده، امکان اضافه کردن تفاوت وجه وجود ندارد
🔹 هر تراکنش یک ساعت معتبر است و بعد از دریافت پیام منقضی شدن تراکنش به هیچ عنوان مبلغی به کیف پول ارسال نکنید

✅ در صورت مشکل میتوانید با پشتیبانی در ارتباط باشید";
        $gethelp = getPaySettingValue('helpofflinearze');
        if ($gethelp !== null && $gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id =sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "startelegrams") {
        $rates = requireTronRates(['USD']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $usd = $rates['USD'];
        if (!is_numeric($usd) || $usd <= 0) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $userAmountUsd = round($user['Processing_value'] / $usd, 2);
        $starPriceSetting = getPaySettingValue('star_price_usd', '0.016');
        if (is_string($starPriceSetting)) {
            $starPriceSetting = str_replace(',', '.', $starPriceSetting);
        }
        $starPriceUsd = is_numeric($starPriceSetting) ? (float) $starPriceSetting : 0.016;
        if ($starPriceUsd <= 0) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $starAmount = (int) ceil($userAmountUsd / $starPriceUsd);
        if ($starAmount < 1) {
            $starAmount = 1;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancestar", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancestar", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, "❌ حداقل مبلغ واریزی این روش پرداخت باید $mainbalance و حداکثر $maxbalance تومان باشد", null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $connect->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Star Telegram";
        $stmt->bind_param("sssssss", $from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice);
        $stmt->execute();
        $affilnecurrency = select("PaySetting", "*", "NamePay", "walletaddress", "select")['ValuePay'];
        $invoiceParams = [
            'title' => "Buy for Price {$user['Processing_value']}",
            'description' => "Buy price",
            'payload' => $randomString,
            'currency' => "XTR",
            'prices' => json_encode(array(
                array(
                    'label' => "Price",
                    'amount' => $starAmount
                )
            ))
        ];
        if (($invoiceParams['currency'] ?? null) === 'XTR') {
            unset($invoiceParams['provider'], $invoiceParams['provider_token']);
        }
        $straCreateLink = telegram('createInvoiceLink', $invoiceParams);
        if ($straCreateLink['ok'] == false) {
            $text_error = json_encode($straCreateLink);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = "
خطا در هنگام ساخت فاکتور استار
✍️ دلیل خطا : $text_error
            
آیدی کابر : $from_id
روش پرداخت : $Payment_Method
نام کاربری کاربر : @$username";
            if (strlen($setting['Channel_Report'] ?? '') > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $straCreateLink['result']]
                ]
            ]
        ]);
        $formatprice = number_format($user['Processing_value'], 0);
        $approxStarUsd = number_format($starAmount * $starPriceUsd, 2);
        $textstar = "✅ تراکنش شما ایجاد شد

🛒 کد پیگیری: <code>$randomString</code>
💲 مبلغ تراکنش: $starAmount ⭐ (حدوداً $approxStarUsd دلار | معادل $formatprice تومان)

📌 لطفاً مبلغ $formatprice تومان را به استار تلگرام تبدیل کرده و واریز نمایید.

💢 نکات مهم قبل از پرداخت: 👇
🔹 هر تراکنش ۱ روز معتبر است؛ بعد از انقضا از واریز خودداری کنید.

✅ در صورت مشکل، با پشتیبانی در ارتباط باشید.";
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpstar", "select")['ValuePay'];
        if (intval($gethelp) != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textstar, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    }
}
if (preg_match('/Confirmpay_user_(\w+)_(\w+)/', $datain, $dataget)) {
    $id_payment = $dataget[1];
    $id_order = $dataget[2];
    $Payment_report = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM Payment_report WHERE id_order = '$id_order' LIMIT 1"));
    if ($Payment_report['payment_Status'] == "paid") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['Confirmpayadmin'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $StatusPayment = StatusPayment($id_payment);
    if ($StatusPayment['payment_status'] == "finished") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['finished'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
        DirectPayment($Payment_report['id_order']);
        $Balance_id = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM user WHERE id = '{$Payment_report['id_user']}' LIMIT 1"));
        $Payment_report['price'] = number_format($Payment_report['price'], 0);
        $text_report = "💵 پرداخت جدید
                
آیدی عددی کاربر : $from_id
مبلغ تراکنش : {$Payment_report['price']} 
روش پرداخت : درگاه ارزی ریالی اول";
        $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackiranpay2", "select")['ValuePay'];
        if ($pricecashback != "0") {
            $result = ($Payment_report['price'] * $pricecashback) / 100;
            $Balance_confrim = intval($Balance_id['Balance']) + $result;
            update("user", "Balance", $Balance_confrim, "id", $user['id']);
            $pricecashback = number_format($pricecashback);
            $text_report = sprintf($textbotlang['users']['Discount']['gift-deposit'], $result);
            sendmessage($from_id, $text_report, null, 'HTML');
        }
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $paymentreports,
                'text' => $text_report,
                'parse_mode' => "HTML"
            ]);
        }
        update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
        update("user", "Processing_value_one", "none", "id", $Payment_report['id_order']);
        update("user", "Processing_value_tow", "none", "id", $Payment_report['id_order']);
        update("user", "Processing_value_four", "none", "id", $Payment_report['id_order']);
    } elseif ($StatusPayment['payment_status'] == "expired") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['expired'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
    } elseif ($StatusPayment['payment_status'] == "refunded") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['refunded'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
    } elseif ($StatusPayment['payment_status'] == "waiting") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['waiting'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
    } elseif ($StatusPayment['payment_status'] == "sending") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['sending'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
    } else {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['users']['Balance']['Failed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
    }
}
if (preg_match('/^sendresidcart-(.*)/', $datain, $dataget)) {
    $timefivemin = time() - 120;
    $timefivemin = date('Y/m/d H:i:s', intval($timefivemin));
    $sql = "SELECT * FROM Payment_report WHERE id_user = '$from_id' AND Payment_Method = 'cart to cart' AND at_updated > '$timefivemin'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $paymentcount = $stmt->rowCount();
    if ($paymentcount != 0 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, "❗ شما در ۲ دقیقه اخیر رسید ارسال کرده اید لطفا ۲ دقیقه دیگر رسید جدید را ارسال نمایید.", null, 'HTML');
        return;
    }
    $payemntcheck = select("Payment_report", "*", "id_order", $dataget[1], "select");
    if ($payemntcheck['payment_Status'] == "paid") {
        sendmessage($from_id, "❗️ تراکنش شما توسط ربات تایید گردیده است.", null, 'HTML');
        return;
    }
    if ($payemntcheck['payment_Status'] == "expire") {
        sendmessage($from_id, "❗زمان این تراکنش به پایان رسیده و امکان پرداخت این تراکنش وجود ندارد.", null, 'HTML');
        return;
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, "🖼 تصویر رسید خود را ارسال نمایید", $backuser, 'HTML');
    step('cart_to_cart_user', $from_id);
    update("user", "Processing_value", $dataget[1], "id", $from_id);
} elseif (preg_match('/^sendresidarze-(.*)/', $datain, $dataget) and $text_inline != null) {
    $payemntcheck = select("Payment_report", "*", "id_order", $dataget[1], "select");
    if ($payemntcheck['payment_Status'] == "paid") {
        sendmessage($from_id, "❗️ تراکنش شما توسط ربات تایید گردیده است.", null, 'HTML');
        return;
    }
    if ($payemntcheck['payment_Status'] == "expire") {
        sendmessage($from_id, "❗زمان این تراکنش به پایان رسیده و امکان پرداخت این تراکنش وجود ندارد.", null, 'HTML');
        return;
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, "📌 تصویر واریزی خود یا لینک تراکنش ترون را ارسال نمایید.", $backuser, 'HTML');
    step('getresidcurrency', $from_id);
    update("user", "Processing_value", $dataget[1], "id", $from_id);
} elseif ($user['step'] == "getresidcurrency") {
    $format_balance = number_format($user['Balance'], 0);
    step('home', $from_id);
    $PaymentReport = select("Payment_report", "*", "id_order", $user['Processing_value'], "select");
    $Paymentusercount = select("Payment_report", "*", "id_user", $PaymentReport['id_user'], "count");
    if ($PaymentReport == false) {
        sendmessage($from_id, "❌ خطایی رخ داده است لطفا مراحل خرید یا پرداخت  را مجدد انجام دهید", $keyboard, 'HTML');
        return;
    }
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['Confirmpaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['reject_pay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
            ],
            [
                ['text' => $textbotlang['users']['Balance']['addbalamceuser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
            ]
        ]
    ]);
    $textdiscount = "";
    $format_price_cart = number_format($PaymentReport['price'], 0);
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        $get_invoice = select("invoice", "*", "username", $user['Processing_value_one'], "select");
        if ($get_invoice == false) {
            sendmessage($from_id, "❌ خطایی رخ داده است لطفا مراحل خرید یا پرداخت  را مجدد انجام دهید", $keyboard, 'HTML');
            return;
        }
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید سرویس جدید

نام کاربری سرویس : {$get_invoice['username']}
نام محصول : {$get_invoice['name_product']}
حجم محصول : {$get_invoice['Volume']} گیگ 
زمان محصول : {$get_invoice['Service_time']} روز
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💵 تعداد کل پرداختی های کاربر : $Paymentusercount عدد
💸 مبلغ پرداختی: $format_price_cart تومان

                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    } elseif ($user['Processing_value_tow'] == "getextenduser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $sql = "SELECT * FROM service_other WHERE username = :username  AND value  LIKE CONCAT('%', :value, '%') AND id_user = :id_user ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernamepanel, PDO::PARAM_STR);
        $stmt->bindParam(':value', $partsdic[1], PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service_other == false) {
            sendmessage($from_id, '❌ خطایی در هنگام دریافت اطلاعات رخ داده است لطفا مراحل را از اول انجام دهید', $keyboard, 'HTML');
            return;
        }
        $service_other = json_decode($service_other['value'], true);
        $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
        $customtimevalueprice = $eextraprice[$user['agent']];
        $codeproduct = $service_other['code_product'];
        if ($codeproduct == "custom_volume") {
            $prodcut['code_product'] = "custom_volume";
            $prodcut['name_product'] = $nameloc['name_product'];
            $prodcut['price_product'] = ($service_other['volumebuy'] * $custompricevalue) + ($nameloc['Service_time'] * $customtimevalueprice);
            $prodcut['Service_time'] = $service_other['Service_time'];
            $prodcut['Volume_constraint'] = $service_other['volumebuy'];
        } else {
            $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
            $prodcut = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE (Location = '{$nameloc['Service_location']}' OR Location = '/all') AND code_product = '$codeproduct'"));
        }
        $Confirm_pay = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['Confirmpaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['reject_pay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
                ],
                [
                    ['text' => $textbotlang['users']['Balance']['addbalamceuser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
                ],
                [
                    ['text' => "⚙️ اطلاعات کانفیگ", 'callback_data' => "manageinvoice_{$nameloc['id_invoice']}"],
                ]
            ]
        ]);
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
تمدید
نام کاربری سرویس : $usernamepanel
نام محصول : {$prodcut['name_product']}
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💵 تعداد کل پرداختی های کاربر : $Paymentusercount عدد
💸 مبلغ پرداختی: $format_price_cart تومان
                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    } elseif ($user['Processing_value_tow'] == "getextravolumeuser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $volumes = $partsdic[1];
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید حجم اضافه
نام کاربری سرویس : $usernamepanel
حجم خریداری شده  : $volumes
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💵 تعداد کل پرداختی های کاربر : $Paymentusercount عدد
💸 مبلغ پرداختی: $format_price_cart تومان
                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    } elseif ($user['Processing_value_tow'] == "getextratimeuser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $time = $partsdic[1];
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید زمان اضافه
نام کاربری سرویس : $usernamepanel
تعداد روز خریداری شده  : $time
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💵 تعداد کل پرداختی های کاربر : $Paymentusercount عدد
💸 مبلغ پرداختی: $format_price_cart تومان
                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    } else {

        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .
افزایش موجودی            
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💵 تعداد کل پرداختی های کاربر : $Paymentusercount عدد
💸 مبلغ پرداختی: $format_price_cart تومان
                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    }
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "support")
            continue;
        if ($photo) {
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'caption' => $textbotlang['users']['Balance']['receiptimage'],
                'parse_mode' => "HTML",
            ]);
        }
        sendmessage($id_admin, $textsendrasid, $Confirm_pay, 'HTML');
    }
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receiptadnsendconfig'], $keyboard, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receipt'], $keyboard, 'HTML');
    }
    update("Payment_report", "payment_Status", "waiting", "id_order", $PaymentReport['id_order']);
    update("Payment_report", "dec_not_confirmed", "$text $caption", "id_order", $PaymentReport['id_order']);
    $dateacc = date('Y/m/d H:i:s');
    update("Payment_report", "at_updated", $dateacc, "id_order", $PaymentReport['id_order']);
} elseif ($user['step'] == "cart_to_cart_user") {
    $format_balance = number_format($user['Balance'], 0);
    if (!$photo or isset($update['message']['media_group_id'])) {
        sendmessage($from_id, "❌  فقط مجاز به ارسال یک تصویر هستید", null, 'HTML');
        return;
    }
    step('home', $from_id);
    $PaymentReport = select("Payment_report", "*", "id_order", $user['Processing_value']);
    if ($PaymentReport == false) {
        sendmessage($from_id, '❌ خطایی در هنگام دریافت اطلاعات رخ داده است لطفا مراحل را از اول انجام دهید', $keyboard, 'HTML');
        return;
    }
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['Confirmpaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['reject_pay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
            ],
            [
                ['text' => $textbotlang['users']['Balance']['addbalamceuser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
            ]
        ]
    ]);
    $format_price_cart = number_format($PaymentReport['price'], 0);
    $split_data = explode('|', $PaymentReport['id_invoice']);
    if ($split_data[0] == "getconfigafterpay") {
        $get_invoice = select("invoice", "*", "username", $split_data[1], "select");
        if ($get_invoice == false) {
            sendmessage($from_id, "❌ خطایی رخ داده است لطفا مراحل خرید یا پرداخت  را مجدد انجام دهید", $keyboard, 'HTML');
            return;
        }
        $textdiscount = "";
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید سرویس جدید
نام کاربری سرویس  : {$get_invoice['username']}
نام محصول : {$get_invoice['name_product']}
حجم محصول : {$get_invoice['Volume']} گیگ
زمان محصول : {$get_invoice['Service_time']} روز
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receiptadnsendconfig'], $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextenduser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $sql = "SELECT * FROM service_other WHERE username = :username  AND value  LIKE CONCAT('%', :value, '%') AND id_user = :id_user ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernamepanel, PDO::PARAM_STR);
        $stmt->bindParam(':value', $partsdic[1], PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service_other == false) {
            sendmessage($from_id, '❌ خطایی در هنگام دریافت اطلاعات رخ داده است لطفا مراحل را از اول انجام دهید', $keyboard, 'HTML');
            return;
        }
        $service_other = json_decode($service_other['value'], true);
        $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
        $customtimevalueprice = $eextraprice[$user['agent']];
        $codeproduct = $service_other['code_product'];
        if ($codeproduct == "custom_volume") {
            $prodcut['code_product'] = "custom_volume";
            $prodcut['name_product'] = $nameloc['name_product'];
            $prodcut['price_product'] = ($service_other['volumebuy'] * $custompricevalue) + ($service_other['Service_time'] * $customtimevalueprice);
            $prodcut['Service_time'] = $service_other['Service_time'];
            $prodcut['Volume_constraint'] = $service_other['volumebuy'];
        } else {
            $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
            $prodcut = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM product WHERE (Location = '{$nameloc['Service_location']}' OR Location = '/all') AND code_product = '$codeproduct'"));
        }
        $Confirm_pay = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['Confirmpaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['reject_pay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
                ],
                [
                    ['text' => $textbotlang['users']['Balance']['addbalamceuser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
                ],
                [
                    ['text' => "⚙️ اطلاعات کانفیگ", 'callback_data' => "manageinvoice_{$nameloc['id_invoice']}"],
                ]
            ]
        ]);
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
تمدید
نام کاربری سرویس : $usernamepanel
نام محصول : {$prodcut['name_product']}
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
        sendmessage($from_id, "🚀 رسید شما ارسال و پس از بررسی سرویس شما تمدید خواهد شد", $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextravolumeuser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $volumes = $partsdic[1];
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید حجم اضافه
نام کاربری سرویس : $usernamepanel
حجم خریداری شده  : $volumes
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
        sendmessage($from_id, "🚀 رسید شما ارسال و پس از بررسی  به سرویس شما حجم اضافه خواهد شد.", $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextratimeuser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $time = $partsdic[1];
        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .

⭕️⭕️⭕️⭕️⭕️
خرید زمان اضافه
نام کاربری سرویس : $usernamepanel
تعداد روز خریداری شده  : $time
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
        sendmessage($from_id, "🚀 رسید شما ارسال و پس از بررسی به سرویس شما زمان اضافه خواهد شد", $keyboard, 'HTML');
    } else {

        $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .
افزایش موجودی            
👤 نام اکانت کاربر : $first_name
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
💸 موجودی فعلی کاربر : $format_balance تومان
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receipt'], $keyboard, 'HTML');
    }
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "support")
            continue;
        telegram('sendphoto', [
            'chat_id' => $id_admin,
            'photo' => $photoid,
            'caption' => $caption,
            'parse_mode' => "HTML",
        ]);
        sendmessage($id_admin, $textsendrasid, $Confirm_pay, 'HTML');
    }
    update("Payment_report", "payment_Status", "waiting", "id_order", $PaymentReport['id_order']);
    $dateacc = date('Y/m/d H:i:s');
    update("Payment_report", "at_updated", $dateacc, "id_order", $PaymentReport['id_order']);
} elseif ($datain == "Discount") {
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['Discount']['getcode'], $bakinfos);
    step('get_code_user', $from_id);
} elseif ($user['step'] == "get_code_user") {
    if (!in_array($text, $code_Discount)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], null, 'HTML');
        return;
    }
    $checklimit = select("Discount", "*", "code", $text, "select");
    if ($checklimit['limitused'] >= $checklimit['limituse']) {
        sendmessage($from_id, $textbotlang['users']['Discount']['erorrlimitdiscount'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if ($Checkcodesql != 0) {
        sendmessage($from_id, $textbotlang['users']['Discount']['giftcodeonce'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Discount WHERE code = :code LIMIT 1");
    $stmt->bindParam(':code', $text);
    $stmt->execute();
    $get_codesql = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance_user = $user['Balance'] + $get_codesql['price'];
    update("user", "Balance", $balance_user, "id", $from_id);
    $discountlimitadd = intval($checklimit['limitused']) + 1;
    update("Discount", "limitused", $discountlimitadd, "code", $text);
    step('home', $from_id);
    $text_balance_code = sprintf($textbotlang['users']['Discount']['giftcodesuccess'], $get_codesql['price']);
    sendmessage($from_id, $text_balance_code, $keyboard, 'HTML');
    $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user, code) VALUES (:id_user, :code)");
    $stmt->execute([
        ':id_user' => $from_id,
        ':code' => $text,
    ]);
    $text_report = sprintf($textbotlang['users']['Discount']['giftcodeused'], $username, $from_id, $text);
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($text == $datatextbot['text_Tariff_list'] || $datain == "Tariff_list") {
    sendmessage($from_id, $datatextbot['text_dec_Tariff_list'], null, 'HTML');
} elseif ($datain == "colselist") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['back'], $keyboard, 'HTML');
} elseif ($text == $datatextbot['text_affiliates'] || $datain == "affiliatesbtn") {
    if (!check_active_btn($setting['keyboardmain'], "text_affiliates")) {
        sendmessage($from_id, "❌ این دکمه غیرفعال می باشد", null, 'HTML');
        return;
    }
    if ($setting['affiliatesstatus'] == "offaffiliates") {
        sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], null, 'HTML');
        return;
    }
    $affiliates = select("affiliates", "*", null, null, "select");
    $textaffiliates = "{$affiliates['description']}\n\n🔗 https://t.me/$usernamebot?start=$from_id";
    if (strlen($affiliates['id_media']) >= 5) {
        telegram('sendphoto', [
            'chat_id' => $from_id,
            'photo' => $affiliates['id_media'],
            'caption' => $textaffiliates,
            'parse_mode' => "HTML",
        ]);
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $sqlPanel = "SELECT COUNT(*) AS orders, COALESCE(SUM(price_product), 0) AS total_price
                 FROM invoice
                 WHERE Status IN ('active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold')
                 AND refral = :refral
                 AND name_product != 'سرویس تست'";
    $stmt = $pdo->prepare($sqlPanel);
    $stmt->execute([':refral' => $from_id]);
    $inforefral = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['orders' => 0, 'total_price' => 0];
    $orders_count = (int)($inforefral['orders'] ?? 0);
    $total_purchase = (float)($inforefral['total_price'] ?? 0);
    $keyboard_share = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "🎁 دریافت هدیه عضویت", 'callback_data' => "get_gift_start"],
                ['text' => "🔗 اشتراک گذاری لینک", 'url' => "https://t.me/share/url?url=https://t.me/$usernamebot?start=$from_id"],
            ],
        ]
    ]);
    $text_start = "";
    $text_porsant = "";
    $Percent_porsant = $setting['affiliatespercentage'];
    $sum_order = number_format($total_purchase, 0);
    if ($affiliatescommission['Discount'] == "onDiscountaffiliates") {
        $text_start = "<b>🎁 هدیه عضویت:</b>
• 🎉 مجموع هدیه: {$affiliatescommission['price_Discount']} تومان  
• 🔻 ۵۰٪ برای شما (معرف)  
• 🔻 ۵۰٪ برای زیرمجموعه (کاربر جدید)
";
    }
    if ($affiliatescommission['status_commission'] == "oncommission") {
        $text_porsant = "<b>💸 پورسانت خرید:</b>  
•  $Percent_porsant درصد از مبلغ خرید زیرمجموعه به شما تعلق می‌گیره";
    }
    $textaffiliates = "<b>💼 زیرمجموعه‌گیری و هدیه خوش‌آمد</b>

با دعوت دوستان از طریق <b>لینک اختصاصی</b>، بدون پرداخت حتی ۱ ریال کیف پولت شارژ میشه و از خدمات ربات استفاده می‌کنی!

$text_start
$text_porsant

<b>📊 آمار شما:</b>
• 👥 زیرمجموعه‌ها: {$user['affiliatescount']} نفر
• 🛒 خریدها: $orders_count عدد
• 💵 مجموع خرید: $sum_order تومان

<b>📢 دعوت کن، هدیه بگیر، رشد کن!</b>
";

    sendmessage($from_id, $textaffiliates, $keyboard_share, 'HTML');
} elseif ($datain == "get_gift_start") {
    $gift_status = select("affiliates", "*", null, null, "select");
    if ($gift_status['Discount'] == "offDiscountaffiliates") {
        sendmessage($from_id, "📛 این بخش درحال حاضر غیرفعال می باشد", $keyboard, 'HTML');
        return;
    }
    if (!in_array($user['affiliates'], $users_ids)) {
        sendmessage($from_id, "📛 شما زیرمجموعه هیچ کاربری نیستید.", $keyboard, 'HTML');
        return;
    }
    $reagent = select("reagent_report", "*", "user_id", $from_id, "select", ['cache' => false]);
    if (!$reagent) {
        $affiliateId = intval($user['affiliates']);
        if ($affiliateId && in_array($affiliateId, $users_ids)) {
            $stmt = $pdo->prepare("INSERT INTO reagent_report (user_id, get_gift, time, reagent)
                                   VALUES (:user_id, :get_gift, :time, :reagent)
                                   ON DUPLICATE KEY UPDATE reagent = VALUES(reagent), get_gift = VALUES(get_gift), time = VALUES(time)");
            $stmt->execute([
                ':user_id' => $from_id,
                ':get_gift' => 0,
                ':time' => date('Y/m/d H:i:s'),
                ':reagent' => $affiliateId,
            ]);
            if (function_exists('clearSelectCache')) {
                clearSelectCache('reagent_report');
            }
            $reagent = select("reagent_report", "*", "user_id", $from_id, "select", ['cache' => false]);
        }
        if (!$reagent) {
            sendmessage($from_id, "📛 شما زیرمجموعه هیچ کاربری نیستید.", $keyboard, 'HTML');
            return;
        }
    }
    if (!empty($reagent['get_gift'])) {
        sendmessage($from_id, "<b>⛔ شما قبلاً هدیه عضویت را دریافت کرده‌اید.</b>
این هدیه فقط <b>یک‌بار</b> قابل فعال‌سازی است.", $keyboard, 'HTML');
        return;
    }
    update("reagent_report", "get_gift", true, "user_id", $from_id);
    $reagent['get_gift'] = true;
    $price_gift_Start = select("affiliates", "*", null, null, "select");
    $price_gift_Start = intval($price_gift_Start['price_Discount']) / 2;
    $useraffiliates = select("user", "*", 'id', $reagent['reagent'], "select");
    $Balance_add_regent = $useraffiliates['Balance'] + $price_gift_Start;
    update("user", "Balance", $Balance_add_regent, "id", $reagent['reagent']);
    $Balance_add_user = $user['Balance'] + $price_gift_Start;
    update("user", "Balance", $Balance_add_user, "id", $from_id);
    $addbalancediscount = number_format($price_gift_Start, 0);
    sendmessage($reagent['reagent'], "🎉 یک نفر با معرفی شما وارد شد! هدیه به حساب شما واریز شد.", null, 'html');
    sendmessage($from_id, "🎉 هدیه عضویت برای شما فعال شد!", null, 'html');
    $report_join_gift = "🎁 پرداخت هدیه عضویت
 -آیدی عددی : $from_id
 - نام کاربری : @$username
 - آیدی عددی معرف : {$reagent['reagent']}
 - موجودی زیرمجموعه قبل از هدیه : {$user['Balance']}
 - موجودی زیرمجموعه بعد از هدیه : $Balance_add_user
  - موجودی معرف قبل از هدیه : {$useraffiliates['Balance']}
 - موجودی معرف بعد از هدیه : $Balance_add_regent
 ";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $porsantreport,
            'text' => $report_join_gift,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/Extra_volumes_(\w+)_(.*)/', $datain, $dataget)) {
    $usernamepanel = $dataget[1];
    $locations = select("marzban_panel", "*", "code_panel", $dataget[2], "select");
    $location = $locations['name_panel'];
    $eextraprice = json_decode($locations['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $usernamepanel, "id", $from_id);
    update("user", "Processing_value_one", $location, "id", $from_id);

    $textextra = sprintf($textbotlang['users']['Extra_volume']['enterextravolume'], $extrapricevalue);
    sendmessage($from_id, $textextra, $backuser, 'HTML');
    step('getvolumeextras', $from_id);
} elseif ($user['step'] == "getvolumeextras") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    if ($text < 1) {
        sendmessage($from_id, $textbotlang['users']['Extra_volume']['invalidprice'], $backuser, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value_one'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextra = $extrapricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Extra_volume']['extracheck'], 'callback_data' => 'confirmaextras_' . $priceextra],
            ]
        ]
    ]);
    $priceextra = number_format($priceextra, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = sprintf($textbotlang['users']['Extra_volume']['extravolumeinvoice'], $extrapricevalues, $priceextra, $text);
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextras_(\w+)/', $datain, $dataget)) {
    $volume = $dataget[1];
    if ($user['Balance'] < $volume && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($volume * $user['pricediscount']) / 100;
                $volume = $volume - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $volume - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $volume) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value_one'], "select");
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    deletemessage($from_id, $message_id);
    if (intval($user['pricediscount']) != 0) {
        $result = ($volume * $user['pricediscount']) / 100;
        $volume = $volume - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }

    $DataUserOut = $ManagePanel->DataUser($user['Processing_value_one'], $user['Processing_value']);
    $data_limit = $DataUserOut['data_limit'] + (intval($volume) / intval($extrapricevalue) * pow(1024, 3));
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price) VALUES (:id_user, :username, :value, :type, :time, :price)");
    $value = $data_limit;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_not_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $user['Processing_value'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $volume,
    ]);
    $data_limit_new = (intval($volume) / intval($extrapricevalue));
    $extra_volume = $ManagePanel->extra_volume($user['Processing_value'], $marzban_list_get['code_panel'], $data_limit_new);
    if ($extra_volume['status'] == false) {
        $extra_volume['msg'] = json_encode($extra_volume['msg']);
        $textreports = "خطای خرید حجم اضافه
نام پنل : {$user['Processing_value_one']}
نام کاربری سرویس : {$user['Processing_value']}
دلیل خطا : {$extra_volume['msg']}";
        sendmessage($from_id, "❌خطایی در خرید حجم اضافه سرویس رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $Balance_Low_user = $user['Balance'] - $volume;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $back = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => 'backuser'],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['extend']['thanks'], $back, 'HTML');
    $volumes = $volume / $extrapricevalue;
    $volumes = number_format($volumes, 0);
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['volumepurchase'], $from_id, $volumes, $volume, $user['Balance'], $user['Processing_value']);
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($datain == "searchservice") {
    sendmessage($from_id, $textbotlang['users']['search']['usernamgeget'], $backuser, 'HTML');
    step('getuseragnetservice', $from_id);
} elseif ($datain == "Responseuser") {
    step('getmessageAsuser', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetTextResponse'], $backuser, 'HTML');
} elseif ($user['step'] == "getmessageAsuser") {
    sendmessage($from_id, $textbotlang['users']['support']['sendmessageadmin'], $keyboard, 'HTML');
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "Seller")
            continue;
        if ($text) {
            $textsendadmin = sprintf($textbotlang['Admin']['MessageBulk']['usermessage'], $from_id, $username, $caption . $text);
            sendmessage($id_admin, $textsendadmin, $Response, 'HTML');
        }
        if ($photo) {
            $textsendadmin = sprintf($textbotlang['Admin']['MessageBulk']['userresponse'], $from_id, $username, $caption);
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'reply_markup' => $Response,
                'caption' => $textsendadmin,
                'parse_mode' => "HTML",
            ]);
        }
    }
    step('home', $from_id);
} elseif (($text == $datatextbot['textpanelagent'] || $datain == "agentpanel") && $user['agent'] != "f") {
    if ($setting['inlinebtnmain'] == "oninline") {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['agent']['agenttext'], $keyboardagent, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['agent']['agenttext'], $keyboardagent, 'HTML');
    }
} elseif ($text == $textbotlang['users']['agenttext']['customnameusername'] || $datain == "selectname") {
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
    step('selectusernamecustom', $from_id);
} elseif ($user['step'] == "selectusernamecustom") {
    if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
        sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['agent']['submitusername'], $keyboardagent, 'html');
    update("user", "namecustom", $text, "id", $from_id);
    step("home", $from_id);
} elseif ($text == $datatextbot['textrequestagent'] || $datain == "requestagent") {
    if ($user['Balance'] < $setting['agentreqprice']) {
        $priceagent = number_format($setting['agentreqprice']);
        sendmessage($from_id, sprintf($textbotlang['users']['agenttext']['insufficientbalanceagent'], $priceagent), $backuser, 'HTML');
        return;
    }
    $countagentrequest = select("Requestagent", "*", "id", $from_id, "count");
    if ($countagentrequest != 0) {
        sendmessage($from_id, $textbotlang['users']['agenttext']['requestreport'], null, 'html');
        return;
    }
    if ($user['agent'] != "f") {
        sendmessage($from_id, $textbotlang['users']['agenttext']['isagent'], null, 'html');
        return;
    }
    if ($datain == "requestagent") {
        Editmessagetext($from_id, $message_id, $datatextbot['text_request_agent_dec'], $backuser);
    } else {
        sendmessage($from_id, $datatextbot['text_request_agent_dec'], $backuser, 'html');
    }
    step("getagentrequest", $from_id);
} elseif ($user['step'] == "getagentrequest" && $text) {
    $balancelow = $user['Balance'] - $setting['agentreqprice'];
    update("user", "Balance", $balancelow, "id", $from_id);
    sendmessage($from_id, $textbotlang['users']['agenttext']['endrequest'], $keyboard, 'html');
    step("home", $from_id);
    $stmt = $pdo->prepare("INSERT INTO Requestagent (id, username, time, Description, status, type) VALUES (:id, :username, :time, :description, :status, :type)");
    $status = "waiting";
    $type = "None";
    $current_time = time();
    $description = $text;
    $requestAgentInserted = false;
    try {
        $stmt->execute([
            ':id' => $from_id,
            ':username' => $username,
            ':time' => $current_time,
            ':description' => $description,
            ':status' => $status,
            ':type' => $type,
        ]);
        $requestAgentInserted = true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Incorrect string value') !== false) {
            $tableConverted = ensureTableUtf8mb4('Requestagent');
            if ($tableConverted) {
                try {
                    $stmt->execute([
                        ':id' => $from_id,
                        ':username' => $username,
                        ':time' => $current_time,
                        ':description' => $description,
                        ':status' => $status,
                        ':type' => $type,
                    ]);
                    $requestAgentInserted = true;
                } catch (PDOException $retryException) {
                    error_log('Retry after charset conversion failed: ' . $retryException->getMessage());
                }
            }

            if (!$requestAgentInserted) {
                $sanitisedDescription = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $description);
                if ($sanitisedDescription !== $description) {
                    $stmt->execute([
                        ':id' => $from_id,
                        ':username' => $username,
                        ':time' => $current_time,
                        ':description' => $sanitisedDescription,
                        ':status' => $status,
                        ':type' => $type,
                    ]);
                    $requestAgentInserted = true;
                } else {
                    throw $e;
                }
            }
        } else {
            throw $e;
        }
    }

    if (!$requestAgentInserted) {
        throw new RuntimeException('Failed to persist agent request description.');
    }
    $textrequestagent = sprintf($textbotlang['users']['agenttext']['agent-request'], $from_id, $username, $first_name, $text);
    $keyboardmanage = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['agenttext']['acceptrequest'], 'callback_data' => "addagentrequest_" . $from_id], ['text' => $textbotlang['users']['agenttext']['rejectrequest'], 'callback_data' => "rejectrequesta_" . $from_id]],
            [
                ['text' => $textbotlang['users']['SendMessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $textrequestagent, $keyboardmanage, 'HTML');
    }
} elseif ($text == "/privacy") {
    sendmessage($from_id, $datatextbot['text_roll'], null, 'HTML');
} elseif ($text == $datatextbot['text_wheel_luck'] || $datain == "wheel_luck" || $text == "/gift") {
    if (!check_active_btn($setting['keyboardmain'], "text_wheel_luck")) {
        sendmessage($from_id, "❌ این دکمه غیرفعال می باشد", null, 'HTML');
        return;
    }
    if ($setting['wheelagent'] == "0" and $user['agent'] != "f") {
        sendmessage($from_id, "❌ این دکمه برای شما غیرفعال می باشد", null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != 'سرویس تست'  AND id_user = :id_user AND status != 'Unpaid'");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $countinvoice = $stmt->rowCount();
    if (intval($setting['statusfirstwheel']) == 1 and $countinvoice != 0) {
        sendmessage($from_id, "❌ متاسفانه این آپشن فقط برای کاربرانی فعال است که از ربات خریدی نداشته باشند.", null, 'HTML');
        return;
    }
    if ($setting['wheelـluck'] == "0" or ($setting['wheelagent'] == "0" and $users['agent'] != "f")) {
        sendmessage($from_id, $textbotlang['users']['wheel_luck']['feature-disabled'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM wheel_list  WHERE id_user = '$from_id' ORDER BY time DESC LIMIT 1");
    $stmt->execute();
    $USER = $stmt->fetch(PDO::FETCH_ASSOC);
    $timelast = isset($USER['time']) ? strtotime($USER['time']) : false;
    if ($USER && $timelast !== false && (time() - $timelast) <= 86400) {
        sendmessage($from_id, $textbotlang['users']['wheel_luck']['already-participated'], null, 'HTML');
        return;
    }
    if (intval($setting['Dice']) == 1) {
        $diceResponse = telegram('sendDice', [
            'chat_id' => $from_id,
            'emoji' => "🎲",
        ]);
        sleep(4.5);
    } else {
        $diceResponse = telegram('sendDice', [
            'chat_id' => $from_id,
            'emoji' => "🎰",
        ]);
        sleep(2);
    }
    if (!is_array($diceResponse) || empty($diceResponse['ok']) || !isset($diceResponse['result']['dice']['value'])) {
        $errorContext = is_array($diceResponse) ? json_encode($diceResponse) : (is_string($diceResponse) ? $diceResponse : 'empty response');
        error_log('Failed to receive dice value for wheel_luck: ' . $errorContext);
        sendmessage($from_id, $textbotlang['users']['wheel_luck']['error'] ?? '❌ خطایی در دریافت نتیجه بازی رخ داد. لطفاً بعداً مجدداً تلاش کنید.', null, 'HTML');
        return;
    }
    $diceValue = (int) $diceResponse['result']['dice']['value'];
    $dateacc = date('Y/m/d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM wheel_list  WHERE id_user = '$from_id' ORDER BY time DESC LIMIT 1");
    $stmt->execute();
    $USER = $stmt->fetch(PDO::FETCH_ASSOC);
    $timelast = isset($USER['time']) ? strtotime($USER['time']) : false;
    if ($USER && $timelast !== false && (time() - $timelast) <= 86400) {
        sendmessage($from_id, $textbotlang['users']['wheel_luck']['already-participated'], null, 'HTML');
        return;
    }
    $status = false;
    if (intval($setting['Dice']) == 1) {
        if ($diceValue === 6) {
            $status = true;
        }
    } else {
        if (in_array($diceValue, [1, 43, 64, 22], true)) {
            $status = true;
        }
    }
    if ($status) {
        $balance_last = intval($setting['wheelـluck_price']) + $user['Balance'];
        update("user", "Balance", $balance_last, "id", $from_id);
        $price = number_format($setting['wheelـluck_price']);
        sendmessage($from_id, sprintf($textbotlang['users']['wheel_luck']['winner-congratulations'], $price), null, 'HTML');
        $pricelast = $setting['wheelـluck_price'];
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherreport,
                'text' => sprintf($textbotlang['users']['wheel_luck']['wheel-winner'], $username, $from_id),
                'parse_mode' => "HTML"
            ]);
        }
    } else {
        sendmessage($from_id, $textbotlang['users']['wheel_luck']['notWinner'], null, 'HTML');
        $pricelast = 0;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO wheel_list (id_user,first_name,wheel_code,time,price) VALUES (:id_user,:first_name,:wheel_code,:time,:price)");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':wheel_code', $diceValue);
    $stmt->bindParam(':time', $dateacc);
    $stmt->bindParam(':price', $pricelast);
    $stmt->execute();
} elseif ($text == "/tron") {
    $rates = requireTronRates(['TRX']);
    if ($rates === null) {
        sendmessage($from_id, "❌ دریافت قیمت در حال حاضر امکان پذیر نیست. لطفاً بعداً تلاش کنید.", null, 'HTML');
        return;
    }
    $price = $rates['TRX'];
    sendmessage($from_id, sprintf($textbotlang['users']['pricearze']['tron-price'], $price), null, 'HTML');
} elseif ($text == "/usd") {
    $rates = requireTronRates(['USD']);
    if ($rates === null) {
        sendmessage($from_id, "❌ دریافت قیمت در حال حاضر امکان پذیر نیست. لطفاً بعداً تلاش کنید.", null, 'HTML');
        return;
    }
    $price = $rates['USD'];
    sendmessage($from_id, sprintf($textbotlang['users']['pricearze']['tether-price'], $price), null, 'HTML');
} elseif ($text == $datatextbot['text_extend'] or $datain == "extendbtn") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $invoices = $stmt->rowCount();
    if ($invoices == 0) {
        sendmessage($from_id, $textbotlang['users']['extend']['emptyServiceforExtend'], null, 'html');
        return;
    }
    $pages = 1;
    update("user", "pagenumber", $pages, "id", $from_id);
    $page = 1;
    $items_per_page = 20;
    $start_index = ($page - 1) * $items_per_page;
    $result = mysqli_query($connect, "SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $start_index, $items_per_page");
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    if ($datain == "backorder") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
    } else {
        sendmessage($from_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json, 'html');
    }
} elseif ($datain == 'next_page_extends') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = mysqli_query($connect, "SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $start_index, $items_per_page");
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
} elseif ($datain == 'previous_page_extends') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $previous_page = 1;
    } else {
        $previous_page = $page - 1;
    }
    $start_index = ($previous_page - 1) * $items_per_page;
    $result = mysqli_query($connect, "SELECT * FROM invoice WHERE id_user = '$from_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') ORDER BY time_sell DESC LIMIT $previous_page, $items_per_page");
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $previous_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
} elseif ($datain == "linkappdownlod") {
    $countapp = select("app", "*", null, null, "count");
    if ($countapp == 0) {
        sendmessage($from_id, $textbotlang['users']['app']['appempty'], $json_list_helpـlink, "html");
        return;
    }
    sendmessage($from_id, $textbotlang['users']['app']['selectapp'], $json_list_helpـlink, "html");
} elseif (preg_match('/changenote_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    update("user", "Processing_value", $id_invoice, "id", $from_id);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['note']['SendNote'], $backinfoss);
    step("getnotedit", $from_id);
} elseif ($user['step'] == "getnotedit") {
    $invoice = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (strlen($text) > 150) {
        sendmessage($from_id, $textbotlang['users']['note']['ErrorLongNote'], $keyboard, "html");
        return;
    }
    $text = sanitizeUserName($text);
    $id_invoice = $user['Processing_value'];
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    update("invoice", "note", $text, "id_invoice", $id_invoice);
    sendmessage($from_id, $textbotlang['users']['note']['changednote'], $backinfoss, "html");
    step("home", $from_id);
    $timejalali = jdate('Y/m/d H:i:s');
    $textreport = "📌  یک کاربر یادداشت سرویس خود را تغییر داد.

▫️ نام کاربری سرویس : {$invoice['username']}
▫️ یاداشت قبلی :‌ {$invoice['note']}
▫️ یاداشت جدید :‌  $text

زمان تغییر یادداشت : $timejalali ";
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $textreport,
            'reply_markup' => $Response,
            'parse_mode' => "HTML"
        ]);
    }
}
if (isset($update['pre_checkout_query'])) {
    $userid = $update['pre_checkout_query']['from']['id'];
    $id_order = $update['pre_checkout_query']['invoice_payload'];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    if ($Payment_report == false) {
        return;
    } else {
        telegram('answerPreCheckoutQuery', [
            'pre_checkout_query_id' => $update['pre_checkout_query']['id'],
            'ok' => true,
        ]);
    }
    if ($Payment_report['payment_Status'] == "paid") {
        return;
    }
    update("Payment_report", "dec_not_confirmed", json_encode($update['pre_checkout_query']), "id_order", $Payment_report['id_order']);
    DirectPayment($Payment_report['id_order']);
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackstar", "select")['ValuePay'];
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($pricecashback != "0") {
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) + $result;
        update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
        $text_report = sprintf($textbotlang['users']['Discount']['gift-deposit'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => sprintf($textbotlang['Admin']['reportgroup']['new-payment-star'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price'], $update['pre_checkout_query']['total_amount']),
            'parse_mode' => "HTML"
        ]);
    }
    update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
} elseif (preg_match('/extends_(\w+)_(.*)/', $datain, $dataget)) {
    $username = $dataget[1];
    $location = select("marzban_panel", "*", "code_panel", $user['Processing_value_four'], "select");
    if ($location == false) {
        sendmessage($from_id, "❌ خطایی رخ داده است مراحل را از اول طی کنید", null, 'html');
        return;
    }
    $location = $location['name_panel'];
    update("user", "Processing_value", $location, "id", $from_id);
    $query = "SELECT * FROM product WHERE (Location = '$location' OR Location = '/all')";
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $location, "select");
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername'] || $marzban_list_get['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
        $datakeyboard = "prodcutservicesom_";
    } else {
        $datakeyboard = "prodcutserviceom_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], "serviceextendselects-", false, "backuser", $username));
} elseif (preg_match('/^serviceextendselects-(.*)-(.*)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    $codeproduct = $dataget[1];
    $username = $dataget[2];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :processing_value OR Location = '/all') AND code_product = :code_product");
    $stmt->execute([
        ':processing_value' => $user['Processing_value'],
        ':code_product' => $codeproduct,
    ]);
    $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($prodcut == false) {
        sendmessage($from_id, $textbotlang['users']['erroroccurred'], $keyboard, 'html');
        return;
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivces-" . $codeproduct . "-" . $username],
            ]
        ]
    ]);
    sendmessage($from_id, sprintf($textbotlang['users']['extend']['renewalinvoice'], $username, $prodcut['name_product'], $prodcut['price_product'], $prodcut['Service_time'], $prodcut['Volume_constraint'], $prodcut['note'], $user['Balance']), $keyboardextend, 'html');
} elseif (preg_match('/^confirmserivces-(.*)-(.*)/', $datain, $dataget)) {
    $codeproduct = $dataget[1];
    $usernamePanelExtends = $dataget[2];
    deletemessage($from_id, $message_id);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :processing_value OR Location = '/all') AND code_product = :code_product");
    $stmt->execute([
        ':processing_value' => $user['Processing_value'],
        ':code_product' => $codeproduct,
    ]);
    $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $usernamePanelExtends);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['extend']['renewalerror'], $keyboard, 'HTML');
        return;
    }
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['renewalerror'], $keyboard, 'HTML');
        return;
    }
    if ($user['Balance'] < $prodcut['price_product'] && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['stateus']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($prodcut['price_product'] * $user['pricediscount']) / 100;
                $prodcut['price_product'] = $prodcut['price_product'] - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $prodcut['price_product'] - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $prodcut['price_product']) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($prodcut['price_product'] * $user['pricediscount']) / 100;
        $prodcut['price_product'] = $prodcut['price_product'] - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $prodcut['price_product'];
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $usernamePanelExtends, $prodcut['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = "خطای تمدید سرویس
        نام پنل : {$marzban_list_get['name_panel']}
        نام کاربری سرویس : $usernamePanelExtends
        دلیل خطا : {$extend['msg']}";
        sendmessage($from_id, "❌خطایی در تمدید سرویس رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($setting['Channel_Report'] ?? '') > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price,output) VALUES (:id_user, :username, :value, :type, :time, :price,:output)");
    $value = json_encode(array(
        "volumebuy" => $prodcut['Volume_constraint'],
        "Service_time" => $prodcut['Service_time'],
        "oldvolume" => $DataUserOut['data_limit'],
        "oldtime" => $DataUserOut['expire'],
        'code_product' => $prodcut['code_product'],
    ));
    $dateacc = date('Y/m/d H:i:s');
    $type = "extends_not_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $usernamePanelExtends,
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $prodcut['price_product'],
        ':output' => json_encode($extend)
    ]);
    $prodcut['price_product'] = number_format($prodcut['price_product']);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $textextend = "✅ تمدید برای سرویس شما با موفقیت صورت گرفت
 
▫️نام سرویس : $usernamePanelExtends
▫️نام محصول : {$prodcut['name_product']}
▫️مبلغ تمدید {$prodcut['price_product']} تومان
";
    sendmessage($from_id, $textextend, $keyboard, 'HTML');
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['renewaldetails'], $from_id, $username, $usernamePanelExtends, $first_name, $marzban_list_get['name_panel'], $prodcut['name_product'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $prodcut['price_product'], $balanceformatsell, $timejalali);
    if (strlen($setting['Channel_Report'] ?? '') > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
}
if (in_array($from_id, $admin_ids))
    require_once 'admin.php';

$pdo = null;
$connect->close();
