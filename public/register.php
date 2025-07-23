<?php
require_once 'conn.php';
session_start();

// 允許來自指定來源的請求
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
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Cross-Origin-Embedder-Policy: require-corp");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
// 開啟錯誤顯示
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

function sendNewUserCoupons($userId, $conn)
{
    // 參考你之前的PHP版優惠券發放函式
    try {
        $sqlUser = "SELECT id FROM users WHERE id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("i", $userId);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($resultUser->num_rows === 0) {
            // 不符合新用戶條件或不存在就不發
            return false;
        }

        $sqlCoupon = "SELECT id FROM upload_db.coupons WHERE id = 3 AND is_public = 1 LIMIT 1";
        $resultCoupon = $conn->query($sqlCoupon);
        if ($resultCoupon->num_rows === 0) {
            return false;
        }
        $newUserCoupon = $resultCoupon->fetch_assoc();

        $sqlExist = "SELECT * FROM user_coupons WHERE user_id = ? AND coupon_id = ?";
        $stmtExist = $conn->prepare($sqlExist);
        $stmtExist->bind_param("ii", $userId, $newUserCoupon['id']);
        $stmtExist->execute();
        $resultExist = $stmtExist->get_result();
        if ($resultExist->num_rows > 0) {
            // 已有該優惠券，不重複發
            return true;
        }

        $sqlInsert = "INSERT INTO user_coupons (user_id, coupon_id, is_used, assigned_at, expire_at) VALUES (?, ?, 0, NOW(), NULL)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("ii", $userId, $newUserCoupon['id']);
        return $stmtInsert->execute();
    } catch (Exception $e) {
        return false;
    }
}

// 主程式開始

if (isset($_POST['captcha'])) {
    $inputCaptcha = strtoupper($_POST['captcha']);
    if ($inputCaptcha === $_SESSION['captcha']) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $birth = $_POST['birth'] ?? '';

            if (empty($username) || empty($email) || empty($password) || empty($phone) || empty($birth)) {
                echo json_encode(['success' => false, 'messages' => ['所有欄位都是必填的']]);
                exit();
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            if ($hashedPassword === false) {
                echo json_encode(['success' => false, 'messages' => ['密碼加密失敗']]);
                exit();
            }

            $errors = [];

            if ($conn->query("SELECT 1 FROM users WHERE username='$username'")->num_rows > 0) {
                $errors[] = '*用戶名已存在';
            }
            if ($conn->query("SELECT 1 FROM users WHERE email='$email'")->num_rows > 0) {
                $errors[] = '*電子信箱已存在';
            }
            if ($conn->query("SELECT 1 FROM users WHERE phone='$phone'")->num_rows > 0) {
                $errors[] = '*手機號碼已存在';
            }
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'messages' => $errors]);
                exit();
            }

            $sql = "INSERT INTO users (username, email, password, phone, birth) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'messages' => ['SQL 錯誤：' . $conn->error]]);
                exit();
            }
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $phone, $birth);

            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                // 註冊成功，發放新用戶優惠券
                $couponResult = sendNewUserCoupons($userId, $conn);

                echo json_encode(['success' => true, 'messages' => ['註冊成功', $couponResult ? '新用戶優惠券已發放' : '新用戶優惠券發放失敗或不符合條件']]);
            } else {
                echo json_encode(['success' => false, 'messages' => ['註冊失敗：' . $stmt->error]]);
            }
            $stmt->close();
        }
    } else {
        echo json_encode(['success' => false, 'messages' => ['*驗證碼錯誤']]);
    }
} else {
    echo json_encode(['success' => false, 'messages' => ['未提供驗證碼']]);
}

session_write_close();
$conn->close();
