<?php
session_start();

// 允許的來源和 CORS 設定
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

// 開啟錯誤顯示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 權限檢查：只有 admin 可以上傳
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['message' => '權限不足']);
  exit;
}

// 資料庫連線
$servername = "localhost";
$username = "root";
$password = "a2614362";
$dbname = "upload_db";

$conn = new mysqli($servername, $username, $password, $dbname, 3306);
if ($conn->connect_error) {
  die("連線失敗: " . $conn->connect_error);
}

// 檢查是否有檔案、標籤、名稱、價格
if (isset($_FILES['files']) && isset($_POST['tags']) && isset($_POST['names']) && isset($_POST['price']) && isset($_POST['size'])) {
  $files = $_FILES['files'];
  $tags = $_POST['tags'];
  $names = $_POST['names'];
  $prices = $_POST['price'];
  $size = $_POST['size'];
  for ($i = 0; $i < count($files['name']); $i++) {
    $fileName = $files['name'][$i];
    $tmpName = $files['tmp_name'][$i];
    $uploadDir = 'uploads/';

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . basename($fileName);

    if (move_uploaded_file($tmpName, $filePath)) {
      $tagArray = json_decode($tags[$i], true);
      $tagString = implode(',', $tagArray);
      $name = $names[$i];
      $price = $prices[$i];

      $stmt = $conn->prepare("INSERT INTO uploads (file_path, tags, name, price, size) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssds", $filePath, $tagString, $name, $price, $size);
      $stmt->execute();
      $stmt->close();
    }
  }

  echo json_encode(['message' => '上傳成功！']);
} else {
  echo json_encode(['message' => '資料不完整。']);
}

$conn->close();
