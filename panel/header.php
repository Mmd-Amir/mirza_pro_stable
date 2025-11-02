<!--header start-->
      <header class="header white-bg">
          <div>
            <div class="sidebar-toggle-box">
                <div data-original-title="Toggle Navigation" data-placement="right" class="icon-reorder tooltips"></div>
            </div>
            <!--logo start-->
            <a href="#" class="logo">ربات <span>میرزا</span></a>
            <!--logo end-->
            <div class="nav notify-row" id="top_menu">
            </div>
            </div>
            <div class="top-nav ">
                <!--search & user info start-->
                <ul class="nav pull-right top-menu">
                    <!-- user login dropdown start-->
                    <li class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <img alt="" src="img/avatar1_small.jpg">
                            <span class="username">سلام <?php echo $_SESSION["user"]; ?></span>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu extended logout">
                            <div class="log-arrow-up"></div>
                            <li><a href="#"><i class="icon-cog"></i> تنظیمات</a></li>
                            <li><a href="login.php"><i class="icon-key"></i> خروج</a></li>
                        </ul>
                    </li>
                    <!-- user login dropdown end -->
                </ul>
                <!--search & user info end-->
            </div>
        </header>
      <!--header end-->
      <!--sidebar start-->
      <aside>
          <div id="sidebar"  class="nav-collapse ">
              <!-- sidebar menu start-->
              <ul class="sidebar-menu">
                  <li>
                      <a href="index.php">
                          <i class="icon-dashboard"></i>
                          <span>صفحه اصلی</span>
                      </a>
                  </li>
                  <li>
                      <a href="users.php">
                          <i class="icon-user"></i>
                          <span>کاربران</span>
                      </a>
                  </li>    
                  <li>
                      <a href="invoice.php">
                          <i class="icon-shopping-cart"></i>
                          <span>سفارشات</span>
                      </a>
                  </li>   
                  <li>
                      <a href="service.php">
                          <i class="icon-shopping-cart"></i>
                          <span>سرویس ها</span>
                      </a>
                  </li>   
                  <li>
                      <a href="product.php">
                          <i class="icon-shopping-cart"></i>
                          <span>محصولات</span>
                      </a>
                  </li>
                  <li>
                      <a href="payment.php">
                          <i class="icon-credit-card"></i>
                          <span>تراکنش ها</span>
                      </a>
                  </li>   
                  <li>
                      <a href="cancelService.php">
                          <i class="icon-trash"></i>
                          <span>حذف سرویس</span>
                      </a>
                  </li> 
                  <li>
                      <a href="seeting_x_ui.php">
                          <i class="icon-sun"></i>
                          <span>تنظیمات پروتکل x-ui</span>
                      </a>
                  </li>
                  <li>
                      <a href="keyboard.php">
                          <i class="icon-sort-by-alphabet-alt"></i>
                          <span>چیدمان کیبورد</span>
                      </a>
                  </li>
                  <!--<li class="sub-menu">-->
                  <!--    <a href="javascript:;" class="">-->
                  <!--        <i class="icon-user"></i>-->
                  <!--        <span>کاربران</span>-->
                  <!--        <span class="arrow"></span>-->
                  <!--    </a>-->
                  <!--    <ul class="sub">-->
                  <!--        <li><a class="" href="users.php">لیست کاربران</a></li>-->
                  <!--    </ul>-->
                  <!--</li>-->
              </ul>
              <!-- sidebar menu end-->
          </div>
      </aside>
      <!--sidebar end-->