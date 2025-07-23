<script setup>
import { ref, onMounted } from 'vue'


const orders = ref([]);
const loading = ref(false);
const expandedOrders = ref(new Set()); // 用 Set 紀錄展開的訂單編號

function formatPrice(price) {
    return `${price.toFixed(2)}`
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleString();
}

async function fetchOrders() {
    loading.value = true;
    try {
        const res = await fetch(`/history_order.php`, {
            credentials: 'include',
        });
        const data = await res.json();
        orders.value = data.orders || [];
    } catch (error) {
        console.error('取得歷史訂單失敗', error);
    } finally {
        loading.value = false;
    }
}

function toggle(orderNumber) {
    if (expandedOrders.value.has(orderNumber)) {
        expandedOrders.value.delete(orderNumber);
    } else {
        expandedOrders.value.add(orderNumber);
    }
}

onMounted(() => {
    fetchOrders();
});
</script>

<template>
    <div>
        <div v-if="loading">載入中...</div>
        <div v-else-if="orders.length === 0">目前沒有訂單資料</div>
        <div v-else class="order-container">
            <transition-group name="fade-slide" tag="div" appear>

                <div v-for="order in orders" :key="order.order_id" class="order">
                    <h2 @click="toggle(order.order_number)" style="cursor:pointer;">
                        訂單編號：{{ order.order_number }}
                        <i class="fa fa-angle-up" v-if="expandedOrders.has(order.order_number)"></i>
                        <i class="fa fa-angle-down" v-else></i>
                    </h2>
                    <transition name="fade-slide" mode="out-in">
                        <div v-if="expandedOrders.has(order.order_number)" class="order-list">
                            <p>日期：{{ formatDate(order.order_date) }}</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th>圖片</th>
                                        <th>名稱</th>
                                        <th>尺寸</th>
                                        <th>單價</th>
                                        <th>數量</th>
                                        <th>小計</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in order.items" :key="item.product_id" class="history-items">
                                        <td class="img"><img :src="item.file_path" alt="" class="product-img" /></td>
                                        <td class="name">{{ item.name }}</td>
                                        <td class="size">{{ item.size }}</td>
                                        <td class="price">{{ formatPrice(item.price) }}</td>
                                        <td class="quantity">{{ item.quantity }}</td>
                                        <td>{{ formatPrice(item.price * item.quantity) }}</td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="6" style="text-align: right; font-weight: bold;">
                                            總金額：${{formatPrice(order.items.reduce((sum, item) => sum + item.price *
                                                item.quantity, 0))}}
                                            付款：{{ order.payment === 1 ? '已付款' : '未付款' }}

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </transition>
                </div>
            </transition-group>
        </div>
    </div>
</template>

<style scoped>
.order-container {
    width: 100%;
    padding: 20px;
    box-sizing: border-box;
}

.order {
    background: #f9f9f9;
    border: 1px solid #ddd;
    margin-bottom: 24px;
    padding: 16px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.3s ease;
}

.order:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

h2 {
    font-size: 20px;
    font-weight: bold;
    background-color: rgba(204, 204, 204, 0.2);
    padding: 12px 16px;
    margin-bottom: 10px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-list {
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #ccc transparent;
}

.order-list::-webkit-scrollbar {
    width: 6px;
}

.order-list::-webkit-scrollbar-thumb {
    background-color: #bbb;
    border-radius: 4px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 16px;
    background: white;
}

thead {
    background-color: #ff9f1a;
    color: white;
    font-weight: bold;
}

th,
td {
    padding: 10px;
    border: 1px solid #eee;
}

.history-items img,
.product-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 6px;
}

.name {
    padding-left: 10px;
    text-align: left;
}

td:not(.name) {
    text-align: center;
    vertical-align: middle;
}

.history-items {
    font-size: 16px;
}

.total-row td {
    background-color: #eee;
    font-weight: bold;
    font-size: 18px;
    padding-top: 12px;
    padding-bottom: 12px;
}

/* 動畫效果 */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {

    opacity: 1;
    max-height: 1000px;
    /* 足夠大讓內容展開 */
    transform: translateY(0);
}
</style>
