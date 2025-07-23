<?php
date_default_timezone_set("Asia/Taipei");
header("Content-Type: text/plain");
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('只接受 POST 請求');
}

$postData = $_POST;

$requiredKeys = ['MerchantID', 'MerchantTradeNo', 'RtnCode', 'CheckMacValue'];
foreach ($requiredKeys as $key) {
    if (!isset($postData[$key])) {
        http_response_code(400);
        exit("缺少必要欄位: $key");
    }
}

$hashKey = 'spPjZn66i0OhqJsQ';
$hashIV = 'hT5OJckN45isQTTs';

function verifyCheckMacValue($params, $hashKey, $hashIV, $receivedCheckMacValue) {
    unset($params['CheckMacValue']);
    ksort($params);
    $macText = "HashKey={$hashKey}";
    foreach ($params as $key => $value) {
        $macText .= "&{$key}={$value}";
    }
    $macText .= "&HashIV={$hashIV}";
    $macText = urlencode($macText);
    $macText = strtolower($macText);
    $macText = str_replace(
        ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
        ['-', '_', '.', '!', '*', '(', ')'],
        $macText
    );
    $calculated = strtoupper(hash('sha256', $macText));
    return $calculated === $receivedCheckMacValue;
}

$receivedCheckMacValue = $postData['CheckMacValue'];

if (!verifyCheckMacValue($postData, $hashKey, $hashIV, $receivedCheckMacValue)) {
    error_log("CheckMacValue 驗證失敗：" . json_encode($postData));
    http_response_code(400);
    exit("CheckMacValue驗證失敗");
}

if (intval($postData['RtnCode']) === 1) {
    $orderNo = $postData['MerchantTradeNo'];
    $payTime = date('Y-m-d H:i:s'); // 或使用 $postData['PaymentDate']

    try {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 1, payment_time = ? WHERE order_number = ?");
        $stmt->execute([$payTime, $orderNo]);
    } catch (PDOException $e) {
        error_log("更新訂單失敗: " . $e->getMessage());
        http_response_code(500);
        exit("更新資料庫錯誤");
    }
}

echo "1|OK";
