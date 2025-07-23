<?php
require_once 'conn.php';
require('db.php');
session_start();

// 允許來自指定來源的請求
$allowed_origins = [
    'http://localhost:5174',           // 開發用 vite
    'http://192.168.0.2:8080',         // 若用 vite dev server
    'http://localhost',                // 打包後使用 localhost 開啟
    'http://192.168.0.2',
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


header('Content-Type: application/json'); // 設定為返回 JSON
// 開啟錯誤顯示
ini_set('display_errors', 1);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// 確認使用者已登入
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登入']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id'] ?? 0);
$action = $data['action'] ?? '';

$user_id = $_SESSION['user_id'];

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT INTO favorites_list (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'action' => 'added']);
} elseif ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM favorites_list WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    echo json_encode(['error' => '無效的操作']);
}
?>