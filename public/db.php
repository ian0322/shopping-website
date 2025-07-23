<?php
// db.php: 資料庫連線設定檔
$host = 'localhost'; 
$username = "root";
$password = "a2614362";
$dbname = "user_registration";

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

?>
