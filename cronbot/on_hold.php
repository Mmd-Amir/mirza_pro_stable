<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();

$setting = select("setting", "*");
// buy service 
$stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE type = 'marzban'  ORDER BY RAND() LIMIT 25");
$stmt->execute();
        while ($panel = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users = getusers($panel['name_panel'],"on_hold")['users'];
        foreach($users as $user){
        $invoice = select("invoice","*","username",$user['username'],"select");
        if($invoice == false )continue;
        if($invoice['Status'] == "send_on_hold")continue;
        $line  = $invoice['username'];
        $resultss = $invoice;
        $marzban_list_get = $panel;
        $get_username_Check = $user;
        if($get_username_Check['status'] != "Unsuccessful"){
        if(in_array($get_username_Check['status'],['on_hold'])){
            $timebuyremin = (time() - $resultss['time_sell'])/86400;
        if ($timebuyremin >= $setting['on_hold_day']) {
        $sql = "SELECT * FROM service_other WHERE username = :username  AND type = 'change_location'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $line ,PDO::PARAM_STR);
        $stmt->execute();
        $service_other = $stmt->rowCount();
        if($service_other != 0)continue;
                $text = "سلام! 🌐

دیدیم که شما هنوز به کانفیگ خود با نام کاربری $line متصل نشده‌اید و بیش از {$setting['on_hold_day']} روز از فعال‌سازی آن گذشته است. اگر در راه‌اندازی یا استفاده از سرویس مشکلی دارید، لطفاً با تیم پشتیبانی ما  از طریق آیدی زیر در ارتباط باشید تا به شما کمک کنیم.
ما آماده‌ایم تا هر گونه سوال یا مشکلی را برطرف کنیم! 📞

اکانت پشتیبانی : @{$setting['id_support']}";
            sendmessage($resultss['id_user'], $text, null, 'HTML');
            update("invoice","Status","send_on_hold", "username",$line);
            }
        }
        }
}
}