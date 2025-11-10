<?php
session_start();
require_once __DIR__ . '/../config.php';

$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);

if($_GET['action'] == "save"){
    update("x_ui", "setting", $_POST['settings'], "codepanel", $_POST['namepanel']);
    header('Location: seeting_x_ui.php');
}

if(!isset($_SESSION["user"]) || !$result){
    header('Location: login.php');
    return;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonData = file_get_contents('php://input');
    $dataArray = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // ذخیره داده‌ها در فایل text.json
        file_put_contents('text.json', json_encode($dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo 'Data saved successfully';
    } else {
        echo 'Invalid JSON data';
    }
} else {
    echo 'Invalid request method';
}
$textbot = file_get_contents($Pathfile.'text.json');
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Mosaddek">
    <meta name="keyword" content="FlatLab, Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <link rel="shortcut icon" href="img/favicon.html">

    <title>پنل مدیریت ربات میرزا</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-reset.css" rel="stylesheet">
    <!--external css-->
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/jquery-easy-pie-chart/jquery.easy-pie-chart.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="css/owl.carousel.css" type="text/css">
    <!-- Custom styles for this template -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            background-color: #f4f7f6;
            color: #333;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #4CAF50;
        }

        form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: #4CAF50;
            outline: none;
        }

        button {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            transition: background-color 0.3s;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #999;
        }
    </style>
</head>

<body>

<section id="container" class="">
    <?php include("header.php"); ?>
    <!--main content start-->
    <section id="main-content">
        <section class="wrapper">
            <!-- page start-->
            <div class="row">
                <aside class="col-lg-12">
                    <section class="panel">
                        <div class="container">
                            <h1>ویرایش متن</h1>
                            <form id="jsonForm"></form>
                            <button type="button" onclick="saveChanges()">ذخیره تغییرات</button>
                        </div>
                    </section>
                </aside>
            </div>
            <!-- page end-->
        </section>
    </section>
    <!--main content end-->
</section>

<!-- js placed at the end of the document so the pages load faster -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.scrollTo.min.js"></script>
<script src="js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="assets/jquery-knob/js/jquery.knob.js"></script>
<!--common script for all pages-->
<script src="js/common-scripts.js"></script>

<script>
    // تابع برای ساخت فرم
    function createForm(data, parentKey = '') {
        const form = document.getElementById('jsonForm');
        Object.keys(data).forEach(key => {
            const fullKey = parentKey ? `${parentKey}.${key}` : key;
            if (typeof data[key] === 'object') {
                createForm(data[key], fullKey);
            } else {
                const label = document.createElement('label');
                label.innerText = fullKey;
                const input = document.createElement('input');
                input.type = 'text';
                input.value = data[key];
                input.name = fullKey;
                form.appendChild(label);
                form.appendChild(input);
            }
        });
    }

    // خواندن فایل JSON
    fetch('<?php echo $Pathfile; ?>text.json')
        .then(response => response.json())
        .then(data => {
            createForm(data); // ساخت فرم بر اساس داده‌های JSON
        })
        .catch(error => console.error('Error loading JSON:', error));

    // تابع برای ذخیره تغییرات
    function saveChanges() {
        const form = document.getElementById('jsonForm');
        const formData = new FormData(form);
        const updatedJson = {};
        formData.forEach((value, key) => {
            const keys = key.split('.');
            let temp = updatedJson;
            while (keys.length > 1) {
                const k = keys.shift();
                if (!temp[k]) temp[k] = {};
                temp = temp[k];
            }
            temp[keys[0]] = value;
        });

        // ارسال داده‌ها به سرور
        fetch('text.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updatedJson)
        })
        .then(response => {
            if (response.ok) {
                alert('تغییرات با موفقیت ذخیره شد!');
            } else {
                alert('خطا در ذخیره سازی داده‌ها');
            }
        })
        .catch(error => {
            console.error('Error saving data:', error);
            alert('خطا در ذخیره سازی داده‌ها');
        });

        console.log(updatedJson);
    }
</script>

<div class="footer">
    <p>© 2024 ویرایش فایل JSON</p>
</div>

</body>
</html>
