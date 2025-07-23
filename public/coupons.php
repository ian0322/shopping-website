<?php
session_start();

$allowed_origins = [
  'http://localhost:5174',
  'http://192.168.0.2:8080',
  'http://localhost',
  'http://192.168.0.2',
  'http://chenyi.local',
  'https://chenyiportfolio.great-site.net'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 預檢請求直接結束
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// 後面是你的主要邏輯

// 測試 MySQL 連線參數
$servername = "localhost";
$username = "root";
$password = "a2614362";
$dbname = "upload_db";
$port = 3306;

$data = json_decode(file_get_contents('php://input'), true);

$code = $data['code'] ?? null;
$description = $data['description'] ?? null;
$type = $data['type'] ?? null;
$value = $data['value'] ?? null;
$min_purchase = $data['min_purchase'] ?? 0;
$max_discount = $data['max_discount'] ?? null;
$total_quantity = $data['total_quantity'] ?? null;
$is_public = $data['is_public'] ?? true;
$start_date = $data['start_date'] ?? '';
if (empty($start_date)) {
  $start_date = date('Y-m-d H:i:s');
}
$expire_date = $data['expire_date'] ?? null;
$img_base64 = $data['image'] ?? null;
$img_url = null;

if (
  $code === null || $code === '' ||
  $type === null || $type === '' ||
  $value === null || $value === ''
) {
  http_response_code(400);
  echo json_encode(['message' => '缺少必要欄位']);
  exit;
}

if ($img_base64) {
  // 解析 base64 字串
  $img_data = base64_decode($img_base64);

  // 產生唯一檔名
  $fileName = uniqid('coupon_img_') . '.png';
  $filePath = __DIR__ . '/coupons/' . $fileName;

  // 確保 uploads 資料夾存在且可寫
  if (!file_exists(__DIR__ . '/coupons')) {
    mkdir(__DIR__ . '/coupons', 0755, true);
  }

  // 寫檔
  file_put_contents($filePath, $img_data);

  // 產生存入 DB 的相對路徑 (或完整 URL)
  $img_url = 'coupons/' . $fileName;
}
try {
  $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $sql = "INSERT INTO coupons
  (code, description, type, value, min_purchase, max_discount, total_quantity, used_quantity, is_public, start_date, expire_date, created_at, updated_at, img_url)
  VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW(), ?)";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $code,
    $description,
    $type,
    $value,
    $min_purchase,
    $max_discount,
    $total_quantity,
    $is_public ? 1 : 0,
    $start_date,
    $expire_date,
    $img_url
  ]);

  echo json_encode([
    'status' => 'success',
    'message' => '連線成功'
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => '連線失敗',
    'error' => $e->getMessage()
  ]);
}
