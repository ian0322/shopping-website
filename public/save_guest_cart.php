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
    'http://192.168.0.5:5174', // ✅ 加這行
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
if (!isset($_SESSION['guest_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登入']);
    exit;
}

$guestId = $_SESSION['guest_id'];

// 前端傳來整張購物車陣列：[{ product_id:..., quantity:... }, ...]
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => '格式錯誤']);
    exit;
}

// 先刪除該使用者舊的購物車
$stmt = $conn->prepare("DELETE FROM guest_carts WHERE guest_id = ?");
$stmt->bind_param("s", $guestId);
$stmt->execute();

// 再批次 INSERT
$stmt = $conn->prepare("
    INSERT INTO guest_carts (guest_id, product_id, size, quantity)
    VALUES (?, ?, UPPER(?), ?)
    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
");

foreach ($input as $item) {
    $pid = intval($item['product_id']);
    $size = $item['size'] ?? '';
    $qty = intval($item['quantity']);
    $stmt->bind_param("sisi", $guestId, $pid, $size, $qty);
    $stmt->execute();
}

echo json_encode(['success' => true]);
session_write_close();
