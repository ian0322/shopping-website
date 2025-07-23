<?php
require_once 'conn.php';
require('db.php');
require __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set(timezoneId: 'Asia/Taipei');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$allowed_origins = [
    'http://localhost:5174',           // 開發用 vite
    'http://192.168.0.2:8080',         // 若用 vite dev server
    'http://localhost',                // 打包後使用 localhost 開啟
    'http://192.168.0.2',
    'https://chenyiportfolio.great-site.net'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Cross-Origin-Embedder-Policy: require-corp");
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
$apiHost = getenv('VITE_API_HOST') ?: 'http://localhost:5174/';

// Check if email is provided
$email = $_POST['email'] ?? '';
if (empty($email)) {
    echo json_encode(["message" => "❌ 請提供 email 或帳號"]);
    exit;
}

// Check if account exists using PDO
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Generate token and write to the database
        $token = bin2hex(random_bytes(32)); // Random token
        $expiration = date('Y-m-d H:i:s', strtotime('+10 minutes')); // Token expiration

        // Update token and expiration in the database
        $updateStmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiration = :expiration WHERE email = :email");
        $updateStmt->bindParam(':token', $token);
        $updateStmt->bindParam(':expiration', $expiration);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->execute();

        // Generate password reset link
        $resetLink = "{$apiHost}lostpwd?token={$token}";

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'a0966170322@gmail.com'; // Use your Gmail account
            $mail->Password = 'vrkt ivaz xird opbl'; // Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->setFrom('your_email@gmail.com', 'chenyiPortfolio');
            $mail->addAddress($email, '使用者');
            $mail->isHTML(true);
            $mail->Subject = '重設您的密碼';
            $mail->Body = "點擊以下鏈接以重設您的密碼：<br><a href='$resetLink'>$resetLink</a>";

            $mail->send();
            echo json_encode(["success" => true, "message" => "郵件已發送，請檢查您的郵箱"]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "郵件發送失敗：" . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "查無此帳號"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "資料庫錯誤：" . $e->getMessage()]);
}

?>