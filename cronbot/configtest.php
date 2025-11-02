<?php
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();
$datatextbotget = select("textbot", "*",null ,null ,"fetchAll");
$datatxtbot = array();
$setting = select("setting","*",null,null);
$status_cron = json_decode($setting['cron_status'],true);
if(!$status_cron['test'])return;
foreach ($datatextbotget as $row) {
    $datatxtbot[] = array(
        'id_text' => $row['id_text'],
        'text' => $row['text']
    );
}
$datatextbot = array(
    'crontest' => '',
);
foreach ($datatxtbot as $item) {
    if (isset($datatextbot[$item['id_text']])) {
        $datatextbot[$item['id_text']] = $item['text'];
    }
}
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE status != 'disabled' AND name_product = 'سرویس تست' ORDER BY RAND() LIMIT 15");
        $stmt->execute();
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultt  = trim($result['username']);
        $marzban_list_get = select("marzban_panel","*","name_panel",$result['Service_location'],"select");
        if($marzban_list_get == false)continue;
        $user = select("user","*","id",$result['id_user'],"select");
        $get_username_Check = $ManagePanel->DataUser($result['Service_location'],$result['username']);
    if (!in_array($get_username_Check['status'],['active','on_hold',"Unsuccessful","disabled"])) {
            $ManagePanel->RemoveUser($result['Service_location'],$resultt);
        update("invoice","status","disabled","username",$resultt);
        if(intval($user['status_cron']) != 0){
         $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "🛍 خرید سرویس", 'callback_data' => 'buy'],
            ],
        ]
    ]);
        $textexpire = str_replace('{username}', $resultt, $datatextbot['crontest']);
        sendmessage($result['id_user'], $textexpire, $Response, 'HTML');
        }
    }
}