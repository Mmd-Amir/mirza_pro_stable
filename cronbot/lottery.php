<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../jdf.php';

$setting = select('setting', '*');
$midnight_time = date('H:i');

if (intval($setting['scorestatus']) === 1) {
    $otherreport = select('topicid', 'idreport', 'report', 'otherreport', 'select')['idreport'];

    if ($midnight_time === '00:00') {
        $lotteryPrizeConfig = json_decode($setting['Lottery_prize'], true);

        if (!is_array($lotteryPrizeConfig)) {
            error_log('Lottery prize configuration is invalid.');
            return;
        }

        $Lottery_prize = array_values(array_filter($lotteryPrizeConfig, static function ($value) {
            return $value !== null && $value !== '';
        }));

        if (count($Lottery_prize) === 0) {
            error_log('Lottery prize configuration is empty.');
            return;
        }

        if ($setting['Lotteryagent'] === '1') {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE User_Status = 'Active' AND score != '0' ORDER BY score DESC LIMIT 3");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE User_Status = 'Active' AND score != '0' AND agent = 'f' ORDER BY score DESC LIMIT 3");
        }

        $stmt->execute();

        $count = 0;
        $winners = 0;
        $textlotterygroup = "📌 ادمین عزیز کاربران زیر برنده قرعه کشی و حسابشان شارژ گردید." . PHP_EOL . PHP_EOL;
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!array_key_exists($count, $Lottery_prize)) {
                break;
            }

            $prizeAmount = (int) $Lottery_prize[$count];
            if ($prizeAmount <= 0) {
                $count++;
                continue;
            }

            $balance_last = (int) $result['Balance'] + $prizeAmount;
            update('user', 'Balance', $balance_last, 'id', $result['id']);

            $formattedPrize = number_format($prizeAmount);
            $countla = $count + 1;
            $textlottery = "🎁 نتیجه قرعه کشی" . PHP_EOL . PHP_EOL . "😎 کاربر عزیز تبریک شما  نفر $countla برنده $formattedPrize تومان موجودی شدید و حساب شما شارژ گردید.";
            sendmessage($result['id'], $textlottery, null, 'html');

            $count++;
            $winners++;

            $textlotterygroup .= PHP_EOL
                . "نام کاربری : @{$result['username']}" . PHP_EOL
                . "آیدی عددی : {$result['id']}" . PHP_EOL
                . "مبلغ : $formattedPrize" . PHP_EOL
                . "نفر : $countla" . PHP_EOL
                . "--------------";
        }

        if ($winners > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherreport,
                'text' => $textlotterygroup,
                'parse_mode' => 'HTML',
            ]);

            update('user', 'score', '0', null, null);
        }
    }
}

