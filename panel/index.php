<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';
$datefirstday = time() - 86400;
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
    $query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}
    $query = $pdo->prepare("SELECT SUM(price_product) FROM invoice  WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != 'سرویس تست'");
    $query->execute();
    $subinvoice = $query->fetch(PDO::FETCH_ASSOC);
    $query = $pdo->prepare("SELECT * FROM user");
    $query->execute();
    $resultcount = $query->rowCount();
    $time = strtotime(date('Y/m/d'));
    $stmt = $pdo->prepare("SELECT * FROM user WHERE register > :time_register AND register != 'none'");
    $stmt->bindParam(':time_register', $datefirstday);
    $stmt->execute();
    $resultcountday = $stmt->rowCount();
    $query = $pdo->prepare("SELECT  * FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != 'سرویس تست'");
    $query->execute();
    $resultcontsell = $query->rowCount();
    $subinvoice['SUM(price_product)'] = number_format($subinvoice['SUM(price_product)']);
    if($resultcontsell != 0){
    $query = $pdo->prepare("SELECT time_sell,price_product FROM invoice ORDER BY time_sell DESC;");
    $query->execute();
    $salesData = $query->fetchAll();
    $grouped_data = [];
    foreach ($salesData as $sell){
        if(count($grouped_data) > 15)break;
        if(!is_numeric($sell['time_sell']))continue;
        $time = date('Y/m/d',$sell['time_sell']);
        $price = (int)$sell['price_product'];
        if (!isset($grouped_data[$time])) {
        $grouped_data[$time] = ['total_amount' => 0, 'order_count' => 0];
        }
        $grouped_data[$time]['total_amount'] += $price;
        $grouped_data[$time]['order_count'] += 1;
    }
    $max_amount = max(array_map(function($info) { return $info['total_amount']; }, $grouped_data)) ?: 1;
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
    <link rel="stylesheet" href="css/owl.carousel.css" type="text/css">
    <!-- Custom styles for this template -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

  </head>

  <body>

  <section id="container" class="">
  <?php include("header.php");
?>
      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
              <!--state overview start-->
              <div class="row state-overview">
                  <div class="col-lg-3 col-sm-6">
                      <section class="panel">
                          <div class="symbol terques">
                              <i class="icon-user"></i>
                          </div>
                          <div class="value">
                              <h1><?php echo $resultcount; ?></h1>
                              <p>تعداد کاربران</p>
                          </div>
                      </section>
                  </div>
                  <div class="col-lg-3 col-sm-6">
                      <section class="panel">
                          <div class="symbol red">
                              <i class="icon-tags"></i>
                          </div>
                          <div class="value">
                              <h1><?php echo $resultcontsell; ?></h1>
                              <p>تعداد فروش کل</p>
                          </div>
                      </section>
                  </div>
                  <div class="col-lg-3 col-sm-6">
                      <section class="panel">
                          <div class="symbol blue">
                              <i class="icon-bar-chart"></i>
                          </div>
                          <div class="value">
                              <h1 style = "font-size:19px"><?php echo $subinvoice['SUM(price_product)']; ?> تومان </h1>
                              <p>جمغ کل فروش</p>
                          </div>
                      </section>
                  </div>
                  <div class="col-lg-3 col-sm-6">
                      <section class="panel">
                          <div class="symbol yellow">
                              <i class="icon-user"></i>
                          </div>
                          <div class="value">
                              <h1><?php echo $resultcountday; ?></h1>
                              <p>کاربران جدید امروز</p>
                          </div>
                      </section>
                  </div>

              </div>
              <?php if($resultcontsell != 0 ){?>
              <div class="titlechart">
                  <h3 class = "title">چارت فروش</h3>
              </div>
              <div class="custom-bar-chart">
            <?php
            $i = 0;
            foreach ($grouped_data as $date => $info) {
                $amount = $info['total_amount'];
                $order_count = $info['order_count'];
                $jdate = jdate('Y/m/d', strtotime($date)); 
                $height_percentage = ($amount / $max_amount) * 100;
                $class = ($i % 2 == 1) ? 'bar doted' : 'bar';
                ?>
                <div class="<?php echo $class; ?>">
                    <div class="title"><?php echo htmlspecialchars($jdate); ?></div>
                    <div class="value tooltips" 
                         data-original-title="<?php echo number_format($amount); ?> تومان" 
                         data-toggle="tooltip" 
                         style="height: <?php echo $height_percentage; ?>%;">
                        <?php echo number_format($amount); ?>
                    </div>
                </div>
                <?php
                $i++;
            }
            ?>
</div>
<?php  } ?>
          </section>
      </section>
      <!--main content end-->
  </section>

    <!-- js placed at the end of the document so the pages load faster -->
    <script src="js/jquery.js"></script>
    <script src="js/jquery-1.8.3.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="js/jquery.sparkline.js" type="text/javascript"></script>
    <script src="js/owl.carousel.js" ></script>
    <script src="js/jquery.customSelect.min.js" ></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    

    <!--common script for all pages-->
    <script src="js/common-scripts.js"></script>
  </body>
</html>
