<?php
require('conn.php');

header('Content-Type: application/json');

// 接收資料
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (!$token || !$newPassword) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
    exit;
}

// 檢查 token
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token 無效或已過期']);
    exit;
}

$user = $result->fetch_assoc();
$userId = $user['id'];

// 更新密碼
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
$update->bind_param("si", $hashedPassword, $userId);
$update->execute();

echo json_encode(['success' => true, 'message' => '密碼已更新']);
