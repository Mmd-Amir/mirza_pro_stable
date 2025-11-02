<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$setting = select("setting","*",null,null,"select");

//________________[ time 12 report]________________
$midnight_time = date("H:i");
$reportnight = select("topicid","idreport","report","reportnight","select")['idreport'];
// if(true){
if ($midnight_time >= "23:45") {
$datefirst = date("Y-m-d") . " 00:00:00";
$dateend = date("Y-m-d") . " 23:59:59";

// Helper function to execute a prepared statement
function executeQuery($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt;
}

// Fetch count and sum for invoices
$sqlInvoices = "SELECT COUNT(*) AS count, SUM(price_product) AS total_price, SUM(Volume) AS total_volume 
                FROM invoice 
                WHERE (FROM_UNIXTIME(time_sell) BETWEEN :startDate AND :endDate) 
                AND (status IN ('active', 'end_of_time', 'sendedwarn', 'send_on_hold')) 
                AND name_product != 'سرویس تست'";
$params = [':startDate' => $datefirst, ':endDate' => $dateend];
$stmt = executeQuery($pdo, $sqlInvoices, $params);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$dayListSell = $result['count'] ?? 0;
$suminvoiceday = $result['total_price'] ?? 0;
$sumvolume = $result['total_volume'] ?? 0;

// Fetch test service count
$sqlTestService = "SELECT COUNT(*) AS count 
                  FROM invoice 
                  WHERE (FROM_UNIXTIME(time_sell) BETWEEN :startDate AND :endDate) 
                  AND (status IN ('active', 'end_of_time', 'sendedwarn')) 
                  AND name_product = 'سرویس تست'";
$stmt = executeQuery($pdo, $sqlTestService, $params);
$dayListSelltest = $stmt->fetchColumn() ?? 0;

// Fetch new users count
$sqlNewUsers = "SELECT COUNT(*) AS count 
                 FROM user 
                 WHERE (FROM_UNIXTIME(register) BETWEEN :startDate AND :endDate)";
$stmt = executeQuery($pdo, $sqlNewUsers, $params);
$usernew = $stmt->fetchColumn() ?? 0;

// Fetch extension data
$datefirstextend = date("Y/m/d") . " 00:00:00";
$dateendextend = date("Y/m/d") . " 23:59:59";

$sqlExtensions = "SELECT COUNT(*) AS count, SUM(price) AS total_price 
                  FROM service_other 
                  WHERE (time BETWEEN :startDate AND :endDate) 
                  AND type = 'extend_user'
                  AND status != 'unpaid'";
$params = [':startDate' => $datefirstextend, ':endDate' => $dateendextend];
$stmt = executeQuery($pdo, $sqlExtensions, $params);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$countextendday = $result['count'] ?? 0;
$sumcountextend = number_format($result['total_price'] ?? 0);

// Fetch top agents
$sqlTopAgents = "
    SELECT u.id, u.username, 
           (SELECT SUM(i.price_product) 
            FROM invoice i 
            WHERE i.id_user = u.id 
            AND (i.time_sell BETWEEN :startDate1 AND :endDate1) 
            AND i.status IN ('active', 'end_of_time', 'sendedwarn', 'send_on_hold')) AS total_spent 
    FROM user u 
    WHERE u.agent IN ('n', 'n2') 
    AND EXISTS (SELECT 1 
                FROM invoice i 
                WHERE i.id_user = u.id 
                AND (i.time_sell BETWEEN :startDate2 AND :endDate2) 
                AND i.status IN ('active', 'end_of_time', 'sendedwarn', 'send_on_hold')) 
    ORDER BY total_spent DESC 
    LIMIT 3";

$params = [
    ':startDate1' => strtotime($datefirstextend),
    ':endDate1' => strtotime($dateendextend),
    ':startDate2' => strtotime($datefirstextend),
    ':endDate2' => strtotime($dateendextend)
];

$stmt = executeQuery($pdo, $sqlTopAgents, $params);
$listagentuser = $stmt->fetchAll(PDO::FETCH_ASSOC);
$textagent = "لیست نمایندگانی که بیشترین خرید در امروز داشتند :\n";
foreach ($listagentuser as $agent) {
    $textagent .= "\nایدی عددی کاربر : {$agent['id']}\nنام کاربری کاربر : {$agent['username']}\nجمع کل خرید امروز : {$agent['total_spent']}\n---------------\n";
}

// Fetch panel reports
$panels = select("marzban_panel", "*", null, null, "fetchAll");
$textpanel = "گزارش پنل ها :\n";
foreach ($panels as $panel) {
    $sqlPanel = "SELECT COUNT(*) AS orders, SUM(price_product) AS total_price, SUM(Volume) AS total_volume 
                 FROM invoice 
                 WHERE (FROM_UNIXTIME(time_sell) BETWEEN :startDate AND :endDate) 
                 AND (status IN ('active', 'end_of_time', 'sendedwarn', 'send_on_hold')) 
                 AND Service_location = :location 
                 AND name_product != 'سرویس تست'";
    $params = [':startDate' => $datefirst, ':endDate' => $dateend, ':location' => $panel['name_panel']];
    $stmt = executeQuery($pdo, $sqlPanel, $params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $orders = $result['orders'] ?? 0;
    $total_price = $result['total_price'] ?? 0;
    $total_volume = $result['total_volume'] ?? 0;

    $textpanel .= "\nنام پنل : {$panel['name_panel']}\n🛍 تعداد سفارشات امروز : $orders عدد\n🛍 جمع مبلغ سفارشات امروز : $total_price تومان\n🔋 جمع حجم های فروخته شده : $total_volume گیگابایت\n---------------\n";
}

// Daily report text
$textreport = "📌 گزارش روزانه کارکرد ربات :\n\n🧲 تعداد تمدید امروز : $countextendday عدد\n💰 جمع تمدید امروز : $sumcountextend تومان\n🛍 تعداد سفارشات امروز : $dayListSell عدد\n🛍 جمع مبلغ سفارشات امروز : $suminvoiceday تومان\n🔑 اکانت های تست امروز : $dayListSelltest عدد\n🔋 جمع حجم های فروخته شده : $sumvolume گیگابایت\nتعداد کاربرانی که امروز به ربات پیوستند : $usernew نفر\n";

// Send reports to Telegram
if (!empty($setting['Channel_Report'])) {
    $report_data = [
        ['text' => $textagent],
        ['text' => $textreport],
        ['text' => $textpanel]
    ];

    foreach ($report_data as $report) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reportnight,
            'text' => $report['text'],
            'parse_mode' => "HTML"
        ]);
    }
}
}