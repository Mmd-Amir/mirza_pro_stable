<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../function.php';
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);
$query = $pdo->prepare("SELECT * FROM invoice");
$query->execute();
$listinvoice = $query->fetchAll();
if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}


$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDirectory = str_replace('\\', '/', dirname($scriptName));
$applicationBasePath = str_replace('\\', '/', dirname($scriptDirectory));
$applicationBasePath = rtrim($applicationBasePath, '/');
if ($applicationBasePath === '/' || $applicationBasePath === '.' || $applicationBasePath === '\\') {
    $applicationBasePath = '';
}

$keyboard = json_decode(file_get_contents("php://input"),true);
$method = $_SERVER['REQUEST_METHOD'];
if($method == "POST" && is_array($keyboard)){
    $keyboardmain = ['keyboard' => []];
    $keyboardmain['keyboard'] = $keyboard;
    update("setting","keyboardmain",json_encode($keyboardmain),null,null);
}else{
    $keyboardmain = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}]]}';
    $action = filter_input(INPUT_GET, 'action');
    if($action === "reaset"){
        update("setting","keyboardmain",$keyboardmain,null,null);
        header('Location: keyboard.php');
        exit;
    }
}
?>

<!doctype html>
<html lang="FA">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پنل مدیریت ربات میرزا</title>
    <script>
      window.__MIRZA_API_ORIGIN = window.location.origin + <?php echo json_encode($applicationBasePath); ?>;
    </script>
    <script type="module" crossorigin src="js/sort_keyboard.js"></script>
    <link rel="stylesheet" crossorigin href="css/sort_keyboard.css">
    <style>
    @font-face {
    font-family: 'yekan';
    src: url('fonts/Vazir.eot');
    src: url('fonts/Vazir.eot#iefix') format('embedded-opentype'),
         url('fonts/Vazir.woff') format('woff'),
         url('fonts/Vazir.ttf') format('truetype'),
         url('fonts/Vazir.svg#CartoGothicStdBook') format('svg');
    font-weight: normal;
    font-style: normal;
}
button{
    font-family: yekan;
}
        .btnback{
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 7px;
            background-color: #3d3d3d;
            color:#fff;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
        }
        .btndefult{
            position: fixed;
            top: 10px;
            left: 150px;
            padding: 7px;
            background-color: #fff;
            border: 2px solid #3d3d3d;
            color:#3d3d3d;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
        }
    </style>
  </head>
  <body>
    <a class="btnback" href = "index.php">بازگشت به پنل کاربری</a>
    <a class="btndefult" href = "keyboard.php?action=reaset" >بازگشت به حالت پیشفرض</a>
    <div id="root"></div>
  </body>
</html>
