<?php
$servername = "localhost";
$username = "root"; // 你的 MySQL 使用者名稱
$password = "a2614362"; // 你的 MySQL 密碼
$dbname = "user_registration";

// 關閉 mysqli 預設的例外拋出，改用錯誤碼處理
mysqli_report(MYSQLI_REPORT_OFF);

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname,3306);

// 檢查連線是否成功
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => '無法連接到資料庫',
        'error' => $conn->connect_error
    ]));
}


// 設定編碼（避免中文亂碼）
$conn->set_charset('utf8');
?>