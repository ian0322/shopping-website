<script setup>
import { onMounted, nextTick, ref, reactive, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2';
import CartItem from './cartItem.vue';
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
            paymentMethod: paymentMethod.value
        };
        const res = await fetch(`/submit_order.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        if (result.success) {
            if (paymentMethod.value === 'credit') {
                // 前往 ECPay（gopay）付款
                goPay(result);
            }
            else {
                sessionStorage.removeItem('confirmtobuy');
                sessionStorage.removeItem('toBuyCart');
                localStorage.removeItem('selectedCoupon')
                if (!userId.value) {
                    form.lastname = '';
                    form.firstname = '';
                    form.email = '';
                    form.phone = '';
                    form.address = '';
                    form.country = '';
                    form.district = '';
                    form.zipcode = '';
                    if (zipcodeInput) zipcodeInput.value = '';
                }
                await filterStore.loadCart();
                await filterStore.loadProducts(true); // 再次強制刷新商品資料
            }
            router.push({ name: 'PT' });
            alert('訂單確認信已寄出');
        } else {
            alert('寄信失敗:' + result.message);
        }
    } catch (error) {
        alert('發生錯誤: ' + error.message);
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
    await filterStore.loadSelectedCoupon()
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
            <a>寄送資訊</a>
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
                <div class="cart-container">
                    <div>
                        <div class="cartlist">
                            <div v-for="p in confirmtobuy" :key="p.id" class="product-list">
                                <CartItem :key="p.id" :p="p" :openSelectId="openSelectId"
                                    @updateOpenSelectId="(id) => openSelectId = id" class="cartitem" />
                            </div>
                            <div class="total">
                                <span v-if="couponObj">使用中的優惠券：{{ couponObj.description
                                }}</span>
                                <span>總金額：{{ totalCost }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="payment">
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
                    <div class="submit-container">
                        <div class="coupon-input">
                            <input v-model="couponCodeInput" placeholder="輸入優惠券代碼" />
                            <button @click="applyCouponCode">套用優惠券</button>
                            <p v-if="couponError" class="error-msg">{{ couponError }}</p>
                        </div>
                        <button class="submit" @click="submitOrder">確認訂單</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.page-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    background: #f5f7fa;
    padding: 20px 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}


.form-container {
    position: relative;
    width: auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgb(0 0 0 / 0.1);
    padding: 30px 40px;
    box-sizing: border-box;
}

.form-container a {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    user-select: none;
}

.form-container .form {
    margin-top: 28px;
    display: flex;
    gap: 32px;
    height: auto;
}

.cartlist {
    width: 100%;
    max-height: 350px;
    overflow-y: auto;
    border-radius: 8px;
    border: 1px solid #d1d9e6;
    box-shadow: 0 2px 12px rgb(0 0 0 / 0.05);
    padding: 12px 16px;
    scrollbar-width: thin;
    scrollbar-color: #a3b1c2 transparent;
}

.cartlist::-webkit-scrollbar {
    width: 8px;
}

.cartlist::-webkit-scrollbar-thumb {
    background-color: #a3b1c2;
    border-radius: 4px;
}

.cartlist .cartitem {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e1e6f0;
    transition: background-color 0.15s ease;
    cursor: default;
}

.cartlist .cartitem:last-child {
    border-bottom: none;
}

.cartlist .cartitem:hover {
    background-color: #f0f4f8;
}

.cartlist .cartitem .name {
    flex: 1;
    padding-left: 12px;
    font-size: 18px;
    font-weight: 600;
    color: #34495e;
}

.cartlist .cartitem .price {
    flex: 2;
    justify-content: space-between;
    font-size: 18px;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.cartlist .cartitem .price .quantity {
    color: #7f8c8d;
    padding-right: 12px;
    font-weight: 500;
}

.product-list {
    margin-bottom: 4px;
}

.cart-container {
    width: 100%;
    margin-left: 24px;
    display: flex;
    flex-direction: column;
}

.detail {
    display: flex;
    flex-direction: column;
    width: 40%;
    gap: 18px;
    box-sizing: border-box;
}

.detail>input {
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    border-radius: 12px;
    height: 50px;
    margin-left: 20px;
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
    padding-left: 20px;
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
    padding-left: 10px;
    transition: border-color 0.2s ease;
}

.tw-city-selector-area select:focus,
.tw-city-selector-area input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 6px #3b82f6aa;
}

.submit-container {
    margin-top: 20px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.submit {
    width: 140px;
    height: 44px;
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
}

.submit:hover {
    background-color: #2563eb;
    box-shadow: 0 6px 20px rgb(37 99 235 / 0.8);
}

.coupon-input {
    display: flex;
    align-items: center;
    gap: 16px;
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

.coupon-input button:hover {
    background-color: #dc2626;
    box-shadow: 0 6px 20px rgb(220 38 38 / 0.85);
}

.error-msg {
    color: #ef4444;
    font-weight: 600;
    margin-left: 8px;
    font-size: 14px;
}

.total {
    letter-spacing: 1px;
    display: flex;
    justify-content: space-between;
    margin: 14px 0 8px 0;
    font-weight: 700;
    padding-right: 6%;
    color: #1f2937;
    font-size: 20px;
}

.payment {
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-weight: 600;
    font-size: 16px;
    color: #374151;
}

.payment input[type="radio"] {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.payment label {
    user-select: none;
    cursor: pointer;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(245, 246, 250, 0.85);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    color: #374151;
    z-index: 9999;
}

.spinner {
    width: 52px;
    height: 52px;
    border: 6px solid #d1d5db;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-top: 16px;
}

/* 旋轉動畫 */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>