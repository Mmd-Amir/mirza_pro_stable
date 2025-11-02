<?php
require_once '../config.php';
require_once '../function.php';
require_once '../botapi.php';
require_once '../panels.php';
require_once '../jdf.php';
require_once '../keyboard.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
$ManagePanel = new ManagePanel();
$headers = getallheaders();
$authorizationHeader = null;
foreach ($headers as $headerKey => $headerValue) {
    if (strcasecmp($headerKey, 'Authorization') === 0) {
        $authorizationHeader = $headerValue;
        break;
    }
}
if ($authorizationHeader === null) {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
}
$setting = select("setting", "*");
$method = $_SERVER['REQUEST_METHOD'];
$datatextbotget = select("textbot", "*", null, null, "fetchAll");
        $datatxtbot = array();
        foreach ($datatextbotget as $row) {
            $datatxtbot[] = array(
                'id_text' => $row['id_text'],
                'text' => $row['text']
            );
        }
        $datatextbot = array(
            'textafterpay' => '',
            'textaftertext' => '',
            'textmanual' => '',
            'textselectlocation' => '',
            'text_wgdashboard' => '',
            'textafterpayibsng' => ''
        );
        foreach ($datatxtbot as $item) {
            if (isset($datatextbot[$item['id_text']])) {
                $datatextbot[$item['id_text']] = $item['text'];
            }
        }
if ($method == "GET") {
    $data = array(
        'actions' => $_GET['actions'],
        'user_id' => isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : 0,
        'page' => isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1,
        'limit' => isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 10,
        'q' => isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : null,
        'username' => isset($_GET['username']) && is_string($_GET['username']) ? $_GET['username'] : null,
        'id_panel' => isset($_GET['country_id']) && is_string($_GET['country_id']) ? $_GET['country_id'] : "",
        'category_id' => isset($_GET['category_id']) && is_string($_GET['category_id']) ? $_GET['category_id'] : 0,
        'time_range_day' => isset($_GET['time_range_day']) && is_string($_GET['time_range_day']) ? $_GET['time_range_day'] : 0,
        'traffic_gb' => isset($_GET['traffic_gb']) && is_string($_GET['traffic_gb']) ? $_GET['traffic_gb'] : 0,
        'time_days' => isset($_GET['time_days']) && is_string($_GET['time_days']) ? $_GET['time_days'] : 0
    );
} elseif ($method == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
}
if (!is_array($data)) {
    echo json_encode([
        'status' => false,
        'msg' => "Data invalid",
        'obj' => []
    ]);
    return;
}

$data = sanitize_recursive($data);
if (empty($authorizationHeader)) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'msg' => 'Authorization header missing',
    ]);
    return;
}
if (!preg_match('/^\s*Bearer\s+(\S.*)$/i', $authorizationHeader, $matches)) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'msg' => 'Authorization header format invalid',
    ]);
    return;
}
$tokencheck = trim($matches[1]);
$usercheck = select('user', "*", "id", $data['user_id'], "select");
if ($usercheck['User_Status'] == "block") {
    echo json_encode([
        'status' => false,
        'msg' => "user blocked",
    ]);
    http_response_code(402);
    return;
}
$errorreport = select("topicid", "idreport", "report", "errorreport", "select")['idreport'];
$porsantreport = select("topicid", "idreport", "report", "porsantreport", "select")['idreport'];
$buyreport = select("topicid", "idreport", "report", "buyreport", "select")['idreport'];
if (!$usercheck || $usercheck['token'] != $tokencheck) {
    echo json_encode([
        'status' => false,
        'msg' => "Token invalid",
    ]);
    http_response_code(403);
    return;
}
switch ($data['actions']) {
    case 'invoices':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $limit = $data['limit'];
        if ($limit > 10) $limit = 10;
        $page = $data['page'];
        $user_id =  $data['user_id'];
        $username = $data['q'];
        $offset = ($page - 1) * $limit;
        if ($username != null) {
            $querywhere = " AND username LIKE '%$username%'";
        } else {
            $querywhere = "";
        }
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoice WHERE id_user = '$user_id' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere");
        $countStmt->execute();
        $totalItems = $countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere  ORDER BY time_sell DESC LIMIT :limit OFFSET :offset ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $datauser = [];
        if (is_array($invoices)) {
            foreach ($invoices as $invoice) {
                if (!isset($invoice['status']) && isset($invoice['Status'])) {
                    $invoice['status'] = $invoice['Status'];
                }
                $datauser[] = $invoice;
            }
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $datauser,
            'meta' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'limit' => $limit
            ]
        ]);
        break;
    case 'service':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_id =  $data['user_id'];
        $username = $data['username'];
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND username = :username");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($invoice) {
            $panel = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
            if (!$panel) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'msg' => "Panel Not Found",
                    'obj' => []
                ]);
                return;
            }
            $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
            if (!is_array($DataUserOut) || !array_key_exists('data_limit', $DataUserOut) || !array_key_exists('used_traffic', $DataUserOut)) {
                http_response_code(502);
                echo json_encode([
                    'status' => false,
                    'msg' => isset($DataUserOut['msg']) ? $DataUserOut['msg'] : "Service data unavailable",
                    'obj' => []
                ]);
                return;
            }
            $data_limit_bytes = is_numeric($DataUserOut['data_limit']) ? (float)$DataUserOut['data_limit'] : 0;
            $used_traffic_bytes = is_numeric($DataUserOut['used_traffic']) ? (float)$DataUserOut['used_traffic'] : 0;
            $remaining_traffic_bytes = max($data_limit_bytes - $used_traffic_bytes, 0);
            $data_limit = $data_limit_bytes / pow(1024, 3);
            $used_Traffic = $used_traffic_bytes / pow(1024, 3);
            $remaining_traffic = $remaining_traffic_bytes / pow(1024, 3);
            $config = [];
            if (in_array($panel['type'], ['marzban', 'marzneshin', 'alireza_single', 'x-ui_single', 'hiddify', 'eylanpanel'])) {
                if ($panel['sublink'] == "onsublink" && !empty($DataUserOut['subscription_url'])) {
                    $config[] = [
                        'type' => "link",
                        'value' => $DataUserOut['subscription_url']
                    ];
                }
                if ($panel['config'] == "onconfig" && !empty($DataUserOut['links'])) {
                    $config[] = [
                        'type' => "config",
                        'value' => $DataUserOut['links']
                    ];
                }
            } elseif ($panel['type'] == "WGDashboard") {
                $config[] = [
                    'type' => "file",
                    'value' => $DataUserOut['subscription_url'] ?? '',
                    'filename' => $panel['inboundid'] . "_" . $invoice['id_user'] . "_" . $invoice['id_invoice'] . ".config"
                ];
            } elseif (in_array($panel['type'], ['mikrotik', 'ibsng'])) {
                $config[] = [
                    'type' => "password",
                    'value' => $DataUserOut['password'] ?? ''
                ];
            }
            if (isset($DataUserOut['sub_updated_at']) && $DataUserOut['sub_updated_at']  !== null) {
                $sub_updated = $DataUserOut['sub_updated_at'];
                $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
                $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
                $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
            } else {
                $lastupdate = null;
            }
            if (($DataUserOut['online_at'] ?? null) == "online") {
                $lastonline = 'آنلاین';
            } elseif (($DataUserOut['online_at'] ?? null) == "offline") {
                $lastonline = 'آفلاین';
            } else {
                if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
                    $dateString = $DataUserOut['online_at'];
                    $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
                } else {
                    $lastonline = "متصل نشده";
                }
            }
            $expireTimestamp = isset($DataUserOut['expire']) && is_numeric($DataUserOut['expire']) ? (int)$DataUserOut['expire'] : 0;
            $expirationDate = $expireTimestamp ? jdate('Y/m/d', $expireTimestamp) : 'نامحدود';
            $usernameOutput = $DataUserOut['username'] ?? $invoice['username'];
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => array(
                    'status' => $DataUserOut['status'],
                    'username' => $usernameOutput,
                    'product_name' => $invoice['name_product'],
                    'total_traffic_gb' => round($data_limit, 2),
                    'used_traffic_gb' => round($used_Traffic, 2),
                    'remaining_traffic_gb' => round($remaining_traffic, 2),
                    'expiration_time' => $expirationDate,
                    'last_subscription_update' => $lastupdate,
                    'online_at' => $lastonline,
                    'service_output' => $config
                ),
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "Service Not  Found",
                'obj' => []
            ]);
        }
        break;
    case 'user_info':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            if ($user_info['codeInvitation'] == null) {
                $randomString = bin2hex(random_bytes(4));
                update("user", "codeInvitation", $randomString, "id", $user_info['id']);
                $user_info['codeInvitation'] = $randomString;
            }
            if ($user_info['number'] == "none") {
                $numberphone = "🔴 ارسال نشده است 🔴";
            } else {
                $numberphone = $user_info['number'];
            }
            if ($user_info['number'] == "confrim number by admin") {
                $numberphone = "✅ تایید شده توسط ادمین";
            }
            $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND name_product != 'سرویس تست' AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
            $stmt->execute([
                ':id_user' => $user_info['id']
            ]);
            $countorder = $stmt->rowCount();
            $stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid'");
            $stmt->execute([
                ':from_id' => $user_info['id']
            ]);
            $countpayment = $stmt->rowCount();
            $groupuser = [
                'f' =>  "عادی",
                'n' => "نماینده",
                'n2' => "نمایندگی پیشرفته",
            ][$user_info['agent']];
            $userjoin = jdate('Y/m/d', $user_info['register']);
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => [
                    'codeInvitation' => $user_info['codeInvitation'],
                    'balance' => $user_info['Balance'],
                    'phone' => $numberphone,
                    'count_order' => $countorder,
                    'count_payment' => $countpayment,
                    'group_type' => $groupuser,
                    'time_join' => $userjoin,
                    'affiliatescount' => $user_info['affiliatescount']

                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'countries':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active' AND (agent = :agent OR agent = 'all') AND type != 'Manualsale'");
            $stmt->bindParam(':agent', $user_info['agent']);
            $stmt->execute();
            $panel_list = [];
            $setting = select("setting", "*", null, null, "select");;
            $is_note = false;
            if ($setting['statusnamecustom'] == 'onnamecustom') $is_note = true;
            if ($setting['statusnoteforf'] == "0" && $user_info['agent'] == "f") $is_note = false;
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($result['MethodUsername'] == $textbotlang['users']['customusername'] || $result['MethodUsername'] == "نام کاربری دلخواه + عدد رندوم") {
                    $is_username = true;
                } else {
                    $is_username = false;
                }
                $statuscustomvolume = json_decode($result['customvolume'], true)[$user_info['agent']];
                if (intval($statuscustomvolume) == 1 && $result['type'] != "Manualsale") {
                    $is_custom = true;
                } else {
                    $is_custom = false;
                }
                if ($result['hide_user'] != null && in_array($user_info['id'], json_decode($result['hide_user'], true))) continue;
                $panel_list[] = [
                    'id' => $result['code_panel'],
                    'name' => $result['name_panel'],
                    'is_custom' => $is_custom,
                    'is_username' => $is_username,
                    'is_note' => $is_note
                ];
            }
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => $panel_list
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'categories':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $setting = select("setting", "*", null, null, "select");
            if ($setting['statuscategorygenral'] == "offcategorys") {
                echo json_encode(array(
                    'status' => true,
                    'msg' => "Successful",
                    'obj' => []
                ));
                return;
            }
            $stmt = $pdo->prepare("SELECT * FROM category");
            $stmt->execute();
            $category_list = [];
            $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
            if (empty($panel)) {
                echo json_encode(array(
                    'status' => false,
                    'msg' => "panel not fonud!(invalid id_panel)"
                ));
                return;
            }
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stmts = $pdo->prepare("SELECT * FROM product WHERE (Location = :location OR Location = '/all') AND category = :category");
                $stmts->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
                $stmts->bindParam(':category', $result['remark'], PDO::PARAM_STR);
                $stmts->execute();
                if ($stmts->rowCount() == 0) continue;
                $category_list[] = [
                    'id' => $result['id'],
                    'name' => $result['remark'],
                ];
            }
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => $category_list
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'time_ranges':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $setting = select("setting", "*", null, null, "select");
            if ($setting['statuscategory'] == "offcategory") {
                echo json_encode(array(
                    'status' => true,
                    'msg' => "Successful",
                    'obj' => []
                ));
                return;
            }
            $category_time_list = [];
            $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
            if (empty($panel)) {
                echo json_encode(array(
                    'status' => false,
                    'msg' => "panel not fonud!(invalid id_panel)"
                ));
                return;
            }
            $stmt = $pdo->prepare("SELECT Service_time FROM product WHERE (Location = :location OR Location = '/all')");
            $stmt->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
            $stmt->execute();
            $montheproduct = array_flip(array_flip($stmt->fetchAll(PDO::FETCH_COLUMN)));
            if (in_array("1", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['1day'],
                    'day' => 1
                );
            }
            if (in_array("7", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['7day'],
                    'day' => 7
                );
            }
            if (in_array("31", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['1'],
                    'day' => 31
                );
            }
            if (in_array("30", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['1'],
                    'day' => 30
                );
            }
            if (in_array("61", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['2'],
                    'day' => 61
                );
            }
            if (in_array("60", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['2'],
                    'day' => 60
                );
            }
            if (in_array("91", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['3'],
                    'day' => 91
                );
            }
            if (in_array("90", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['3'],
                    'day' => 90
                );
            }
            if (in_array("121", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['4'],
                    'day' => 121
                );
            }
            if (in_array("120", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['4'],
                    'day' => 120
                );
            }
            if (in_array("181", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['6'],
                    'day' => 181
                );
            }
            if (in_array("180", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['6'],
                    'day' => 180
                );
            }
            if (in_array("365", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['365'],
                    'day' =>  365
                );
            }
            if (in_array("0", $montheproduct)) {
                $category_time_list[] = array(
                    'id' => 0,
                    'name' => $textbotlang['Admin']['month']['unlimited'],
                    'day' =>  0
                );
            }
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => $category_time_list
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'services':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $product_list = [];
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
            if (empty($panel)) {
                echo json_encode(array(
                    'status' => false,
                    'msg' => "panel not fonud!(invalid id_panel)"
                ));
                return;
            }
            $category_remark = null;
            $selected_category_id = isset($data['category_id']) ? $data['category_id'] : null;
            if (!empty($data['category_id'])) {
                $category_remark = select("category", "*", "id", $data['category_id'], "select");
                if (!is_array($category_remark) || !isset($category_remark['remark'])) {
                    echo json_encode([
                        'status' => false,
                        'msg' => "category not found!(invalid category_id)",
                    ]);
                    return;
                }
                $selected_category_id = $category_remark['id'];
            }
            $query = "SELECT * FROM product WHERE (Location = :location OR Location = '/all')";
            $queryParams = [
                ':location' => $panel['name_panel'],
            ];
            if ($category_remark !== null) {
                $query .= " AND category = :category";
                $queryParams[':category'] = $category_remark['remark'];
            }
            if (!empty($data['time_range_day'])) {
                $query .= " AND Service_time = :service_time";
                $queryParams[':service_time'] = $data['time_range_day'];
            }
            $stmt = $pdo->prepare($query);
            foreach ($queryParams as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $product_list = [];
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $hide_panel = json_decode($result['hide_panel'], true);
                if (!is_array($hide_panel)) {
                    $hide_panel = [];
                }
                if (in_array($panel['name_panel'], $hide_panel)) {
                    error_log("miniapp: product {$result['code_product']} would be hidden for panel {$panel['name_panel']}, but returning it per updated logic.");
                }
                $stmts2 = $pdo->prepare("SELECT * FROM invoice WHERE Status != 'Unpaid' AND id_user = '{$user_info['id']}'");
                $stmts2->execute();
                $countorder = $stmts2->rowCount();
                if ($result['one_buy_status'] == "1" && $countorder != 0) {
                    error_log("miniapp: user {$user_info['id']} previously purchased, but product {$result['code_product']} is still included per updated logic.");
                }
                if (intval($user_info['pricediscount']) != 0) {
                    $resultper = ($result['price_product'] * $user_info['pricediscount']) / 100;
                    $result['price_product'] = $result['price_product'] - $resultper;
                }
                $product_list[] = [
                    'id' => $result['code_product'],
                    'name' => $result['name_product'],
                    'description' => $result['note'],
                    'price' => $result['price_product'],
                    'traffic_gb' => $result['Volume_constraint'],
                    'time_days' => intval($result['Service_time']),
                    'category_id' => $selected_category_id,
                    'country_id' => $panel['code_panel'],
                    'time_range_id' => $result['Service_time']

                ];
            }
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => $product_list
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'custom_price':
        if ($method !== "GET") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be GET",
            ]);
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        if ($user_info) {
            $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
            if (empty($panel)) {
                echo json_encode(array(
                    'status' => false,
                    'msg' => "panel not fonud!(invalid id_panel)"
                ));
                return;
            }
            $statuscustomvolume = json_decode($panel['customvolume'], true)[$user_info['agent']];
            $mainvolume = json_decode($panel['mainvolume'], true);
            $mainvolume = $mainvolume[$user_info['agent']];
            $maxvolume = json_decode($panel['maxvolume'], true);
            $maxvolume = $maxvolume[$user_info['agent']];
            $maintime = json_decode($panel['maintime'], true);
            $maintime = $maintime[$user_info['agent']];
            $maxtime = json_decode($panel['maxtime'], true);
            $maxtime = $maxtime[$user_info['agent']];
            $traffic_price = json_decode($panel['pricecustomvolume'], true);
            $traffic_price = $traffic_price[$user_info['agent']];
            $time_price = json_decode($panel['pricecustomtime'], true);
            $time_price = $time_price[$user_info['agent']];
            if (intval($statuscustomvolume) == 1 && $panel['type'] != "Manualsale") {
                $price = ($traffic_price * intval($data['traffic_gb'])) + ($time_price * intval($data['time_days']));
            } else {
                $price = false;
            }
            echo json_encode([
                'status' => true,
                'msg' => "Successful",
                'obj' => array(
                    'price' => $price,
                    'traffic_min' => intval($mainvolume),
                    'traffic_max' => intval($maxvolume),
                    'time_min' => intval($maintime),
                    'time_max' => intval($maxtime)
                )
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "User Not  Found",
            ]);
        }
        break;
    case 'purchase':
        if ($method !== "POST") {
            echo json_encode([
                'status' => false,
                'msg' => "Method invalid; must be POST",
            ]);
            return;
        }
        $panel = select("marzban_panel", "*", "code_panel", $data['country_id'], "select");
        if (empty($panel)) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "پنل انتخابی موجود نیست."
            ));
            return;
        }
        if ($panel['status'] == "disable") {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "پنل انتخابی درحال حاضر فعال نیست"
            ));
            return;
        }
        $user_info = select("user", "*", "token", $tokencheck, "select");
        $usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
        if (empty($data['custom_service'])) {
            $product = select("product", "*", "code_product", $data['service_id'], "select");
        } else {
            $statuscustomvolume = json_decode($panel['customvolume'], true)[$user_info['agent']];
            $mainvolume = json_decode($panel['mainvolume'], true);
            $mainvolume = $mainvolume[$user_info['agent']];
            $maxvolume = json_decode($panel['maxvolume'], true);
            $maxvolume = $maxvolume[$user_info['agent']];
            $maintime = json_decode($panel['maintime'], true);
            $maintime = $maintime[$user_info['agent']];
            $maxtime = json_decode($panel['maxtime'], true);
            $maxtime = $maxtime[$user_info['agent']];
            $customsrvice = $data['custom_service'];
            $eextraprice = json_decode($panel['pricecustomvolume'], true);
            $custompricevalue = $eextraprice[$user_info['agent']];
            $eextraprice = json_decode($panel['pricecustomtime'], true);
            $customtimevalueprice = $eextraprice[$user_info['agent']];
            $product = array(
                'code_product' => "customvolume",
                'name_product' => $textbotlang['users']['customsellvolume']['title'],
                'Volume_constraint' => $customsrvice['traffic_gb'],
                'Service_time' => $customsrvice['time_days'],
                'Location' => $panel['name_panel'],
                'price_product' => ($customsrvice['traffic_gb'] * $custompricevalue) + ($customsrvice['time_days'] * $customtimevalueprice)
            );
            if (intval($product['Volume_constraint']) > $maxvolume or intval($product['Volume_constraint']) < $mainvolume) {
                http_response_code(500);
                echo json_encode(array(
                    'status' => false,
                    'msg' => "حجم نامعتبر است خرید را از اول انجام دهید"
                ));
                return;
            }
            if (intval($product['Service_time']) > $maxtime or intval($product['Service_time']) < $maintime) {
                http_response_code(500);
                echo json_encode(array(
                    'status' => false,
                    'msg' => "زمان نامعتبر است خرید را از اول انجام دهید"
                ));
                return;
            }
        }
        if (empty($product)) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "محصول انتخابی پیدا نشد"
            ));
            return;
        }
        if (intval($user_info['pricediscount']) != 0) {
            $result = ($product['price_product'] * $user_info['pricediscount']) / 100;
            $product['price_product'] = $product['price_product'] - $result;
            sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
        }
        if ($user_info['Balance'] < $product['price_product']) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "موجودی کمتر از قیمت محصول است"
            ));
            return;
        }
        $randomString = bin2hex(random_bytes(4));
        $username_ac = generateUsername($user_info['id'], $panel['MethodUsername'], $user_info['username'], $randomString, $data['custom_username'], $panel['namecustom'], $user_info['namecustom']);
        $username_ac = strtolower($username_ac);
        $DataUserOut = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
        if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "نام کاربری وجود دارد مراحل را از اول طی کنید"
            ));
            return;
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        $stmt = $connect->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,note,refral,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");
        $Status =  "active";
        $date = time();
        $data['custom_note'] = strval($data['custom_note']) <= 1 ? null : $data['custom_note'];
        $stmt->bind_param("sssssssssssss", $user_info['id'], $randomString, $username_ac, $date, $panel['name_panel'], $product['name_product'], $product['price_product'], $product['Volume_constraint'], $product['Service_time'], $Status, $data['custom_note'], $user_info['affiliates'], $notifctions);
        $stmt->execute();
        $stmt->close();
        $datetimestep = strtotime("+" . $product['Service_time'] . "days");
        if ($product['Service_time'] == 0) {
            $datetimestep = 0;
        } else {
            $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
        }
        $datac = array(
            'expire' => $datetimestep,
            'data_limit' => $product['Volume_constraint'] * pow(1024, 3),
            'from_id' => $user_info['id'],
            'username' => $user_info['username'],
            'type' => 'buy'
        );
        $dataoutput = $ManagePanel->createUser($panel['name_panel'], $product['code_product'], $username_ac, $datac);
        if ($dataoutput['username'] == null) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => "خطایی در ساخت اشتراک رخ داده است با پشتیبانی در ارتباط باشید"
            ));
            $dataoutput['msg'] = json_encode($dataoutput['msg']);

            $texterros = "⭕️ خطای ساخت اشتراک 
✍️ دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : {$user_info['id']}
نام کاربری کاربر : @{$user_info['username']}
نام پنل : {$panel['name_panel']}";
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $config = "";
        $output_config_link = $panel['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
        if ($panel['config'] == "onconfig" && is_array($dataoutput['configs'])) {
            foreach ($dataoutput['configs'] as $link) {
                $config .= "\n" . $link;
            }
        }
        error_log(json_encode($datatextbotget));
        $datatextbot['textafterpay'] = $panel['type'] == "Manualsale" ? $datatextbot['textmanual'] : $datatextbot['textafterpay'];
        $datatextbot['textafterpay'] = $panel['type'] == "WGDashboard" ? $datatextbot['text_wgdashboard'] :  $datatextbot['textafterpay'];
        $datatextbot['textafterpay'] = $panel['type'] == "ibsng" || $panel['type'] == "mikrotik" ? $datatextbot['textafterpayibsng'] : $datatextbot['textafterpay'];
        if (intval($product['Service_time']) == 0) $product['Service_time'] = $textbotlang['users']['stateus']['Unlimited'];
        if (intval($product['Volume_constraint']) == 0) $product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
        $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $datatextbot['textafterpay']);
        $textcreatuser = str_replace('{name_service}', $product['name_product'], $textcreatuser);
        $textcreatuser = str_replace('{location}', $panel['name_panel'], $textcreatuser);
        $textcreatuser = str_replace('{day}', $product['Service_time'], $textcreatuser);
        $textcreatuser = str_replace('{volume}', $product['Volume_constraint'], $textcreatuser);
        $textcreatuser = applyConnectionPlaceholders($textcreatuser, $output_config_link, $config);
        sendMessageService($panel, $dataoutput['configs'], $output_config_link, $user_info['username'], null, $textcreatuser, $randomString, $user_info['id'], $image = '../images.jpg');
        if (intval($product['price_product']) != 0) {
            $Balance_prim = $user_info['Balance'] - $product['price_product'];
            update("user", "Balance", $Balance_prim, "id", $user_info['id']);
        }
        if ($panel['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $panel['MethodUsername'] == "نام کاربری + عدد به ترتیب" || $panel['MethodUsername'] == "آیدی عددی+عدد ترتیبی" || $panel['MethodUsername']  == "متن دلخواه نماینده + عدد ترتیبی") {
            $value  = intval($user_info['number_username']) + 1;
            update("user", "number_username", $value, "id", $user_info['id']);
            if ($panel['MethodUsername'] == "متن دلخواه + عدد ترتیبی" || $panel['MethodUsername']  == "متن دلخواه نماینده + عدد ترتیبی") {
                $value  = intval($setting['numbercount']) + 1;
                update("setting", "numbercount", $value);
            }
        }
        $affiliatescommission = select("affiliates", "*", null, null, "select");
        $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != 'سرویس تست'  AND id_user = :id_user");
        $stmt->bindParam(':id_user', $user_info['id']);
        $stmt->execute();
        $countinvoice = $stmt->rowCount();
        if ($affiliatescommission['status_commission'] == "oncommission" && ($user_info['affiliates'] != null && intval($user_info['affiliates']) != 0)) {
            if ($marzbanporsant_one_buy['porsant_one_buy'] == "on_buy_porsant") {
                if ($countinvoice == 1) {
                    $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
                    $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
                    $Balance_prim = $user_Balance['Balance'] + $result;
                    if (intval($setting['scorestatus']) == 1) {
                        sendmessage($user_info['affiliates'], "📌شما 2 امتیاز جدید کسب کردید.", null, 'html');
                        $scorenew = $user_Balance['score'] + 2;
                        update("user", "score", $scorenew, "id", $user_info['affiliates']);
                    }
                    update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
                    $result = number_format($result);
                    $dateacc = date('Y/m/d H:i:s');
                    $textadd = "🎁  پرداخت پورسانت 
            
            مبلغ $result تومان به حساب شما از طرف  زیر مجموعه تان به کیف پول شما واریز گردید";
                    $textreportport = "
    مبلغ $result به کاربر {$user_info['affiliates']} برای پورسانت از کاربر {$user_info['id']} واریز گردید 
    تایم : $dateacc";
                    if (strlen($setting['Channel_Report']) > 0) {
                        telegram('sendmessage', [
                            'chat_id' => $setting['Channel_Report'],
                            'message_thread_id' => $porsantreport,
                            'text' => $textreportport,
                            'parse_mode' => "HTML"
                        ]);
                    }
                    sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
                } else {

                    $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
                    $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
                    $Balance_prim = $user_Balance['Balance'] + $result;
                    if (intval($setting['scorestatus']) == 1) {
                        sendmessage($user_info['affiliates'], "📌شما 2 امتیاز جدید کسب کردید.", null, 'html');
                        $scorenew = $user_Balance['score'] + 2;
                        update("user", "score", $scorenew, "id", $user_info['affiliates']);
                    }
                    update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
                    $result = number_format($result);
                    $dateacc = date('Y/m/d H:i:s');
                    $textadd = "🎁  پرداخت پورسانت 
        
        مبلغ $result تومان به حساب شما از طرف  زیر مجموعه تان به کیف پول شما واریز گردید";
                    $textreportport = "
مبلغ $result به کاربر {$user_info['affiliates']} برای پورسانت از کاربر {$user_info['id']} واریز گردید 
تایم : $dateacc";
                    if (strlen($setting['Channel_Report']) > 0) {
                        telegram('sendmessage', [
                            'chat_id' => $setting['Channel_Report'],
                            'message_thread_id' => $porsantreport,
                            'text' => $textreportport,
                            'parse_mode' => "HTML"
                        ]);
                    }
                    sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
                }
            }
        }
        if (intval($setting['scorestatus']) == 1) {
            sendmessage($user_info['id'], "📌شما 1 امتیاز جدید کسب کردید.", null, 'html');
            $scorenew = $user_info['score'] + 1;
            update("user", "score", $scorenew, "id", $user_info['id']);
        }
        $balanceformatsell = number_format(select("user", "Balance", "id", $user_info['id'], "select")['Balance'], 0);
        $textonebuy = "";
        if ($countinvoice == 1) {
            $textonebuy = "📌 خرید اول کاربر";
        }
        $balanceformatsellbefore = number_format($user_info['Balance'], 0);
        $Response = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['Admin']['ManageUser']['mangebtnuser'], 'callback_data' => 'manageuser_' . $user_info['id']],
                ],
            ]
        ]);
        $timejalali = jdate('Y/m/d H:i:s');
        $text_report = "📣 جزئیات ساخت اکانت در مینی اپ ثبت شد .
        
$textonebuy
▫️آیدی عددی کاربر : <code>{$user_info['id']}</code>
▫️نام کاربری کاربر :@{$user_info['username']}
▫️نام کاربری کانفیگ :$username_ac
▫️موقعیت سرویس سرویس : {$panel['name_panel']}
▫️نام محصول :{$product['name_product']}
▫️زمان خریداری شده :{$product['Service_time']} روز
▫️حجم خریداری شده : {$product['Volume_constraint']} GB
▫️موجودی قبل خرید : $balanceformatsellbefore تومان
▫️موجودی بعد خرید : $balanceformatsell تومان
▫️کد پیگیری: $randomString
▫️نوع کاربر : {$user_info['agent']}
▫️شماره تلفن کاربر : {$user_info['number']}
▫️دسته بندی محصول : {$product['category']}
▫️قیمت محصول : {$product['price_product']} تومان
▫️زمان خرید : $timejalali";
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $buyreport,
                'text' => $text_report,
                'parse_mode' => "HTML",
                'reply_markup' => $Response
            ]);
        }
        echo json_encode(array(
            'success' => true,
            "message" => "ok",
            "order_id" => $randomString,
            "service" => array(
                'id' => $randomString,
                "username" => $username_ac,
                "status" => "active",
                "expire" => $datetimestep
            )
        ));
        break;
    default:
        echo json_encode([
            'status' => false,
            'msg' => "Action Invalid",
            'obj' => []
        ]);
        break;
}
