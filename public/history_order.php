<?php
require_once 'conn.php';
require('db.php');
session_start();

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
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 讀取前端傳送的 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);
$orderNumber = $data['order_number'] ?? '';
$email = $data['email'] ?? '';

$orders = [];

// ✅ 狀況一：有登入的會員
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $sql = "
        SELECT 
            oi.order_id, 
            oi.product_id, 
            oi.quantity, 
            u.name,
            oi.size,
            u.price, 
            u.file_path, 
            o.order_number, 
            o.payment_status,
            o.created_at AS order_date
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN upload_db.uploads u ON oi.product_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC, oi.order_id DESC;
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'error' => 'SQL prepare 錯誤',
            'message' => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

}
// ✅ 狀況二：訪客查詢歷史訂單（需提供訂單編號和 email）
else if ($orderNumber && $email) {
    $sql = "
        SELECT 
            oi.order_id, 
            oi.product_id, 
            oi.quantity, 
            u.name,
            oi.size,
            u.price, 
            u.file_path, 
            o.order_number, 
            o.payment_status,
            o.created_at AS order_date
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN upload_db.uploads u ON oi.product_id = u.id
        WHERE o.order_number = ? AND o.guest_email = ?
        ORDER BY o.created_at DESC, oi.order_id DESC;
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'error' => 'SQL prepare 錯誤',
            'message' => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("ss", $orderNumber, $email);
    $stmt->execute();
    $result = $stmt->get_result();
}
// ❌ 兩者都沒有
else {
    echo json_encode(['orders' => [], 'message' => '未登入且未提供訂單編號與 email']);
    exit;
}

// 整理結果
while ($row = $result->fetch_assoc()) {
    $orderId = intval($row['order_id']);
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $orderId,
            'order_number' => $row['order_number'],
            'order_date' => $row['order_date'],
            'items' => [],
            'payment' => $row['payment_status']
        ];
    }
    $orders[$orderId]['items'][] = [
        'product_id' => intval($row['product_id']),
        'quantity' => intval($row['quantity']),
        'name' => $row['name'],
        'size' => $row['size'],
        'price' => floatval($row['price']),
        'file_path' => $row['file_path'],
    ];
}


echo json_encode(['orders' => array_values($orders)], JSON_UNESCAPED_UNICODE);
session_write_close();
?>