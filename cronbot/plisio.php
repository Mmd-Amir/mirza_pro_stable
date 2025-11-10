<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*");
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

function statusplisio($tx_id){
    global $connect;
$apinowpayments = mysqli_fetch_assoc(mysqli_query($connect, "SELECT (ValuePay) FROM PaySetting WHERE NamePay = 'apinowpayment'"))['ValuePay'];
$api_key = $apinowpayments;
$url = 'https://api.plisio.net/api/v1/operations?';
$url .= '&api_key=' . urlencode($api_key);
$url .= '&search='.$tx_id;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
return json_decode($response,true);
curl_close($ch);

}
$list_service = mysqli_query($connect, "SELECT * FROM Payment_report WHERE payment_Status = 'Unpaid' AND Payment_Method = 'plisio'");
while ($row = mysqli_fetch_assoc($list_service)) {
    $Payment_report = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM Payment_report WHERE id_order = '{$row['id_order']}' LIMIT 1"));
    $textbotlang = languagechange('../text.json');
    if ($Payment_report['payment_Status'] == "paid")continue;
    if(!isset($Payment_report['dec_not_confirmed']) or $Payment_report['dec_not_confirmed'] == null)continue;
    if($Payment_report['dec_not_confirmed'] == null)continue;
    $StatusPayment = statusplisio($Payment_report['id_order']);
    if($StatusPayment['data']['operations'][0]['status'] == null || $StatusPayment['data']['operations'][0]['status'] == "cancelled"){
    $textexpire = "❌ تراکنش زیر بدلیل عدم پرداخت منقضی شد، لطفا وجهی بابت این تراکنش پرداخت نکنید

🛒 کد سفارش: {$Payment_report['id_order']}
💰 مبلغ:  {$Payment_report['price']} تومان";
    sendmessage($Payment_report['id_user'], $textexpire, null, 'html');
    update("Payment_report","payment_Status","expire","id_order",$Payment_report['id_order']);
}
    if (isset($StatusPayment['data']['operations'][0]['status']) && $StatusPayment['data']['operations'][0]['status'] == "completed") {
        DirectPayment($Payment_report['id_order'],"../images.jpg");
        $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackplisio","select")['ValuePay'];
    $Balance_id = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM user WHERE id = '{$Payment_report['id_user']}' LIMIT 1"));
    if($pricecashback != "0"){
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) +$result;
        update("user","Balance",$Balance_confrim, "id",$Balance_id['id']); 
        $pricecashback =  number_format($pricecashback);
        $text_report = "🎁 کاربر عزیز مبلغ $result تومان به عنوان هدیه واریز به حساب شما واریز گردید.";
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $text_reportpayment = "💵 پرداخت جدید
- 👤 نام کاربری کاربر : @{$Balance_id['username']}
- ‏🆔آیدی عددی کاربر : {$Balance_id['id']}
- 💸 مبلغ تراکنش {$Payment_report['price']}
- 🔗 <a href = \"{$StatusPayment['tx_url'][0]}\">لینک پرداخت </a>
- 🔗 <a href = \"{$StatusPayment['invoice_url']}\">لینک پرداخت plisio </a>
- 📥 مبلغ واریز شده ترون. : {$StatusPayment['invoice_total_sum']}
- 💳 روش پرداخت :  plisio";
         if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_reportpayment,
        'parse_mode' => "HTML"
        ]);
    }
        update("Payment_report","payment_Status","paid","id_order",$Payment_report['id_order']);
}
}