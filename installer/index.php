<?php

$uPOST = sanitizeInput($_POST);
$rootDirectory = dirname(__DIR__).'/';

$configDirectory = $rootDirectory.'config.php';
$tablesDirectory = $rootDirectory.'table.php';
if(!file_exists($configDirectory) || !file_exists($tablesDirectory)) {
    $ERROR[] = "فایل های پروژه ناقص هستند.";
    $ERROR[] = "فایل های پروژه را مجددا دانلود و بارگذاری کنید (<a href='https://github.com/mahdiMGF2/botmirzapanel'>‎🌐 Github</a>)";
}
if(phpversion() < 8.2){
    $ERROR[] = "نسخه PHP شما باید حداقل 8.2 باشد.";
    $ERROR[] = "نسخه فعلی: ".phpversion();
    $ERROR[] = "لطفا نسخه PHP خود را به 8.2 یا بالاتر ارتقا دهید.";
}

if(!empty($_SERVER['SCRIPT_URI'])) {
    $URI = str_replace($_SERVER['REQUEST_SCHEME'].'://','',$_SERVER['SCRIPT_URI']);
    if(basename($URI) == 'index.php') {
        $URI = (dirname($URI));
    }
    $webAddress = (dirname($URI)).'/';
}
else {
    $webAddress = $_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
}

$success = false;
$tgBot = [];
$botFirstMessage = '';

if(isset($uPOST['submit']) && $uPOST['submit']) {

    $ERROR = [];
    $SUCCESS[] = "✅ ربات با موفقیت نصب شد !";
    $rawConfigData = file_get_contents($configDirectory);

    $tgAdminId = $uPOST['admin_id'];
    $tgBotToken = $uPOST['tg_bot_token'];
    $dbInfo['host'] = 'localhost';
    $dbInfo['name'] = $uPOST['database_name'];
    $dbInfo['username'] = $uPOST['database_username'];
    $dbInfo['password'] = $uPOST['database_password'];
    $document = normalizeDomainAddress($uPOST['bot_address_webhook']);
    if ($document === null) {
        $ERROR[] = 'آدرس ارائه شده برای ربات نامعتبر است.';
    }

    if($_SERVER['REQUEST_SCHEME'] != 'https') {
        $ERROR[] = 'برای فعال سازی ربات تلگرام نیازمند فعال بودن SSL (https) هستید';
        $ERROR[] = '<i>اگر از فعال بودن SSL مطمئن هستید آدرس صفحه را چک کنید، حتما با https صفحه را باز کنید.</i>';
        $ERROR[] = '<a href="https://'.$_SERVER['HTTP_HOST'].'/'.$_SERVER['SCRIPT_NAME'].'">https://'.$_SERVER['HTTP_HOST'].'/'.$_SERVER['SCRIPT_NAME'].'</a>';
    }

    $isValidToken = isValidTelegramToken($tgBotToken);
    if(!$isValidToken) {
        $ERROR[] = "توکن ربات صحیح نمی باشد.";
    }

    if (!isValidTelegramId($tgAdminId)) {
        $ERROR[] = "آیدی عددی ادمین نامعتبر است.";
    }

    if($isValidToken) {
        $tgBot['details'] = getContents("https://api.telegram.org/bot".$tgBotToken."/getMe");
        if($tgBot['details']['ok'] == false) {
            $ERROR[] = "توکن ربات را بررسی کنید. <i>عدم توانایی دریافت جزئیات ربات.</i>";
        }
        else {
            $tgBot['recognitionion'] = getContents("https://api.telegram.org/bot".$tgBotToken."/getChat?chat_id=".$tgAdminId);
            if($tgBot['recognitionion']['ok'] == false) {
                $ERROR[] = "<b>عدم شناسایی مدیر ربات:</b>";
                $ERROR[] = "ابتدا ربات را فعال/استارت کنید با اکانت که میخواهید مدیر اصلی ربات باشد.";
                $ERROR[] = "<a href='https://t.me/'".$tgBot['details']['result']['username'].">@".$tgBot['details']['result']['username']."</a>";
            }
        }
    }


    try {
        $dsn = "mysql:host=" . $dbInfo['host'] . ";dbname=" . $dbInfo['name'] . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $SUCCESS[] = "✅ اتصال به دیتابیس موفقیت آمیز بود!";
    }
    catch (\PDOException $e) {
        $ERROR[] = "❌ عدم اتصال به دیتابیس: ";
        $ERROR[] = "اطلاعات ورودی را بررسی کنید.";
        $ERROR[] = "<code>".$e->getMessage()."</code>";
    }

    if(empty($ERROR)) {
        $replacements = [
            '{database_name}' => $dbInfo['name'],
            '{username_db}' => $dbInfo['username'],
            '{password_db}' => $dbInfo['password'],
            '{API_KEY}' => $tgBotToken,
            '{admin_number}' => $tgAdminId,
            '{domain_name}' => $document['address'],
            '{username_bot}' => $tgBot['details']['result']['username']
        ];

        $replacementCount = 0;
        $newConfigData = updateConfigValues($rawConfigData, $replacements, $replacementCount);

        if($replacementCount === 0 || file_put_contents($configDirectory,$newConfigData) === false) {
            $ERROR[] = '✏️❌ خطا در زمان بازنویسی اطلاعات فایل اصلی ربات';
            $ERROR[] = "فایل های پروژه را مجددا دانلود و بارگذاری کنید (<a href='https://github.com/mahdiMGF2/botmirzapanel'>‎🌐 Github</a>)";
        }
        else {
            getContents("https://api.telegram.org/bot".$tgBotToken."/setwebhook?url=https://".$document['address'].'/index.php');
            getContents("https://".$document['address']."/table.php");
            $botFirstMessage = "\n[🤖] شما به عنوان ادمین معرفی شدید.";
            getContents('https://api.telegram.org/bot'.$tgBotToken.'/sendMessage?chat_id='.$tgAdminId.'&text='.urlencode(' '.$SUCCESS[0].$botFirstMessage).'&reply_markup={"inline_keyboard":[[{"text":"⚙️  شروع ربات ","callback_data":"start"}]]}');

            $success = true;
        }

    }
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ نصب خودکار ربات میرزا</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>⚙️ نصب خودکار ربات میرزا</h1>
        
        <?php if (!empty($ERROR)): ?>
            <div class="alert alert-danger">
                <?php echo implode("<br>",$ERROR); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo implode("<br>",$SUCCESS); ?>
            </div>
            <a class="submit-success" href="https://t.me/<?php echo $tgBot['details']['result']['username']; ?>">🤖 رفتن به ربات <?php echo "‎@".$tgBot['details']['result']['username']; ?> »</a>
            <div style="text-align: center; margin-top: 20px; font-size: 18px; color: #28a745;">
                <p>نصب با موفقیت تکمیل شد! 🎉</p>
                <p>پوشه Installer بعد از <span id="countdown">10</span> ثانیه به طور خودکار حذف خواهد شد.</p>
            </div>
            <script>
                let timeLeft = 10;
                const countdownElement = document.getElementById('countdown');
                const timer = setInterval(() => {
                    timeLeft--;
                    countdownElement.textContent = timeLeft;
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        window.location.href = 'delete_installer.php';
                    }
                }, 1000);
            </script>
        <?php endif; ?>
            
            <form id="installer-form" <?php if($success) { echo 'style="display:none;"'; } ?> method="post">
                <div class="form-group">
                    <label for="admin_id">آیدی عددی ادمین:</label>
                    <input type="text" id="admin_id" name="admin_id"
                           placeholder="ADMIN TELEGRAM #Id" value="<?php echo escapeHtml($uPOST['admin_id'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="tg_bot_token">توکن ربات تلگرام :</label>
                    <input type="text" id="tg_bot_token" name="tg_bot_token"
                           placeholder="BOT TOKEN" value="<?php echo escapeHtml($uPOST['tg_bot_token'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="database_username">نام کاربری دیتابیس :</label>
                    <input type="text" id="database_username" name="database_username"
                           placeholder="DATABASE USERNAME" value="<?php echo escapeHtml($uPOST['database_username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="database_password">رمز عبور  دیتابیس :</label>
                    <input type="text" id="database_password" name="database_password"
                           placeholder="DATABASE PASSOWRD" value="<?php echo escapeHtml($uPOST['database_password'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="database_name">نام دیتابیس :</label>
                    <input type="text" id="database_name" name="database_name"
                           placeholder="DATABASE NAME" value="<?php echo escapeHtml($uPOST['database_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <details>
                        <summary for="secret_key"><i>آدرس سورس ربات</i></summary>
                        <label for="bot_address_webhook ">آدرس صفحه سورس ربات</label>
                        <input type="text" id="bot_address_webhook" name="bot_address_webhook" placeholder="Web URL for Set Webhook" value="<?php echo escapeHtml($uPOST['bot_address_webhook'] ?? ($webAddress.'/index.php')); ?>" required>
                    </details>
                </div>
                <div class="form-group">
                    <label for="remove_directory"><b style="color:#f30;">هشدار:</b> حذف خودکار اسکریپت نصب&zwnj;کننده پس از نصب موفقیت&zwnj;آمیز</label>
                    <label for="remove_directory" style="font-size: 14px;font-weight: normal;text-indent: 20px;">برای امنیت بیشتر، بعد از اتمام نصب ربات پوشه Installer حذف خواهد شد. </label>
                </div>
                
                <button type="submit" name="submit" value="submit">نصب ربات</button>
            </form>
        <footer>
            <p>Mirzabot Installer , Made by ♥️ | <a href="https://github.com/mahdiMGF2/botmirzapanel">Github</a> | <a href="https://t.me/mirzapanel">Telegram</a> | &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>

<?php 

function getContents($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
        ],
        'https' => [
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return ['ok' => false];
    }

    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['ok' => false];
    }

    return $decoded;
}
function isValidTelegramToken($token) {
    return preg_match('/^\d{6,12}:[A-Za-z0-9_-]{35}$/', $token);
}
function isValidTelegramId($id) {
    return preg_match('/^\d{6,12}$/', $id);
}
function sanitizeInput(&$INPUT, array $options = []) {

    $defaultOptions = [
        'allow_html' => false,
        'allowed_tags' => '',
        'remove_spaces' => false,
        'connection' => null,
        'max_length' => 0,
        'encoding' => 'UTF-8'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    if (is_array($INPUT)) {
        return array_map(function($item) use ($options) {
            return sanitizeInput($item, $options);
        }, $INPUT);
    }
    
    if ($INPUT === null || $INPUT === false) {
        return '';
    }
    
    $INPUT = trim((string)$INPUT);
    
    $INPUT = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $INPUT);
    
    if ($options['max_length'] > 0) {
        $INPUT = mb_substr($INPUT, 0, $options['max_length'], $options['encoding']);
    }
    
    if (!$options['allow_html']) {
        $INPUT = strip_tags($INPUT);
    } elseif (!empty($options['allowed_tags'])) {
        $INPUT = strip_tags($INPUT, $options['allowed_tags']);
    }
    
    if ($options['remove_spaces']) {
        $INPUT = preg_replace('/\s+/', ' ', trim($INPUT));
    }
    
    if ($options['connection'] instanceof mysqli) {
        $INPUT = $options['connection']->real_escape_string($INPUT);
    }
    
    return $INPUT;
}

function normalizeDomainAddress($url) {
    $url = trim((string) $url);

    if ($url === '') {
        return null;
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    $parsedUrl = parse_url($url);

    if (empty($parsedUrl['host'])) {
        return null;
    }

    $path = $parsedUrl['path'] ?? '';
    $path = preg_replace('#/index\.php$#i', '', $path);
    $path = rtrim($path, '/');

    $address = $parsedUrl['host'];
    if ($path !== '') {
        $address .= $path;
    }

    return [
        'address' => $address
    ];
}

function updateConfigValues($configContents, array $placeholderValues, &$replacementCount = 0) {
    $replacementCount = 0;

    $configData = str_replace(array_keys($placeholderValues), array_values($placeholderValues), $configContents, $placeholderReplacementCount);

    if ($placeholderReplacementCount > 0) {
        $replacementCount += $placeholderReplacementCount;
    }

    $variableMap = [
        'dbname' => $placeholderValues['{database_name}'] ?? '',
        'usernamedb' => $placeholderValues['{username_db}'] ?? '',
        'passworddb' => $placeholderValues['{password_db}'] ?? '',
        'APIKEY' => $placeholderValues['{API_KEY}'] ?? '',
        'adminnumber' => $placeholderValues['{admin_number}'] ?? '',
        'domainhosts' => $placeholderValues['{domain_name}'] ?? '',
        'usernamebot' => $placeholderValues['{username_bot}'] ?? '',
    ];

    $updatedConfig = $configData;

    foreach ($variableMap as $variable => $value) {
        $pattern = '/(\$' . preg_quote($variable, '/') . '\s*=\s*)([\'\"])(.*?)(\2)(\s*;)([^\n]*)(\n?)/u';
        $updatedConfig = preg_replace_callback(
            $pattern,
            function ($matches) use ($value, &$replacementCount) {
                $replacementCount++;
                $quoteChar = $matches[2];
                $formattedValue = formatConfigValue($value, $quoteChar);
                return $matches[1] . $formattedValue . $matches[5] . $matches[6] . $matches[7];
            },
            $updatedConfig,
            1
        );
    }

    return $updatedConfig;
}

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatConfigValue($value, $quoteChar = '\'') {
    if ($value === null) {
        return 'null';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if ($quoteChar !== "'" && $quoteChar !== '"') {
        $quoteChar = "'";
    }

    $stringValue = (string) $value;
    $escapedValue = addcslashes($stringValue, "\\$quoteChar");

    return $quoteChar . $escapedValue . $quoteChar;
}
?>

