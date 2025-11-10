<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../botapi.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

$headrs = getallheaders();
$setting = select("setting", "*");
$token = file_get_contents('hash.txt');
if(!isset($headrs['Token']) or $token != $headrs['Token']){
    echo json_encode(array(
        'status' => false,
        'msg' => "token invalid"
        ));
    return;
}
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"),true);
if(!is_array($data)){
    echo json_encode(array(
        'status' => false,
        'msg' => "data invalid",
        'obj' => []
        ));
        return;
}
$data = sanitize_recursive($data);
$stmt = $pdo->prepare("INSERT IGNORE INTO logs_api (header,data,time,ip,actions) VALUES (:header,:data,:time,:ip,:actions)");
$stmt->bindParam(':header',json_encode($headrs));
$stmt->bindParam(':data',json_encode($data));
$stmt->bindParam(':time',date('Y/m/d H:i:s'));
$stmt->bindParam(':ip',$_SERVER['REMOTE_ADDR']);
$stmt->bindParam(':actions',$data['actions']);
$stmt->execute();
switch ($data['actions']) {
    case 'services':
        if($method != "GET"){
    echo json_encode(array(
        'status' => false,
        'msg' => "method invalid; is mthod must GET"
        ));
    return;
}
        if (isset($data['limit']) && is_numeric($data['limit'])){
            $limit = "LIMIT {$data['limit']}";
        }else{
            $limit = "";
        }
        $stmt = $pdo->prepare("SELECT id,id_user,username,time,price,type,status FROM service_other $limit");
        $stmt->execute();
        $users = $stmt->fetchAll();
        echo json_encode(array(
        'status' => true,
        'msg' => "Successful",
        'obj' => $users
        ));
        break;
    default:
        echo json_encode(array(
        'status' => false,
        'msg' => "Action Invalid"
        ));
        break;
}