<script setup>
import { ref, onMounted } from 'vue'
import { useFilterStore } from '@/stores/filterStore2';

const filterStore = useFilterStore();

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
        <div class="order-container">
            <transition-group name="fade-slide" tag="div" appear>
                <div v-for="order in orders" :key="order.order_id" class="order">
                    <h2 @click="toggle(order.order_number)">
                        訂單編號：{{ order.order_number }}
                        <i class="fa fa-angle-up" v-if="expandedOrders.has(order.order_number)"></i>
                        <i class="fa fa-angle-down" v-else></i>
                    </h2>
                    <transition name="fade-slide" mode="out-in">
                        <div v-if="expandedOrders.has(order.order_number)" class="order-list" key="list">
                            <p>日期：{{ formatDate(order.order_date) }}</p>

                            <div v-for="item in order.items" :key="item.product_id" class="history-items">
                                <img :src="item.file_path" alt="" />
                                <div class="product-info">
                                    <div class="name">{{ item.name }}</div>
                                    <div class="size-quantity">
                                        <span>{{ item.size }}</span>
                                        <span>x{{ item.quantity }}</span>
                                    </div>
                                    <div class="total">${{ formatPrice(item.price * item.quantity) }}</div>
                                </div>
                            </div>
                            <div class="total-row">
                                總金額：${{formatPrice(order.items.reduce((sum, item) => sum + item.price * item.quantity,
                                    0))
                                }}
                                付款： {{ order.payment === 1 ? '已付款' : '未付款' }}
                            </div>
                        </div>
                    </transition>
                </div>
            </transition-group>
        </div>
    </div>
</template>

<style scoped>
.order-container {
    display: grid;
    gap: 16px;
    max-width: 600px;
    margin: 0 auto;
}

.order {
    background: #f9f9f9;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    margin-bottom: 6%;
}

.order h2 {
    background-color: rgba(204, 204, 204, 0.2);
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 8px;
}

.order-list {
    max-height: 200px;
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    margin-top: 8px;
}

.history-items {
    display: flex;
    gap: 10px;
    margin-bottom: 5px;
    font-size: 16px;
    height: 100px;
}

.history-items img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border: 1px solid rgba(128, 128, 128, 0.2);
    border-radius: 3px;
}

.product-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    position: relative;
}

.product-info .name {
    text-align: start;
}

.product-info .size-quantity {
    margin-top: 2px;
    font-size: 13px;
    display: flex;
    justify-content: space-between;
    color: gray;
}

.product-info .total {
    position: absolute;
    right: 0;
    bottom: 0;
}

.total-row {
    background-color: #ccc;
    text-align: end;
    padding-right: 10px;
    font-size: 16px;
    font-weight: bold;
    margin-top: 10px;
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
