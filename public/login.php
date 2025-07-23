<?php
require_once 'conn.php';
require('db.php');

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_error.log");
error_log('Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'null'));
error_log("✍ 測試寫入 log（由程式設定）");
session_start();

// 允許來自指定來源的請求
$allowed_origins = [
    'http://localhost:5174',           // 開發用 vite
    'http://192.168.0.2:8080',         // 若用 vite dev server
    'http://localhost',                // 打包後使用 localhost 開啟
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
header("Cross-Origin-Embedder-Policy: require-corp");
// 開啟錯誤顯示

header('Content-Type: application/json'); // 設定為返回 JSON

// 檢查是否是訪客登入
if (isset($_POST['is_guest']) && $_POST['is_guest'] === '1') {
    if (!isset($_SESSION['guest_id'])) {
        if (isset($_COOKIE['guest_id'])) {
            $_SESSION['guest_id'] = $_COOKIE['guest_id'];
        } else {
            $_SESSION['guest_id'] = bin2hex(random_bytes(8));
            setcookie('guest_id', $_SESSION['guest_id'], time() + (86400 * 30), "/", "", false, true);
        }
    }
    /*if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = bin2hex(string: random_bytes(length: 8));
    }*/
    $login_time = date('Y-m-d H:i:s');
    $last_active_time = $login_time; // 剛登入時活動時間 = 登入時間
    $guest_id = $_SESSION['guest_id'];
    $stmt = $pdo->prepare("
    INSERT INTO guests (guest_id, login_time, last_active_time)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE last_active_time = VALUES(last_active_time)
");
    $stmt->execute([$guest_id, $login_time, $last_active_time]);

    echo json_encode([
        "message" => "訪客登入成功",
        "guest_id" => $_SESSION['guest_id']
    ]);
    session_write_close();
    exit;
}

// 接下來才是正常會員登入邏輯
if (!isset($_POST['useraccount']) || !isset($_POST['password'])) {
    echo json_encode(["message" => "缺少帳號或密碼"]);
    exit;
}

// 獲取用戶輸入的帳號和密碼
$user_account = trim($_POST['useraccount']);  // 去除多餘的空白字元
$password = $_POST['password'];

// 檢查帳號格式：可以進一步驗證 email 或 phone 的格式
if (filter_var($user_account, FILTER_VALIDATE_EMAIL)) {
    $query = "SELECT * FROM users WHERE email = ?";
} elseif (preg_match('/^\d{10}$/', $user_account)) {
    $query = "SELECT * FROM users WHERE phone = ?";
} else {
    $query = "SELECT * FROM users WHERE username = ?";
}

// 使用預處理語句查詢資料庫
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_account); // 綁定帳號參數
$stmt->execute();
$result = $stmt->get_result();

$login_success = false;
$user_data = null;

// 檢查是否有匹配的用戶
while ($row = $result->fetch_assoc()) {
    // 使用 password_verify 來檢查密碼是否正確
    if (password_verify($password, $row["password"])) {
        $login_success = true;
        $user_data = $row;  // 儲存用戶資料，這裡可擴展返回更多資料
        $_SESSION['username'] = $row["username"];
        $_SESSION['user_id'] = $row["id"]; // 如果有 id 可以存
        $_SESSION['email'] = $row["email"];
        $_SESSION['phone'] = $row["phone"];
        $_SESSION['role'] = $row["role"];  // 新增這行
        break;
    }
}

// 根據登入結果回應
if ($login_success) {
    echo json_encode([
        "message" => "登入成功",
        "account_name" => $user_data["username"],  // 統一返回帳戶名稱
        "user_id" => $user_data["id"],  // 你可以根據需要返回更多的資料
        "account_email" => $user_data["email"],    // 新增這一行
        "account_phone" => $user_data["phone"],
        "account_lastname" => $user_data["lastname"],
        "account_firstname" => $user_data["firstname"],
        "account_address" => $user_data["address"],
        "role" => $user_data["role"],  // 新增這行
    ]);
} else {
    echo json_encode(["message" => "帳號或密碼錯誤"]);
}
session_write_close();

// 關閉資料庫連線
$stmt->close();
$conn->close();
?>