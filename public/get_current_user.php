<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 如果你的登入流程有存 $_SESSION['username']，就直接回傳它
if (isset($_SESSION['username'])) {
    echo json_encode(["username" => $_SESSION['username']]);  // 確保使用 json_encode 返回資料
} else {
    echo json_encode(["username" => null]);  // 確保返回的是 JSON 格式
}
session_write_close();

?>
