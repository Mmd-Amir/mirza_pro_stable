<?php
session_start();
require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../function.php'; 
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);
// In previous versions this page attempted to load data from the deprecated
// `x_ui` table. The table no longer exists which caused the whole page to
// crash with a fatal PDO exception before any validation could run. The
// product edit flow does not rely on the removed data, therefore the query is
// skipped altogether so that the form can load normally.
if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}
$statusmessage = false;
$infomesssage = "";
$id_product = htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
$product = select("product","*","id",$id_product,"select");
$action = isset($_GET['action']) ? $_GET['action'] : null;
if($product == false){
    $statusmessage = true;
    $infomesssage ="محصول پیدا نشد";
}else{
if($action === "save" && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $name_product = htmlspecialchars($_POST['name_product'] ?? '', ENT_QUOTES, 'UTF-8');
    if($product['name_product'] != $name_product){
        $statement = $pdo->prepare('SELECT COUNT(*) FROM product WHERE name_product = :name_product AND id != :id');
        $statement->execute([
            ':name_product' => $name_product,
            ':id' => (int) $id_product,
        ]);
        if($statement->fetchColumn() > 0){
            $statusmessage = true;
            $infomesssage ="نام محصول وجود دارد.";
        }else{
            update("product","name_product",$name_product,"id",$id_product);
        }
    }
    $price_product = htmlspecialchars($_POST['price_product'] ?? '', ENT_QUOTES, 'UTF-8');
    if(!is_numeric($price_product)){
        $statusmessage = true;
        $infomesssage ="مبلغ محصول باید عدد باشد";
    }else{
        if($product['price_product'] != $price_product){
            update("product","price_product",$price_product,"id",$id_product);
        }
    }
    $Volume_constraint = htmlspecialchars($_POST['Volume_constraint'] ?? '', ENT_QUOTES, 'UTF-8');
    if(!is_numeric($Volume_constraint)){
        $statusmessage = true;
        $infomesssage ="حجم محصول باید عدد باشد";
    }else{
        if($product['Volume_constraint'] != $Volume_constraint){
            update("product","Volume_constraint",$Volume_constraint,"id",$id_product);
        }
    }
    $Service_time = htmlspecialchars($_POST['Service_time'] ?? '', ENT_QUOTES, 'UTF-8');
    if(!is_numeric($Service_time)){
        $statusmessage = true;
        $infomesssage ="زمان محصول باید عدد باشد";
    }else{
        if($product['Service_time'] != $Service_time){
            update("product","Service_time",$Service_time,"id",$id_product);
        }
    }
    $agent = htmlspecialchars($_POST['agent'] ?? '', ENT_QUOTES, 'UTF-8');
    if(!in_array($agent,['f','n','n2'])){
        $statusmessage = true;
        $infomesssage ="گروه کاربری نامعتبر است";
    }else{
        if($product['agent'] != $agent){
            update("product","agent",$agent,"id",$id_product);
        }
    }
    $category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');
    if($product['category'] != $category){
            update("product","category",$category,"id",$id_product);
        }
    $note = htmlspecialchars($_POST['note'] ?? '', ENT_QUOTES, 'UTF-8');
    if($product['note'] != $note){
            update("product","note",$note,"id",$id_product);
        }

    if(!$statusmessage){
         header('Location: product.php');
    }
}
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
                <?php if($statusmessage){
                    echo "<h2>$infomesssage</h2>";
                } ?>
                <div class="row">
                    <aside class="col-lg-12">
                            <section class="panel">
                            <div class="panel-body bio-graph-info">
                                <h1>ویرایش محصول</h1>
                                <form class="form-horizontal" role="form" method = "post" action = "productedit.php?action=save&id=<?php echo $id_product ?>">
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">نام محصول</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['name_product'];?>" type="text" name = "name_product" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">قیمت محصول</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['price_product'];?>" type="number" name = "price_product" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">حجم محصول</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['Volume_constraint'];?>" type="number" name = "Volume_constraint" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">زمان محصول</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['Service_time'];?>" type="number" name = "Service_time" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">گروه کاربری</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['agent'];?>" type="text" name = "agent" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">دسته بندی</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['category'];?>" type="text" name = "category" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-2 control-label">یادداشت</label>
                                        <div class="col-lg-7">
                                            <input value = "<?php echo $product['note'];?>" type="text" name = "note" class="form-control input-sm m-bot15">
                                        </div>
                                    </div>

                                    </div>

                                    <div class="form-group">
                                            <button type="submit" class="btn btn-success">تغییر تنظیمات</button>
                                    </div>
                                </form>
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
</select>

<script>
  function updateTextarea() {
    var selectElement = document.getElementById("mySelect");
    var textareaElement = document.getElementById("settings");
    var selectedOption = selectElement.options[selectElement.selectedIndex].value;
    if (selectedOption === "tcp_http") {
        selectedOption = `{
  "network": "tcp",
  "security": "none",
  "externalProxy": [],
  "tcpSettings": {
    "acceptProxyProtocol": false,
    "header": {
      "type": "http",
      "request": {
        "version": "1.1",
        "method": "GET",
        "path": [
          "/"
        ],
        "headers": {
          "host": [
            "zula.ir"
          ]
        }
      },
      "response": {
        "version": "1.1",
        "status": "200",
        "reason": "OK",
        "headers": {}
      }
    }
  }
}`;
    } else if (selectedOption == "") {
        selectedOption =  '{
  "network": "ws",
  "security": "none",
  "externalProxy": [],
  "wsSettings": {
    "acceptProxyProtocol": false,
    "path": "/",
    "host": "",
    "headers": {}
  }
}';
}   
 else if (selectedOption == "ws_tls") {
        selectedOption = `{
  "network": "ws",
  "security": "tls",
  "externalProxy": [],
  "tlsSettings": {
    "serverName": "sni.com",
    "minVersion": "1.2",
    "maxVersion": "1.3",
    "cipherSuites": "",
    "rejectUnknownSni": true,
    "certificates": [
      {
        "certificateFile": "",
        "keyFile": "",
        "ocspStapling": 3600
      }
    ],
    "alpn": [
      "h2",
      "http/1.1"
    ],
    "settings": {
      "allowInsecure": true,
      "fingerprint": ""
    }
  },
  "wsSettings": {
    "acceptProxyProtocol": false,
    "path": "/",
    "headers": {}
  }
}`;
}   

    textareaElement.value = selectedOption;
  }
</script>

</body>
</html>
