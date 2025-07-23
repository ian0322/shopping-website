<?php
require_once 'conn.php';
session_start();

// 允許 CORS
$allowed_origins = [
    'http://localhost:5174',
    'http://192.168.0.2:5173', // 你的前端位址
    'https://chenyiportfolio.great-site.net'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 確保使用者已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    // 允許更新的欄位
    $fields = ['lastname', 'firstname', 'address'];

    $set_clauses = [];
    $params = [];
    $types = '';

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $set_clauses[] = "$field = ?";
            $params[] = $_POST[$field];
            $types .= 's'; // 三個欄位都是字串類型
        }
    }

    if (count($set_clauses) === 0) {
        echo json_encode(['success' => false, 'message' => '沒有要更新的欄位']);
        exit;
    }

    // 最後加上 user_id
    $types .= 'i';
    $params[] = $user_id;

    $sql = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'SQL 錯誤：' . $conn->error]);
        exit();
    }

    // 動態綁定參數
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '修改成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '修改失敗：' . $stmt->error]);
    }

    $stmt->close();
}

session_write_close();
$conn->close();
