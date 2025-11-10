<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';


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
                    <div class="col-lg-12">
                        <section class="panel">
                            <header class="panel-heading">لیست سفارشات</header>
                            <table class="table table-striped border-top" id="sample_1">
                                <thead>
                                    <tr>
                                        <th style="width: 8px;">
                                            <input type="checkbox" class="group-checkable" data-set="#sample_1 .checkboxes" /></th>
                                        <th class="hidden-phone">آیدی عددی</th>
                                        <th class="hidden-phone">شناسه سفارش</th>
                                        <th>نام کاربری کانفیگ</th>
                                        <th class="hidden-phone">لوکیشن سرویس</th>
                                        <th class="hidden-phone">نام محصول</th>
                                        <th class="hidden-phone">تاریخ سفارش</th>
                                        <th class="hidden-phone">قیمت سفارش</th>
                                        <th class="hidden-phone">وضعیت سفارش</th>
                                    </tr>
                                </thead>
                                <tbody> <?php
                                foreach($listinvoice as $list){
                                    if(intval($list['time_sell'])){
                                        $list['time_sell'] = jdate('Y/m/d |  H:i:s',$list['time_sell']);
                                    }
                                    $list['Status'] = [
                                        'unpaid' => "در انتظار پرداخت",
                                        "active" => "فعال",
                                        "disabledn" => "ناموجود در پنل",
                                        "end_of_time" => "هشدار زمان باقی ماند",
                                        "end_of_volume" => "هشدار حجم باقی مانده",
                                        "sendedwarn" => "هشدار حجم و زمان باقی مانده",
                                        "send_on_hold" => "هشدار متصل نشدن به کانفیگ",
                                        'removebyuser' => 'حذف شده توسط کاربر'
                                        ][$list['Status']];
                                        if($list['price_product'] == 0)$list['price_product'] = "رایگان";
                                   echo "<tr class=\"odd gradeX\">
                                        <td>
                                        <input type=\"checkbox\" class=\"checkboxes\" value=\"1\" /></td>
                                        <td>{$list['id_user']}</td>
                                        <td class=\"hidden-phone\">{$list['id_invoice']}</td>
                                        <td class=\"hidden-phone\">{$list['username']}</td>
                                        <td class=\"hidden-phone\">{$list['Service_location']}</td>
                                        <td class=\"hidden-phone\">{$list['name_product']}</td>
                                        <td class=\"hidden-phone time_Sell\">{$list['time_sell']}</td>
                                        <td class=\"hidden-phone\">{$list['price_product']}</td>
                                        <td class=\"hidden-phone\">{$list['Status']}</td>
                                    </tr>";
                                }
                                    ?>
                                </tbody>
                            </table>
                        </section>
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
    <script type="text/javascript" src="assets/data-tables/jquery.dataTables.js"></script>
    <script type="text/javascript" src="assets/data-tables/DT_bootstrap.js"></script>


    <!--common script for all pages-->
    <script src="js/common-scripts.js"></script>

    <!--script for this page only-->
    <script src="js/dynamic-table.js"></script>


</body>
</html>
