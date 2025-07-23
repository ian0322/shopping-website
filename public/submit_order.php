<?php
require_once 'conn.php';
require('db.php');
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$mail = new PHPMailer(true);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
session_start();

// 允許的來源清單
$allowed_origins = [
    'http://localhost:5174',
    'http://192.168.0.2:8080',
    'http://localhost',
    'http://192.168.0.2',
    'http://192.168.0.5:5173', // ✅ 加這行
    'https://chenyiportfolio.great-site.net'

];
$apiHost = getenv('VITE_API_HOST') ?: 'http://localhost'; // 預設值

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: http://localhost");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['user']) || !isset($data['cart'])) {
    echo json_encode(['success' => false, 'message' => '資料不完整']);
    exit;
}

$mail = new PHPMailer(true);  // <- 這行很重要，必須先初始化

$user = $data['user'];
$coupon = $data['coupon'] ?? null;
$couponId = $coupon['coupon_id'] ?? null;
$cart = $data['cart'];

$paymentMethod = $data['paymentMethod'] ?? 'cod';  // 默認為貨到付款

// 產生訂單編號
$orderNumber = 'ORD' . date('YmdHi') . rand(1000, 9999);

// 組合訂單 HTML 內容
$html = "<h2>訂單確認信</h2>";
$html .= "<p><strong>訂單編號：</strong> {$orderNumber}</p>";
$html .= "<p><strong>姓名：</strong> " . htmlspecialchars($user['name']) . "</p>";
$html .= "<p><strong>Email：</strong> " . htmlspecialchars($user['email']) . "</p>";
$html .= "<p><strong>電話：</strong> " . htmlspecialchars($user['phone']) . "</p>";
$html .= "<p><strong>地址：</strong> " . htmlspecialchars($user['location']) . "</p>";
$html .= "<br />";
$html .= "<h3>🛒 購物車內容：</h3>";

$html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>
<tr>
  <th>圖片</th>
  <th>商品名稱</th>
  <th>尺寸</th>
  <th>單價</th>
  <th>數量</th>
  <th>小計</th>
</tr>";

// 計算總金額
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * intval($item['quantity']);
}
foreach ($cart as $index => $item) {
    $filePath = $item['file_path'] ?? '';
    $cid = "image{$index}";
    $realPath = realpath(__DIR__ . '/' . $filePath);

    if ($realPath && file_exists($realPath)) {
        $mail->addEmbeddedImage($realPath, $cid);
    }

    $html .= "<tr>
        <td><img src='cid:{$cid}' width='60' /></td>
        <td>" . htmlspecialchars($item['name']) . "</td>
        <td>" . htmlspecialchars($item['size']) . "</td>
        <td>\$" . number_format($item['price'], 2) . "</td>
        <td>" . intval($item['quantity']) . "</td>
        <td>\$" . number_format($item['price'] * intval($item['quantity']), 2) . "</td>
    </tr>";
}

$html .= "<tr><td colspan='5' align='right'><strong>總計</strong></td><td><strong>\$" . number_format($total, 2) . "</strong></td></tr>";
$html .= "</table>";

try {

    $pdo->beginTransaction();

    // 判斷會員與訪客 ID
    $userId = isset($user['userId']) && !empty($user['userId']) ? $user['userId'] : null;
    $guestId = isset($user['guestId']) && !empty($user['guestId']) ? $user['guestId'] : null;
    $guestEmail = !$userId ? $user['email'] : null;

    // 插入訂單主表
    if ($userId) {
        // PHP 版本發放首購券，取代原先 Node.js shell_exec 呼叫
        sendFirstOrderCouponToUser($pdo, $userId);

        $stmt = $pdo->prepare("
            INSERT INTO orders (order_number, user_id, guest_id, guest_email, user_name, phone, address, total_price)
            VALUES (?, ?, NULL, NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderNumber,
            $userId,
            $user['name'],
            $user['phone'],
            $user['location'],
            $total
        ]);
    } elseif ($guestId) {
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_number, user_id, guest_id, guest_email, user_name, phone, address, total_price)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderNumber,
            $guestId,
            $user['email'],
            $user['name'],
            $user['phone'],
            $user['location'],
            $total
        ]);
    } else {
        throw new Exception("無法識別訂單使用者身份");
    }

    $orderId = $pdo->lastInsertId();

    // 插入訂單明細並扣庫存
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity, subtotal, size) VALUES (?, ?, ?, ?, ?, UPPER(?))");
    $stmtSelectSize = $pdo->prepare("SELECT size FROM upload_db.uploads WHERE id = ?");
    $stmtUpdateSize = $pdo->prepare("UPDATE upload_db.uploads SET size = ? WHERE id = ?");

    foreach ($cart as $item) {
        $quantity = intval($item['quantity']);
        $subtotal = $item['price'] * $quantity;
        $size = strtoupper($item['size']); // 統一大寫

        // 插入明細
        $stmtItem->execute([
            $orderId,
            $item['id'],
            $item['price'],
            $quantity,
            $subtotal,
            $size
        ]);

        // 扣庫存
        $stmtSelectSize->execute([$item['id']]);
        $sizeStr = $stmtSelectSize->fetchColumn();

        if ($sizeStr !== false) {
            $sizesArr = explode(',', $sizeStr);

            // 更新指定尺寸的庫存數量
            foreach ($sizesArr as $index => $s) {
                list($sz, $qty) = explode(':', $s);
                if ($sz === $size) {
                    $newQty = max(0, intval($qty) - $quantity);
                    $sizesArr[$index] = $sz . ':' . $newQty;
                    break;
                }
            }

            // 過濾掉數量為 0 的尺寸
            $filteredSizesArr = array_filter($sizesArr, function ($entry) {
                list(, $qty) = explode(':', $entry);
                return intval($qty) > 0;
            });

            $newSizeStr = implode(',', $filteredSizesArr);
            $stmtUpdateSize->execute([$newSizeStr, $item['id']]);
        }
    }

    // 刪除購物車
    if ($userId) {
        $stmtDeleteCartItem = $pdo->prepare("DELETE FROM carts WHERE user_id = ? AND product_id = ? AND size = ?");
        foreach ($cart as $item) {
            $stmtDeleteCartItem->execute([$userId, intval($item['id']), strtoupper($item['size'])]);
        }
        if ($coupon) {
            $stmt = $pdo->prepare("
                UPDATE user_coupons
                SET is_used = 1, used_at = NOW()
                WHERE user_id = ?
                  AND coupon_id = ?
                  AND is_used = 0
                  AND (expire_at IS NULL OR expire_at >= NOW())
            ");
            $stmt->execute([$userId, $couponId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                echo json_encode(['error' => '優惠券無效或已過期']);
                exit;
            }

            $stmtUpdateQty = $pdo->prepare("
                UPDATE upload_db.coupons
                SET used_quantity = used_quantity + 1
                WHERE id = ?
                  AND (total_quantity IS NULL OR used_quantity < total_quantity)
            ");
            $stmtUpdateQty->execute([$couponId]);
            if ($stmtUpdateQty->rowCount() === 0) {
                http_response_code(400);
                echo json_encode(['error' => '優惠券已使用完畢']);
                exit;
            }
        }
    } elseif ($guestId) {
        $stmtDeleteGuestCartItem = $pdo->prepare("DELETE FROM guest_carts WHERE guest_id = ? AND product_id = ? AND size = ?");
        foreach ($cart as $item) {
            $stmtDeleteGuestCartItem->execute([$guestId, intval($item['id']), strtoupper($item['size'])]);
        }
    }

    $pdo->commit();

    // 返回訂單編號和總金額等資料給前端
    $items = array_map(function ($item) {
        return [
            'name' => $item['name'],
            'size' => strtoupper($item['size']),
            'quantity' => intval($item['quantity']),
            'subtotal' => $item['price'] * intval($item['quantity']),
        ];
    }, $cart);

    if ($paymentMethod === 'credit') {
        echo json_encode([
            'success' => true,
            'orderNumber' => $orderNumber,
            'amount' => $total,
            'items' => $items,
            'paymentMethod' => 'credit'
        ]);
        exit;  // Exit here to avoid sending confirmation email or redirecting to payment
    }
    try {
        // SMTP 設定（請改成你自己的郵件服務）
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'a0966170322@gmail.com'; // 請換成你的Gmail帳號
        $mail->Password = 'vrkt ivaz xird opbl';   // 請換成你的Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // 收件人設定
        $mail->setFrom('a0966170322@gmail.com', '訂單系統');
        $mail->addAddress($user['email'], $user['name']);

        // 信件內容
        $mail->isHTML(true);
        $mail->Subject = '訂單確認 - 感謝您的購買！';
        $mail->Body = $html;
        $mail->CharSet = 'UTF-8';
        $mail->send();
    } catch (Exception $e) {
        // 處理郵件錯誤
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'orderNumber' => $orderNumber,
        'amount' => $total,
        'items' => $items
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// 發放首購券函式
function sendFirstOrderCouponToUser(PDO $pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("SELECT id FROM upload_db.coupons WHERE id = 2 AND is_public = 1 LIMIT 1");
        $stmt->execute();
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$coupon) {
            error_log('沒有首購優惠券公版');
            return false;
        }
        $firstOrderCouponId = $coupon['id'];

        $stmt = $pdo->prepare("SELECT * FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
        $stmt->execute([$userId, $firstOrderCouponId]);
        if ($stmt->rowCount() > 0) {
            error_log("用戶{$userId}已擁有首購券，跳過");
            return false;
        }

        $stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id, is_used, assigned_at, expire_at) VALUES (?, ?, 0, NOW(), NULL)");
        $stmt->execute([$userId, $firstOrderCouponId]);

        error_log("成功給用戶{$userId}首購券");
        return true;
    } catch (Exception $e) {
        error_log('首購券發送錯誤: ' . $e->getMessage());
        return false;
    }
}
