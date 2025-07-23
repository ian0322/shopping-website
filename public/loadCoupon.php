<?php
session_start();

// CORS 設定
$allowed_origins = [
    'http://localhost:5174',
    'http://192.168.0.2:8080',
    'http://localhost',
    'http://192.168.0.2',
    'http://192.168.0.5:5173', // ✅ 加這行
    'http://192.168.0.5',
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
header("Cross-Origin-Embedder-Policy: require-corp");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 預檢請求直接結束
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// 資料庫連線參數
$servername = "localhost";
$username = "root";
$password = "a2614362";
$dbname = "user_registration";
$port = 3306;

try {
  $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // 驗證登入（此處可根據實際情況從 session 或前端傳 user_id）
  $user_id = $_SESSION['user_id'] ?? null;
  if (!$user_id) {
    http_response_code(401);
    echo json_encode(['message' => '未登入']);
    exit;
  }

  // 查詢使用者擁有的優惠券
  $sql = "
    SELECT 
      uc.id AS user_coupon_id,
      uc.is_used,
      uc.assigned_at,
      uc.used_at,
      c.id AS coupon_id,
      c.code,
      c.description,
      c.type,
      c.value,
      c.min_purchase,
      c.max_discount,
      c.total_quantity,
      c.is_public,
      c.start_date,
      c.expire_date,
      c.img_url
    FROM user_coupons uc
    JOIN upload_db.coupons c ON uc.coupon_id = c.id
    WHERE uc.user_id = ?
    AND uc.is_used = 0
    AND (c.expire_date IS NULL OR c.expire_date >= NOW())
    ORDER BY uc.assigned_at DESC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([$user_id]);
  $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'status' => 'success',
    'data' => $coupons
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => '資料庫錯誤',
    'error' => $e->getMessage()
  ]);
}
