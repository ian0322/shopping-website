<template>
    <button style="cursor: pointer;" @click="goPay">立即刷卡</button>
</template>

<script setup>
const result = {
    "success": true,
    "orderNumber": "ORD20251042483697",
    "amount": 3600,
    "items": [
        {
            "name": "藍色POLO衫",
            "size": "L",
            "quantity": 1,
            "subtotal": 1200
        },
        {
            "name": "藍色POLO衫",
            "size": "XL",
            "quantity": 2,
            "subtotal": 2400
        }
    ]
};
const goPay = async () => {
    try {
        const res = await fetch("/ecpay_checkout.php", {
            method: "POST",                  // 用 POST
            headers: {
                "Content-Type": "application/json",  // 告訴後端是 JSON
            },
            body: JSON.stringify(result),    // 把物件轉成字串送出
        });

        if (!res.ok) {
            throw new Error("伺服器錯誤：" + res.status);
        }

        const html = await res.text();   // 假設後端回 HTML 字串
        const win = window.open("", "_blank");
        win.document.open();
        win.document.write(html);
        win.document.close();
    } catch (e) {
        console.error("付款跳轉失敗：", e);
    }
};

</script>
