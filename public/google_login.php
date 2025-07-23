<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit();
}

$client_id = getenv('GOOGLE_CLIENT_ID');  // 從環境變數讀取

$client = new Google_Client(['client_id' => $client_id]);
$inputRaw = file_get_contents('php://input');
error_log("Raw input: " . $inputRaw);

$input = json_decode($inputRaw, true);
error_log("Decoded input: " . print_r($input, true));

$id_token = $input['credential'] ?? null;
if (!$id_token) {
    error_log("No credential found");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit;
}

$payload = $client->verifyIdToken($id_token);

if ($payload) {

    $email = $payload['email'];
    $google_username = $payload['name'];
    $google_id = $payload['sub'];

    require_once 'db.php';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 登入成功，設定 session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['address'] = $user['address'];
        $_SESSION['role'] = $user['role']; // 新增這行

        // 若未綁定 google_id 則綁定
        if (empty($user['google_id'])) {
            $update = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $update->execute([$google_id, $user['id']]);
        }

        echo json_encode([
            'success' => true,
            'account_name' => $user['username'],
            'account_email' => $user['email'],
            'account_phone' => $user['phone'],
            'user_id' => $user['id'],
            'account_lastname' => $user['lastname'],
            'account_firstname' => $user['firstname'],
            'account_address' => $user['address'],
            'role' => $user['role'] // ⬅️ 回傳給前端
        ]);
        session_write_close();

    } else {
        // email 不存在，回傳需要註冊給前端跳轉註冊頁
        echo json_encode([
            'success' => false,
            'need_register' => true,
            'email' => $email,
            'name' => $google_username
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '無效的 Google token'
    ]);
}

?>