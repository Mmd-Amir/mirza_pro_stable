<?php
session_start();
require_once __DIR__ . '/../config.php';
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
    $query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $query = $pdo->prepare("SELECT * FROM user");
    $query->execute();
    $listusers = $query->fetchAll();
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
                            <header class="panel-heading">لیست کاربران</header>
                            <table class="table table-striped border-top" id="sample_1">
                                <thead>
                                    <tr>
                                        <th style="width: 8px;">
                                            <input type="checkbox" class="group-checkable" data-set="#sample_1 .checkboxes" /></th>
                                        <th class="hidden-phone">آیدی عددی</th>
                                        <th>نام کاربری</th>
                                        <th class="hidden-phone">شماره تلفن</th>
                                        <th class="hidden-phone">موجودی کاربر</th>
                                        <th class="hidden-phone">تعداد زیرمجموعه های کاربر</th>
                                        <th class="hidden-phone">وضعیت کاربر</th>
                                        <th class="hidden-phone">مدیریت کاربر</th>
                                    </tr>
                                </thead>
                                <tbody> <?php
                                foreach($listusers as $list){
                                    $status_user = [
                                        'Active' => "فعال",
                                        'active' => "فعال",
                                        "block" => "بلاک",
                                        ][$list['User_Status']];
                                    if($list['number'] == "none")$list['number'] ="بدون شماره ";
                                   echo "<tr class=\"odd gradeX\">
                                        <td>
                                        <input type=\"checkbox\" class=\"checkboxes\" value=\"1\" /></td>
                                        <td>{$list['id']}</td>
                                        <td class=\"hidden-phone\">{$list['username']}</td>
                                        <td class=\"hidden-phone\">{$list['number']}</td>
                                        <td class=\"hidden-phone\">{$list['Balance']}</td>
                                        <td class=\"hidden-phone\">{$list['affiliatescount']}</td>
                                        <td class=\"hidden-phone\">$status_user</td>
                                        <td class=\"hidden-phone\">
                                        <a class = \"btn btn-success\" href= \"user.php?id={$list['id']}\">مدیریت کاربر </a></td>
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
