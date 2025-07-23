<?php
require_once 'conn.php'; // 載入 PDO 連線物件 $pdo

try {
    // 開啟交易，確保資料一致性
    $pdo->beginTransaction();

    // 1. 找出今天生日的使用者 (只比對月-日)
    $today = new DateTime();
    $monthDay = $today->format('m-d');

    $stmt = $pdo->prepare("SELECT id, birth FROM users WHERE DATE_FORMAT(birth, '%m-%d') = ?");
    $stmt->execute([$monthDay]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) === 0) {
        echo "今天沒有人生日\n";
        $pdo->commit();
        exit;
    }

    // 2. 找出生日優惠券公版（跨資料庫）
    $stmt = $pdo->prepare("SELECT id, code, description FROM upload_db.coupons WHERE id = 1 AND is_public = 1 LIMIT 1");
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$coupon) {
        echo "沒有生日優惠券公版，請先建立\n";
        $pdo->commit();
        exit;
    }

    // 3. 對每個生日用戶，檢查是否已有此優惠券並新增
    $expireAtStr = (new DateTime('last day of December'))->format('Y-m-d'); // 今年年底失效

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id, is_used, assigned_at, expire_at) VALUES (?, ?, 0, NOW(), ?)");

    foreach ($users as $user) {
        // 檢查是否已有優惠券
        $checkStmt->execute([$user['id'], $coupon['id']]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            echo "用戶 {$user['id']} 已經有生日優惠券，跳過\n";
            continue;
        }

        // 新增優惠券紀錄
        $insertStmt->execute([$user['id'], $coupon['id'], $expireAtStr]);
        echo "成功給用戶 {$user['id']} 生日優惠券\n";
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo "錯誤: " . $e->getMessage() . "\n";
}
