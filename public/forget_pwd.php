<?php
session_start();
require('conn.php');

header("Access-Control-Allow-Origin: *");  // 測試用，可先開放
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

while (ob_get_level()) {
    ob_end_clean();
}

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$newPassword = $data['newPassword'] ?? '';
$confirmPWD = $data['confirmPWD'] ?? '';

if (!$token || !$newPassword || !$confirmPWD) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
    exit;
}

if ($newPassword !== $confirmPWD) {
    echo json_encode(['success' => false, 'message' => '兩次密碼不一致']);
    exit;
}

// 先查詢使用者資訊與原密碼
$stmt = $conn->prepare("SELECT id, password FROM users WHERE reset_token = ? AND reset_token_expiration > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token 無效或已過期']);
    exit;
}

$user = $result->fetch_assoc();

// 判斷新密碼是否與舊密碼相同
if (password_verify($newPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => '新密碼不可與原本密碼相同']);
    exit;
}

// 更新密碼
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
$update->bind_param("si", $hashed, $user['id']);
$update->execute();

echo json_encode(['success' => true, 'message' => '✅ 密碼重設成功']);
exit;
