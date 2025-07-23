<script setup>
import { onMounted, nextTick, ref, reactive, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2';
import CartItem from './McartItem.vue';
import { useRouter } from 'vue-router';
const filterStore = useFilterStore();
const router = useRouter();
const loading = ref(false);
const openSelectId = ref(null);

const { confirmtobuy, email, phone, lastname, firstname, address, userId, guestId, coupons } = storeToRefs(filterStore);
const usingCoupon = localStorage.getItem("selectedCoupon");
const allCoupon = localStorage.getItem("Coupon");
const All = allCoupon ? JSON.parse(allCoupon) : null;
const couponObj = ref(usingCoupon ? JSON.parse(usingCoupon) : null);
const couponCodeInput = ref('');
const couponError = ref('');
const paymentMethod = ref('cod'); // 預設空字串，選擇其中一個會改變這個值

function applyCouponCode() {
    couponError.value = '';
    const inputCode = couponCodeInput.value.trim().toLowerCase();
    const found = All.find(c => c.code.toLowerCase() === inputCode);

    if (!found) {
        couponError.value = '優惠券代碼不存在或無效';
        return;
    }

    if (couponObj.value && couponObj.value.code.toLowerCase() === inputCode) {
        couponError.value = '優惠券已正在使用中';
        return;
    }

    couponObj.value = found;
    filterStore.selectCoupon(found);
}

const totalCost = computed(() => {
    const total = confirmtobuy.value
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.price * item.quantity, 0);

    const coupon = couponObj.value;
    if (!coupon) return total;

    const discountValue = parseFloat(coupon.value);
    const minPurchase = parseFloat(coupon.min_purchase);
    const maxDiscount = parseFloat(coupon.max_discount);
    const type = coupon.type;

    if (total < minPurchase) return total;

    let discount = 0;

    if (type === 'fixed') {
        discount = Math.min(discountValue, maxDiscount);
    } else if (type === 'percent') {
        discount = Math.min((total * (discountValue / 100)), maxDiscount);
    }

    return total - discount;
});
const form = reactive({
    firstname: '',
    lastname: '',
    country: '',
    district: '',
    zipcode: '',
    address: '',
    email: '',
    phone: ''
});

function checkStockBeforeOrder(cart) {
    const products = filterStore.products || [];
    const productsMap = new Map(products.map(p => [Number(p.id), p]));
    for (const item of cart) {
        const product = productsMap.get(Number(item.id));
        if (!product) {
            alert(`商品 ${item.name} 找不到資料，無法完成訂單`);
            return false;
        }
        const stockObj = filterStore.parseSizeToStock(product.size);

        const stockQty = stockObj[item.size] || 0;

        if (stockQty === 0) {
            alert(`商品 ${item.name} 尺寸 ${item.size} 已無庫存`);
            return false;
        }
        if (item.quantity > stockQty) {
            alert(`商品 ${item.name} 尺寸 ${item.size} 庫存不足，目前剩餘 ${stockQty} 件`);
            return false;
        }
    }
    return true;
}


const submitOrder = async () => {
    loading.value = true;
    try {
        // 強制刷新商品資料確保庫存是最新
        await filterStore.loadProducts(true);
        const isStockOk = checkStockBeforeOrder(filterStore.confirmtobuy);
        if (!isStockOk) {
            alert("庫存不足");
            loading.value = false;
            return;
        }

        const zipcodeInput = document.querySelector('.zipcode');
        form.zipcode = zipcodeInput ? zipcodeInput.value : '';

        const payload = {
            user: {
                name: form.lastname + ' ' + form.firstname,
                email: form.email,
                phone: form.phone,
                location: form.zipcode + form.country + form.district + form.address,
                userId: userId.value,
                guestId: guestId.value,
            },
            coupon: couponObj.value ? {
                user_coupon_id: couponObj.value.user_coupon_id,
                coupon_id: couponObj.value.coupon_id,
            } : null,
            cart: filterStore.confirmtobuy,
            paymentMethod: paymentMethod.value,  // 這是前端選擇的付款方式

        };

        const res = await fetch(`/submit_order.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const result = await res.json();
        if (result.success) {
            if (paymentMethod.value === 'credit') {
                // 前往 ECPay（gopay）付款
                if (!userId.value) {
                    clearFormData();
                }
                // 使用 ECPay 的回調或成功訊息來更新商品資料
                await goPay(result); // 使用 await 等待 goPay 完成
                await filterStore.loadProducts(true); // 付款後刷新商品資料

                return; // 停止後續邏輯
            } else {
                // 其他邏輯不變
                sessionStorage.removeItem('confirmtobuy');
                sessionStorage.removeItem('toBuyCart');
                localStorage.removeItem("selectedCoupon");

                if (!userId.value) {
                    clearFormData();
                }

                await filterStore.loadCart();
                await filterStore.loadProducts(true);
            }
            router.push({ name: 'PT' });
            alert('訂單確認信已寄出');
        } else {
            alert('寄信失敗:' + result.message);
            console.log('寄信失敗:' + result.message);
        }
    } catch (error) {
        alert('發生錯誤: ' + error.message);
        console.log('發生錯誤: ' + error.message);
    } finally {
        loading.value = false;
    }
};
const goPay = async (data) => {
    const win = window.open("", "_blank");
    if (!win) {
        alert("請允許瀏覽器彈出視窗");
        return;
    }
    try {
        const res = await fetch(`/ecpay_checkout.php`, {
            method: "POST",                  // 用 POST
            headers: {
                "Content-Type": "application/json",  // 告訴後端是 JSON
            },
            body: JSON.stringify(data),    // 把物件轉成字串送出
        });

        if (!res.ok) {
            throw new Error("伺服器錯誤：" + res.status);
        }

        const html = await res.text();   // 假設後端回 HTML 字串
        win.document.open();
        win.document.write(html);
        win.document.close();
    } catch (e) {
        console.error("付款跳轉失敗：", e);
    }
};
function clearFormData() {
    form.lastname = '';
    form.firstname = '';
    form.email = '';
    form.phone = '';
    form.address = '';
    form.country = '';
    form.district = '';
    form.zipcode = '';
    const zipcodeInput = document.querySelector('.zipcode');
    if (zipcodeInput) zipcodeInput.value = '';
}
function parseAddress(addr) {
    if (!addr) return { county: '', district: '', detail: '' };

    // 簡單使用「縣市」3字 + 「區」字切分，請根據實際地址結構微調
    const county = addr.slice(0, 3); // 假設縣市都是3字，例如「台中市」
    const districtEndIndex = addr.indexOf('區') + 1; // 找到「區」字，切到包含「區」
    const district = addr.slice(3, districtEndIndex); // 縣市後面到「區」結束，取出區域

    const detail = addr.slice(districtEndIndex); // 「區」後面剩下的就是詳細地址

    return { county, district, detail };
}
onMounted(async () => {
    filterStore.loadSelectedCoupon()
    await nextTick();  // 確保 DOM 更新完成後再執行
    // 初始化 TwCitySelector
    const container = document.querySelector('.tw-city-selector-area');
    if (container) {
        new TwCitySelector({
            el: '.tw-city-selector-area',
            elCounty: '.country',
            elDistrict: '.district',
            elZipcode: '.zipcode',
            onChange: function (county, district, zipcode) {
                form.country = county;
                form.district = district;
                form.zipcode = zipcode;
            }
        });

        // 等一點時間，等 TwCitySelector 將縣市、區域選項渲染完畢
        setTimeout(() => {
            const fullAddr = address.value || '';
            const { county, district, detail } = parseAddress(fullAddr);

            const countySelect = container.querySelector('.country');
            const districtSelect = container.querySelector('.district');
            const zipcodeInput = container.querySelector('.zipcode');  // <-- 這裡要先取得

            if (countySelect) {
                countySelect.value = county;
                countySelect.dispatchEvent(new Event('change'));
            }
            if (districtSelect) {
                districtSelect.value = district;
                districtSelect.dispatchEvent(new Event('change'));
            }

            form.country = county;
            form.district = district;
            form.zipcode = zipcodeInput ? zipcodeInput.value : ''; // 這裡才有值
            form.address = detail;
        }, 300);

    }
    form.lastname = lastname.value;
    form.firstname = firstname.value;
    form.email = email.value;
    form.phone = phone.value;
    // 從 localStorage 讀取資料並更新 filterStore
    const savedItems = sessionStorage.getItem('confirmtobuy');
    if (savedItems) {
        await filterStore.toBuy(JSON.parse(savedItems));
    }
});
watch(couponObj, (newVal) => {
    if (newVal && newVal.code) {
        couponCodeInput.value = newVal.code;
    } else {
        couponCodeInput.value = '';
    }
}, { immediate: true });
</script>
<template>
    <div v-if="loading" class="loading-overlay">
        <div>正在寄送確認信...</div>
        <div class="spinner"></div>
    </div>
    <div class="page-wrapper">
        <div class="form-container">
            <div class="cartlist">
                <div v-for="p in confirmtobuy" :key="p.id + '-' + p.size" class="product-list">
                    <CartItem :p="p" :openSelectId="openSelectId" @updateOpenSelectId="(id) => openSelectId = id"
                        class="cartitem" />
                </div>
                <div v-if="couponObj" class="total">
                    <span>使用中的優惠券：{{ couponObj.description
                    }}</span>
                </div>
            </div>
            <div class="form">
                <div class="detail">
                    <input type="text" v-model="form.lastname" placeholder="姓氏">
                    <input type="text" v-model="form.firstname" placeholder="名字">
                    <div class="tw-city-selector-area">
                        <select v-model="form.country" class="country"></select>
                        <select v-model="form.district" class="district"></select>
                        <input class="zipcode" type="text" placeholder="郵遞區號" readonly />
                    </div>
                    <input type="text" v-model="form.address" placeholder="地址">
                    <input type="email" v-model="form.email" placeholder="電子郵件">
                    <input type="tel" v-model="form.phone" placeholder="電話號碼" pattern="^[0-9]{10}$" required>
                </div>
                <div class="submit-container">
                    <div class="coupon-input">
                        <input v-model="couponCodeInput" placeholder="輸入優惠券代碼" />
                        <button @click="applyCouponCode">套用優惠券</button>
                        <p v-if="couponError" class="error-msg">{{ couponError }}</p>
                    </div>
                </div>
                <div class="payment">
                    <div style="padding-left: 10px;">
                        <span>付款方式</span>
                        <div>
                            <input type="radio" id="cod" value="cod" v-model="paymentMethod" />
                            <label for="cod">貨到付款</label>
                        </div>
                        <div>
                            <input type="radio" id="credit" value="credit" v-model="paymentMethod" />
                            <label for="credit">信用卡一次付清</label>
                        </div>
                    </div>
                </div>
                <div class="submit-fixed">
                    <span>總金額：{{ totalCost }}</span>
                    <button class="submit" @click="submitOrder">確認訂單</button>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.page-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    max-width: 1200px;
    /* 限制最大寬度 */
    margin: 50px auto;
    /* 水平置中 */
    padding: 0 16px;
    /* 內縮左右邊距 */
    box-sizing: border-box;
    /* 防止內距把寬度撐爆 */
    overflow-x: hidden;
    /* 防止因為某些元素寬度過大出現捲軸 */
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;

}

.form-container {
    margin-top: 50px;
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
}

.form-container a {
    font-size: 30px;
}

.form-container .form {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
}

.cartlist .cartitem {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 8px 0px;
    cursor: default;
}

.product-list {
    padding: 4px 8px;
}

::v-deep(.itemInfo) {
    width: 100%;
}

::v-deep(.productInfo) {
    padding-left: 10px;
}

.cartlist {
    width: 100%;
    height: 100%;
    border: 1px solid rgba(128, 128, 128, 0.2);
    max-height: 350px;
    overflow-y: auto;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    border-radius: 5px;
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
    background-color: #fff;
}

.cartlist:last-child {
    border-bottom: none;
}

.detail {
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 18px;
    box-sizing: border-box;
    justify-content: center;
}

.detail>input {
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    border-radius: 12px;
    height: 50px;
    padding-left: 14px;
    font-size: 16px;
    border: 1px solid #cfd8dc;
    transition: border-color 0.2s ease;
}

.detail>input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 6px #3b82f6aa;
}

.tw-city-selector-area {
    width: 100%;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    box-sizing: border-box;
}

.tw-city-selector-area select,
.tw-city-selector-area input {
    flex-grow: 1;
    min-width: 0;
    height: 50px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    border: 1px solid #cfd8dc;
    font-size: 16px;
    transition: border-color 0.2s ease;
}

.tw-city-selector-area input {
    padding-left: 14px;

}

.tw-city-selector-area select:focus,
.tw-city-selector-area input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 6px #3b82f6aa;
}

.submit-container {
    background-color: #fff;
    width: 100%;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 5px;
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
}

.submit {
    width: 140px;
    height: 38px;
    background-color: #3b82f6;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgb(59 130 246 / 0.6);
    transition: background-color 0.3s ease;
    margin-right: 10px;
}

.submit-fixed {
    position: fixed;
    left: 0;
    bottom: 0;
    background-color: #fff;
    border-top: 1px solid rgba(128, 128, 128, 0.2);
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.submit-fixed span {
    font-weight: 700;
    color: #2c3e50;
    user-select: none;
}

.coupon-input {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-evenly;
}

.coupon-input>input {
    width: 140px;
    height: 44px;
    padding-left: 12px;
    font-size: 16px;
    border-radius: 12px;
    border: 1px solid #cfd8dc;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    transition: border-color 0.2s ease;
}

.coupon-input>input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 6px #3b82f6aa;
}

.coupon-input button {
    width: 140px;
    height: 44px;
    background-color: #ef4444;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgb(239 68 68 / 0.7);
    transition: background-color 0.3s ease;
}

.total span {
    border-top: 1px solid #e1e6f0;
    padding-left: 10px;
    letter-spacing: 1px;
    display: flex;
    text-align: right;
    font-weight: bold;
    padding-right: 5%;
    justify-content: space-between;
}

.payment {
    width: 100%;
    background-color: #fff;
    margin-bottom: 50px;
    display: flex;
    flex-direction: column;
    border-radius: 5px;
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
    font-weight: 600;
    font-size: 16px;
    color: #374151;
    margin-bottom: 60px;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    color: #333;
    z-index: 9999;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 6px solid #ccc;
    border-top-color: #3a8ee6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* 旋轉動畫 */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>