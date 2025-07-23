<?php
require_once 'db.php';
session_start();

// CORS 設定
$allowed_origins = [
    'http://localhost:5174',
    'http://192.168.0.2:8080',
    'http://localhost',
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 顯示錯誤
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$orderNumber = 'ORD202505241203031988';
$email = 'a0966170322@gmail.com';

if (!$orderNumber || !$email) {
    echo json_encode(['success' => false, 'message' => '資料不完整']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND guest_email = ?");
    $stmt->execute([$orderNumber, $email]);

    $order = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($order) {
        echo json_encode(['success' => true, 'data' => $order]);
    } else {
        echo json_encode(['success' => false, 'message' => '找不到符合的訂單']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '查詢錯誤: ' . $e->getMessage()]);
}
