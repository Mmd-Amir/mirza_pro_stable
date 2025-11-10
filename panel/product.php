<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
    $query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $query = $pdo->prepare("SELECT * FROM product");
    $query->execute();
    $listinvoice = $query->fetchAll();
    $query = $pdo->prepare("SELECT * FROM marzban_panel");
    $query->execute();
    $listpanel = $query->fetchAll();
if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}
$nameProduct = $_POST['nameproduct'] ?? null;
if(!empty($nameProduct)){
    $randomString = bin2hex(random_bytes(2));
    $userdata['data_limit_reset'] = "no_reset";
    $product = select("product","*","name_product",$nameProduct,"count");
    if($product != 0){
        echo "alert(\"محصول از قبل وجود دارد\")";
        return;
    }
    $hidepanel = "{}";
    $priceProduct   = $_POST['price_product']   ?? '';
    $volumeProduct  = $_POST['volume_product']  ?? '';
    $serviceTime    = $_POST['time_product']    ?? '';
    $location       = $_POST['namepanel']       ?? '';
    $agentProduct   = $_POST['agent_product']   ?? '';
    $category       = $_POST['cetegory_product'] ?? '';
    $note           = $_POST['note_product']    ?? '';
    $dataLimitReset = $userdata['data_limit_reset'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO product (name_product,code_product,price_product,Volume_constraint,Service_time,Location,agent,data_limit_reset,note,category,hide_panel,one_buy_status) VALUES (:name_product,:code_product,:price_product,:Volume_constraint,:Service_time,:Location,:agent,:data_limit_reset,:note,:category,:hide_panel,'0')");
    $stmt->bindParam(':name_product', $nameProduct, PDO::PARAM_STR);
    $stmt->bindParam(':code_product', $randomString);
    $stmt->bindParam(':price_product', $priceProduct, PDO::PARAM_STR);
    $stmt->bindParam(':Volume_constraint', $volumeProduct, PDO::PARAM_STR);
    $stmt->bindParam(':Service_time', $serviceTime, PDO::PARAM_STR);
    $stmt->bindParam(':Location', $location, PDO::PARAM_STR);
    $stmt->bindParam(':agent', $agentProduct, PDO::PARAM_STR);
    $stmt->bindParam(':data_limit_reset', $dataLimitReset);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':note', $note, PDO::PARAM_STR);
    $stmt->bindParam(':hide_panel', $hidepanel);
    $stmt->execute();
    header("Location: product.php");
}
if(isset($_GET['oneproduct'], $_GET['toweproduct']) && $_GET['oneproduct'] !== '' && $_GET['toweproduct'] !== ''){
    update("product", "id", 10000, "id", $_GET['oneproduct']);
    update("product", "id", intval($_GET['oneproduct']), "id", intval($_GET['toweproduct']));
    update("product", "id", intval($_GET['toweproduct']), "id", 10000);
    header("Location: product.php");
}

if(isset($_GET['removeid']) && $_GET['removeid'] !== ''){
    $stmt = $connect->prepare("DELETE FROM product WHERE id = ?");
    $stmt->bind_param("s", $_GET['removeid']);
    $stmt->execute();
    header("Location: product.php");
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
                            <header class="panel-heading">لیست محصولات</header>
                                <section class="panel">
                                <a href="#addproduct" data-toggle="modal"  class="btn btn-info  btn-sm">اضافه کردن محصول</a>
                                <a href="#moveradif" data-toggle="modal"  class="btn btn-success  btn-sm">جابه جایی ردیف محصول</a>
                        </section>
                            <table class="table table-striped border-top" id="sample_1">
                                <thead>
                                    <tr>
                                        <th style="width: 8px;">
                                            <input type="checkbox" class="group-checkable" data-set="#sample_1 .checkboxes" /></th>
                                        <th class="hidden-phone">شناسه</th>
                                        <th class="hidden-phone">کد سرویس</th>
                                        <th class="hidden-phone">نام سرویس</th>
                                        <th>قیمت سرویس</th>
                                        <th class="hidden-phone">حجم سرویس</th>
                                        <th class="hidden-phone">زمان سرویس</th>
                                        <th class="hidden-phone">لوکیشن سرویس</th>
                                        <th class="hidden-phone">گروه کاربری سرویس</th>
                                        <th class="hidden-phone">ریست دوره ای سرویس</th>
                                        <th class="hidden-phone">دسته بندی محصول</th>
                                        <th class="hidden-phone">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody> <?php
                                foreach($listinvoice as $list){
                                    if($list['category'] == null){
                                        $list['category'] = "ندارد";
                                    }
                                   echo "<tr class=\"odd gradeX\">
                                        <td>
                                        <input type=\"checkbox\" class=\"checkboxes\" value=\"1\" /></td>
                                        <td>{$list['id']}</td>
                                        <td>{$list['code_product']}</td>
                                        <td class=\"hidden-phone\">{$list['name_product']}</td>
                                        <td class=\"hidden-phone\">{$list['price_product']}</td>
                                        <td class=\"hidden-phone\">{$list['Volume_constraint']}</td>
                                        <td class=\"hidden-phone\">{$list['Service_time']}</td>
                                        <td class=\"hidden-phone\">{$list['Location']}</td>
                                        <td class=\"hidden-phone\">{$list['agent']}</td>
                                        <td class=\"hidden-phone\">{$list['data_limit_reset']}</td>
                                        <td class=\"hidden-phone\">{$list['category']}</td>
                                        <td  class=\"hidden-phone\"><a class = \"btn btn-danger\" href= \"product.php?removeid={$list['id']}\">حذف محصول</a><a class = \"btn btn-info\" href= \"productedit.php?id={$list['id']}\">ویرایش محصول</a></td>
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
        <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="moveradif" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">تغییر مکان دو محصول</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "product.php" method = "GET" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                        <label for="oneproduct" class="col-lg-2 control-label">شناسه اول</label>
                                                        <div class="col-lg-10">
                                                            <input type="number" name = "oneproduct" class="form-control" id="oneproduct">
                                                        </div>
                                                        <label for="toweproduct" class="col-lg-2 control-label" style = "margin:20px 0;">شناسه دوم</label>
                                                        <div class="col-lg-10" style = "margin:20px 0;">
                                                            <input type="number" name = "toweproduct" class="form-control" id="toweproduct">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">تغییر مکان دو محصول</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
        <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="addproduct" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                                <h4 class="modal-title">اضافه کردن محصول</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "product.php" method = "POST" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                        <label for="nameproduct" class="col-lg-2 control-label">نام محصول</label>
                                                        <div class="col-lg-10"><input required type="text" name = "nameproduct" class="form-control" id="nameproduct"></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="nameproduct" class="col-lg-2 control-label">موقعیت محصول</label>
                                                        <div class="col-lg-10">
                                                        <select required  name = "namepanel" class="form-control">
                                                  <option value="/all">تمامی پنل ها</option>
                                                <?php
                                                if(count($listpanel)>=0){
                                                foreach($listpanel as $panel){
                                                echo "<option value = \"{$panel['name_panel']}\">{$panel['name_panel']}</option>";
                                                }
                                                }
                                                ?>
                                            </select>
                                                    </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="price_product" class="col-lg-2 control-label">قیمت محصول</label>
                                                        <div class="col-lg-10"><input  required type="number" name = "price_product" class="form-control" id="price_product"></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="volume_product" class="col-lg-2 control-label">حجم محصول</label>
                                                        <div class="col-lg-10"><input required type="number" name = "volume_product" class="form-control" id="volume_product"></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="time_product" class="col-lg-2 control-label">زمان محصول</label>
                                                        <div class="col-lg-10"><input required type="number" name = "time_product" class="form-control" id="volume_product"></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="agent_product" class="col-lg-2 control-label">نوع کاربری محصول</label>
                                                        <div class="col-lg-10">
                                                        <select required  name = "agent_product" class="form-control">
                                                 <option value="f">عادی</option>
                                                <option value = "n">نماینده</option>
                                                <option value = "n2">نماینده پیشرفته</option>
                                            </select>
                                                    </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="note_product" class="col-lg-2 control-label">توضیحات محصول</label>
                                                        <div class="col-lg-10"><input required type="text" name = "note_product" class="form-control" id="note_product"></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="cetegory_product" class="col-lg-2 control-label">دسته بندی محصول</label>
                                                        <div class="col-lg-10"><input required type="text" name = "cetegory_product" class="form-control" id="cetegory_product"></div>
                                                    </div>

                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-danger">اضافه کردن محصول</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>

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
    