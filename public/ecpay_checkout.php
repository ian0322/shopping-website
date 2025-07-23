<?php
// ecpay_checkout.php

date_default_timezone_set("Asia/Taipei");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
    exit;


// 解 JSON 成 PHP 陣列或物件
$json = file_get_contents('php://input');
$data = json_decode($json, true); // 轉成關聯陣列

$orderNumber = $data['orderNumber'] ?? null;
$totalAmount = $data['amount'] ?? null;  // 注意：你前端用的是 amount，不是 totalAmount
$items = $data['items'] ?? [];

$itemNameParts = [];
foreach ($items as $item) {
    $itemNameParts[] = "{$item['name']} x{$item['quantity']}({$item['size']})";
}

$itemName = implode('#', $itemNameParts);

if (mb_strlen($itemName, 'UTF-8') > 400) {
    $itemName = mb_substr($itemName, 0, 400, 'UTF-8');
}

// 交易參數
$tradeData = [
    'MerchantID' => '3002599', // 測試用
    'MerchantTradeNo' => $orderNumber,//訂單編號
    'MerchantTradeDate' => date('Y/m/d H:i:s'),
    'PaymentType' => 'aio',
    'TotalAmount' => $totalAmount,
    'TradeDesc' => '測試刷卡付款',
    'ItemName' => $itemName,
    'ReturnURL' => 'https://9fe4-59-126-167-80.ngrok-free.app/return_url.php',//開ngrok，或是部署自己的domain
    'ClientBackURL' => 'http://localhost:5173/PT',
    'ChoosePayment' => 'Credit',
    'EncryptType' => 1,
];

// 加密用的金鑰（測試用）
$hashKey = 'spPjZn66i0OhqJsQ';
$hashIV = 'hT5OJckN45isQTTs';

// 產生 CheckMacValue
ksort($tradeData);
$macText = "HashKey={$hashKey}";
foreach ($tradeData as $key => $value) {
    $macText .= "&{$key}={$value}";
}
$macText .= "&HashIV={$hashIV}";
$macText = urlencode($macText);
$macText = strtolower($macText);
$macText = str_replace([
    '%2d',
    '%5f',
    '%2e',
    '%21',
    '%2a',
    '%28',
    '%29'
], [
    '-',
    '_',
    '.',
    '!',
    '*',
    '(',
    ')'
], $macText);
$checkMacValue = strtoupper(hash('sha256', $macText));

$tradeData['CheckMacValue'] = $checkMacValue;

// 輸出 HTML 表單
echo '<form id="ecpay-form" method="post" action="https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5">';
foreach ($tradeData as $key => $value) {
    echo "<input type='hidden' name='{$key}' value='{$value}'>";
}
echo '<input type="submit" value="前往付款" style="display:none;"></form>';
echo '<script>document.getElementById("ecpay-form").submit();</script>';
