<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM setting"));
$paymentreports = select("topicid","idreport","report","paymentreport","select")['idreport'];
$datatextbotget = select("textbot", "*",null ,null ,"fetchAll");
    $datatxtbot = array();
foreach ($datatextbotget as $row) {
    $datatxtbot[] = array(
        'id_text' => $row['id_text'],
        'text' => $row['text']
    );
}
$datatextbot = array(
    'textafterpay' => '',
    'textaftertext' => '',
    'textmanual' => '',
    'textselectlocation' => '',
    'text_wgdashboard' => '',
    'textafterpayibsng' => ''
);
foreach ($datatxtbot as $item) {
    if (isset($datatextbot[$item['id_text']])) {
        $datatextbot[$item['id_text']] = $item['text'];
    }
}
$textbotlang = languagechange('../text.json');
$list_service = mysqli_query($connect, "SELECT * FROM Payment_report WHERE payment_Status = 'Unpaid' AND Payment_Method = 'Currency Rial 3' ORDER BY RAND() LIMIT 10");
while ($Payment_report = mysqli_fetch_assoc($list_service)) {
    if ($Payment_report['payment_Status'] == "paid")return;
    $StatusPayment = verifpay($Payment_report['dec_not_confirmed']);
    if(!is_string($StatusPayment))continue;
    $StatusPayment = json_decode($StatusPayment,true);
    if(!is_array($StatusPayment))continue;
    if(!$StatusPayment['success'])continue;
    if($StatusPayment['data']['status'] != "approved")continue;
    update("Payment_report","dec_not_confirmed",json_encode($StatusPayment['data']),"id_order",$Payment_report['id_order']);
    DirectPayment($Payment_report['id_order']);
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackiranpay1","select")['ValuePay'];
    $Balance_id = select("user","*","id",$Payment_report['id_user'],"select");
    if($pricecashback != "0"){
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) +$result ;
        update("user","Balance",$Balance_confrim, "id",$Balance_id['id']); 
        $text_report = "🎁 کاربر عزیز مبلغ $result تومان به عنوان هدیه واریز به حساب شما واریز گردید.";
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
        $text_reportpayment = "💵 پرداخت جدید
- 👤 نام کاربری کاربر : @{$Balance_id['username']}
- ‏🆔آیدی عددی کاربر : {$Balance_id['id']}
- 💸 مبلغ تراکنش {$Payment_report['price']}
- 💳 روش پرداخت :  ارزی ریالی سوم";
         if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_reportpayment,
        'parse_mode' => "HTML"
        ]);
    }
        update("Payment_report","dec_not_confirmed",json_encode($StatusPayment),"id_order",$Payment_report['id_order']);
        update("Payment_report","payment_Status","paid","id_order",$Payment_report['id_order']);

}