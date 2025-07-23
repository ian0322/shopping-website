<?php
require('conn.php');

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => '缺少 token']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => '查詢失敗']);
    exit;
}

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '無效或過期的 token']);
}
