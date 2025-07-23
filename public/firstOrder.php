<?php
// firstOrder.php
// 這個腳本用於 CLI，執行時要帶入 userId：php firstOrder.php 123

require_once 'conn.php';  // 連接 PDO 的檔案，請自行調整路徑

if (php_sapi_name() !== 'cli') {
    echo "請在 CLI 模式執行此腳本\n";
    exit(1);
}

$userId = $argv[1] ?? null;

if (!$userId) {
    echo "請傳入使用者 ID，例如：php firstOrder.php 123\n";
    exit(1);
}

try {
    // 查首購券（id=2 且是公版）
    $stmt = $pdo->prepare("SELECT id FROM upload_db.coupons WHERE id = 2 AND is_public = 1 LIMIT 1");
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo "沒有首購優惠券公版\n";
        exit(1);
    }

    $couponId = $coupon['id'];

    // 查用戶是否已經有此券
    $stmt = $pdo->prepare("SELECT * FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
    $stmt->execute([$userId, $couponId]);

    if ($stmt->rowCount() > 0) {
        echo "用戶{$userId}已擁有首購券，跳過\n";
        exit(0);
    }

    // 發放首購券
    $stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id, is_used, assigned_at, expire_at) VALUES (?, ?, 0, NOW(), NULL)");
    $stmt->execute([$userId, $couponId]);

    echo "成功給用戶{$userId}首購券\n";
    exit(0);
} catch (PDOException $e) {
    echo "首購券發送錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
