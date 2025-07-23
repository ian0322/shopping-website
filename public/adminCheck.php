<?php
session_start();

// 允許的前端來源，依你實際開發環境調整
$allowed_origins = [
    'http://localhost:5174',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://192.168.0.2:8080',
    'https://chenyiportfolio.great-site.net'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 檢查是否登入（後端應該在 login.php 時就寫入 $_SESSION['role']）
if (isset($_SESSION['role'])) {
    echo json_encode([
        'status' => 'ok',
        'role' => $_SESSION['role']
    ]);
} else {
    http_response_code(401); // 沒登入
    echo json_encode([
        'status' => 'unauthorized'
    ]);
}
