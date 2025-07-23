<?php
// 開啟 session
session_start();

// 顯示錯誤以便於除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 允許的來源
$allowed_origins = [
    'http://localhost:5174',  // 開發用 vite
    'http://192.168.0.2:8080',  // 若用 vite dev server
    'http://localhost',  // 打包後使用 localhost 開啟
    'http://192.168.0.2',
    'http://192.168.0.5:5173', // ✅ 加這行
    'https://chenyiportfolio.great-site.net'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';


// 檢查請求來源是否允許
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");  // 允許攜帶憑證 (如 cookies)
}

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');  // 設置允許的 HTTP 方法
header('Access-Control-Allow-Headers: Content-Type, Authorization');  // 允許的請求頭
header("Cross-Origin-Opener-Policy: same-origin-allow-popups");
header("Cross-Origin-Embedder-Policy: require-corp");
// 處理 preflight 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// 資料庫連線資訊
$servername = "localhost";
$username = "root";
$password = "a2614362";
$dbname = "upload_db";

// 建立與 MySQL 的連線
$conn = new mysqli($servername, $username, $password, $dbname, 3306);

// 檢查連線是否成功
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// 從資料庫中選取所有檔案資料
$sql = "SELECT * FROM uploads"; // 修改為你的查詢語句
$result = $conn->query($sql);

// 準備存放圖片資訊的陣列
$images = [];
$Maintags = ['男裝', '女裝', '童裝', '全新商品', '優惠商品'];

// 如果資料庫中有資料，則將資料加入陣列
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tags = explode(',', $row['tags']);
        $itemMaintags = [];
        $itemSubtags = [];

        foreach ($tags as $tag) {
            if (in_array($tag, $Maintags)) {
                $itemMaintags[] = $tag;
            } else {
                $itemSubtags[] = $tag;
            }
        }
        $images[] = [
            'file_path' => '/' . $row['file_path'],
            'name' => $row['name'],
            'price' => $row['price'],
            'tags' => $row['tags'],
            'Maintags' => $itemMaintags,
            'Subtags' => $itemSubtags,
            'id' => $row['id'],
            'size' => $row['size'],
        ];
    }
} else {
    // 如果沒有資料，返回錯誤信息
    echo json_encode(['status' => 'error', 'message' => 'No records found']);
    exit;
}

// 準備返回的資料，包含用戶名稱（如果登入）和商品資料
$response = [
    "username" => isset($_SESSION['username']) ? $_SESSION['username'] : null,  // 若有登入則顯示使用者名稱
    "email" => isset($_SESSION["email"]) ? $_SESSION["email"] : null,
    "phone" => isset($_SESSION["phone"]) ? $_SESSION["phone"] : null,
    "images" => $images,
    "Maintags" => $Maintags,
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// 關閉資料庫連線
$conn->close();
session_write_close();

?>