<?php
session_start();

// 設定允許的來源，這樣可以處理 CORS
$allowed_origins = [
    'http://localhost:5174',           // 開發用 vite
    'http://192.168.0.2:8080',         // 若用 vite dev server
    'http://localhost',                // 打包後使用 localhost 開啟
    'http://192.168.0.2',
    'http://chenyi.local',
    'http://192.168.0.5:5173', // ✅ 加這行
    'https://chenyiportfolio.great-site.net'

];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 開啟錯誤顯示
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    session_unset();
    session_destroy();
    // 清除 token cookie (如果有)
    // 可以回傳成功訊息或空回應
    echo json_encode(['status' => 'success', 'message' => '已登出']);
    exit();
}

// 如果有儲存登入 Token 或 Cookie，清除它們
setcookie("token", "", time() - 3600, "/");  // 刪除 token cookie

// 這裡可以選擇重定向到登入頁面或其他頁面
header('Location: login.php');
exit;
?>