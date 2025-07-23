<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("開始測試 MySQL 連線");

$mysqli = new mysqli();
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5); // 5 秒 timeout
$connected = $mysqli->real_connect('localhost', 'root', 'a2614362', 'upload_db', 3306);

if (!$connected) {
    error_log("MySQL 連線失敗：" . mysqli_connect_error());
    echo "連線失敗：" . mysqli_connect_error();
    exit;
}

echo "連線成功！";
$mysqli->close();
error_log("MySQL 測試完成");
