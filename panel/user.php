<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);
$query = $pdo->prepare("SELECT * FROM user WHERE id=:id");
$query->bindParam("id", $_GET["id"], PDO::PARAM_STR);
$query->execute();
$user = $query->fetch(PDO::FETCH_ASSOC);
$setting = select("setting","*",null,null);
$otherservice = select("topicid","idreport","report","otherservice","select")['idreport'];
$paymentreports = select("topicid","idreport","report","paymentreport","select")['idreport'];
if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}



if(isset($_GET['status']) and $_GET['status']){
    if($_GET['status'] == "block"){
        $textblok = "کاربر با آیدی عددی
{$_GET['id']}  در ربات مسدود گردید 

ادمین مسدود کننده : پنل تحت وب
نام کاربری  : {$_SESSION['user']}";
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $textblok,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
    }else{
        sendmessage($_GET['id'],"✳️ حساب کاربری شما از مسدودی خارج شد ✳️
اکنون میتوانید از ربات استفاده کنید ", null, 'HTML');
    }
    update("user", "User_Status", $_GET['status'], "id", $_GET['id']);
    header("Location: user.php?id={$_GET['id']}");
}
if(isset($_GET['priceadd']) and$_GET['priceadd']){
    $priceadd = number_format($_GET['priceadd'],0);
    $textadd = "💎 کاربر عزیز مبلغ {$priceadd} تومان به موجودی کیف پول تان اضافه گردید.";
    sendmessage($_GET['id'], $textadd, null, 'HTML');
     if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = "📌 یک ادمین موجودی کاربر را از پنل تحت وب افزایش داده است :
        
🪪 اطلاعات ادمین افزایش دهنده موجودی : 
نام کاربری : {$_SESSION['user']}
👤 اطلاعات کاربر دریافت کننده موجودی :
آیدی عددی کاربر  : {$_GET['id']}
مبلغ موجودی : $priceadd";
        telegram('sendmessage',[
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
    $value = intval($user['Balance'])+intval($_GET['priceadd']);
    update("user", "Balance", $value, "id", $_GET['id']);
    header("Location: user.php?id={$_GET['id']}");
}
if(isset($_GET['pricelow']) and $_GET['pricelow']){
    $priceadd = number_format($_GET['pricelow'],0);
    $textlow = "⚠️ کاربر عزیز مبلغ {$priceadd} تومان از موجودی کیف پول تان کسر گردید.";
    sendmessage($_GET['id'], $textlow, null, 'HTML');
    if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = "📌 یک ادمین موجودی کاربر را از پنل تحت وب کسر کرده است :

🪪 اطلاعات ادمین کسر کننده موجودی :
نام کاربری : {$_SESSION['user']}
👤 اطلاعات کاربر :
آیدی عددی کاربر  : {$_GET['id']}
مبلغ موجودی : $priceadd";
        telegram('sendmessage',[
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
    $value = intval($user['Balance'])-intval($_GET['pricelow']);
    update("user", "Balance", $value, "id", $_GET['id']);
    header("Location: user.php?id={$_GET['id']}");
}
if(isset($_GET['agent']) and $_GET['agent']){
    update("user", "agent", $_GET['agent'], "id", $_GET['id']);
    header("Location: user.php?id={$_GET['id']}");
}
if(isset($_GET['textmessage']) and$_GET['textmessage']){
    $messagetext = "📥 یک پیام از مدیریت برای شما ارسال شد.

متن پیام : {$_GET['textmessage']}";
    sendmessage($_GET['id'], $messagetext, null, 'HTML');
     if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = "📌 از طریق پنل تحت وب یک پیام برای کاربر ارسال شد
        
🪪 اطلاعات ادمین ارسال کننده  : 
نام کاربری : {$_SESSION['user']}
👤 اطلاعات ارسال :
آیدی عددی کاربر  : {$_GET['id']}
متن ارسال شده : {$_GET['textmessage']}";
        telegram('sendmessage',[
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
    header("Location: user.php?id={$_GET['id']}");
}

$status_user = [
            'Active' => "فعال",
            'active' => "فعال",
            "block" => "بلاک",
][$user['User_Status']];
if($user['number'] == "none")$user['number'] ="بدون شماره ";
?>
<!DOCTYPE html>
<html lang="en">
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
  </head>


<body>

    <section id="container" class="">
<?php include("header.php");
?>
        <!--main content start-->
        <section id="main-content">
            <section class="wrapper">
                <!-- page start-->
                <div class="row">
                    <aside class="profile-nav col-lg-3">
                        <section class="panel">
                            <div class="user-heading round">
                                <h1><?php echo $user['id'];?></h1>
                                <p><a style = "border:0;color:#fff;font-size:15px;" href = "https://t.me/<?php echo $user['username'];?>"><?php echo $user['username'];?></a></p>
                            </div>

                            <ul class="nav nav-pills nav-stacked">
                                <li class="active"><a href="profile.html"><i class="icon-user"></i>پروفایل</a></li>
                            </ul>

                        </section>
                    </aside>
                    <aside class="profile-info col-lg-9">
                        <section class="panel">
                            <div class="panel-body bio-graph-info">
                                <h1>اطلاعات کاربر</h1>
                                <div class="row">
                                    <div class="bio-row">
                                        <p><span>نام کاربری</span>: <?php echo $user['username'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>محدودیت تست </span>: <?php echo $user['limit_usertest'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>شماره موبایل  </span>: <?php echo $user['number'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>موجودی</span>: <?php echo number_format($user['Balance']);?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>وضعیت کاربر </span>: <?php echo $status_user;?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>نوع کاربر </span>: <?php echo $user['agent'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>تعداد زیرمجموعه  </span>: <?php echo $user['affiliatescount'];?> نفر</p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>زیرمجموعه کاربر  </span>: <?php echo $user['affiliates'];?></p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="panel">
                            <header class="panel-heading">
                                مدیریت کاربر
                            </header>
                            <div class="panel-body">
                                <a class="btn btn-default btn-sm" href="user.php?id=<?php echo $user['id'];?>&status=block">مسدود کردن کاربر</a>
                                <a class="btn btn-success  btn-sm" href="user.php?id=<?php echo $user['id'];?>&status=active">رفع مسدودی کاربر</a>
                                <a href="#addbalance" data-toggle="modal" class="btn btn-info  btn-sm">افزایش موجودی</a>
                                <a href="#lowbalance" data-toggle="modal" class="btn btn-warning  btn-sm">کم کردن موجودی</a>
                                <a href="#changeagent" data-toggle="modal" class="btn btn-primary  btn-sm">تغییر نوع کاربر</a>
                                <a class="btn btn-danger  btn-sm" href="user.php?id=<?php echo $user['id'];?>&agent=f">حذف نماینده</a>
                                <a href="#sendmessage" data-toggle="modal" class="btn btn-info  btn-sm">ارسال پیام به کاربر</a>
                            </div>
                        </section>
                    </aside>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="addbalance" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">اضافه کردن موجودی</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php" method = "GET" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">مبلغ</label>
                                                        <div class="col-lg-10">
                                                            <input type="number" name = "priceadd" class="form-control" id="inputEmail4" placeholder="موجودی که می خواهید افزایش داده شود را وارد نمایید">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">افزایش موجودی</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="sendmessage" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">ارسال پیام به کاربر</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php" method = "GET" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="iduser">
                                                        <label for="text" class="col-lg-2 control-label">متن پیام</label>
                                                        <div class="col-lg-10">
                                                            <input type="text" name = "textmessage" class="form-control" id="text" placeholder="متن پیام خود را بنویسید">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">ارسال پیام</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="lowbalance" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">کم کردن موجودی</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php" method = "GET" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">مبلغ</label>
                                                        <div class="col-lg-10">
                                                            <input type="number" name = "pricelow" class="form-control" id="inputEmail4" placeholder="موجودی که می خواهید کسر شود را وارد نمایید">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">کسر موجودی</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="changeagent" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">تغییر نوع نماینده</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php" method = "GET" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">نوع کاربری</label>
                                                        <div class="col-lg-10">
                                            <select style ="padding:0;" name = "agent" class="form-control input-sm m-bot15">
                                                <option value = "f">کاربر عادی</option>
                                                <option value = "n">نماینده معمولی</option>
                                                <option value = "n2">نماینده پیشرفته</option>
                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">تغییر نوع کاربری</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
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

        //knob
        $(".knob").knob();

  </script>


</body>
</html>
