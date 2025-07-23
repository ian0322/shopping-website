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
header("Cross-Origin-Embedder-Policy: require-corp");

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['favorite' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "
SELECT 
  c.product_id, 
  u.name, 
  u.price, 
  u.file_path, 
  u.size,
  u.tags
FROM favorites_list c
JOIN upload_db.uploads u ON u.id = c.product_id
WHERE c.user_id = ?
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

$favorite = [];
while ($row = $result->fetch_assoc()) {
    $favorite[] = [
        'id' => intval($row['product_id']),
        'name' => $row['name'],
        'price' => floatval($row['price']),
        'file_path' => $row['file_path'],
        'size' => $row['size'],
        'tags' => $row['tags'],
    ];
}

echo json_encode(['favorite' => $favorite], JSON_UNESCAPED_UNICODE);
session_write_close();

?>