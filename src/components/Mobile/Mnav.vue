<script setup>
import { useFilterStore } from '@/stores/filterStore2'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router';
import { ref } from 'vue';
const router = useRouter();
const filterStore = useFilterStore()
const showModal = ref(false);
const text = ref('ORD202505251001373818')          // 訂單編號
const email = ref('a0966170322@gmail.com')         // 電子信箱
const orderResult = ref([]) // 查詢結果
const {
    userId,
    username,
    searchInput,
    shopcart,
} = storeToRefs(filterStore)
const {
    applySearch,
} = filterStore
function formatPrice(price) {
    return `$${price.toFixed(2)}`
}
const closeShowmodal = () => {
    showModal.value = false;
    text.value = '';
    email.value = '';
    orderResult.value = [];
}
const favorite_page = () => {
    if (userId.value) {
        router.push({ name: 'UserInfo', query: { tab: 'favorite' } })
    } else {
        alert("請登入解鎖此功能");
    }
}
function goToHistory() {
    if (userId.value) {
        router.push({ name: 'UserInfo', query: { tab: 'history' } })
    } else {
        showModal.value = true;
    }
}
const searchOrder = async () => {
    if (!text.value || !email.value) {
        alert('請輸入訂單編號與電子信箱');
        return;
    }

    const res = await fetch(`/history_order.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_number: text.value,
            email: email.value
        })
    });
    const result = await res.json();
    if (result.orders && result.orders.length > 0) {
        orderResult.value = result.orders;
    } else {
        alert(result.message || '查無符合訂單');
        orderResult.value = [];
    }

};

function handleInput(e) {
    // 將輸入轉成大寫並去除非英數字
    text.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '')
}
async function logout() {
    await fetch(`/logout.php`, {
        method: "POST",
        credentials: "include",
    });

    filterStore.guestLogin();
    router.push({ name: "PT" });
}
const goBack = () => {
    window.history.back();
};
</script>

<template>
    <teleport to="body">
        <div v-if="showModal" class="modal-overlay">
            <div class="modal">
                <div class="order-container">
                    <div id="close">
                        <div>查詢訂單</div>
                        <i class="fa fa-times fa-lg" aria-hidden="true" @click="closeShowmodal"></i>
                    </div>
                    <div class="order-tip">請輸入訂單編號及電子信箱</div>
                    <div class="order-form">
                        <input v-model="text" @input="handleInput" maxlength="21" placeholder="訂單編號"
                            autocomplete="off" />
                        <input v-model="email" type="email" placeholder="電子信箱" autocomplete="email" />
                        <button @click="searchOrder">確認</button>
                    </div>

                    <div v-if="orderResult.length" class="order-list-wrapper">
                        <div v-for="order in orderResult" :key="order.order_id" class="order">
                            <h2>訂單編號：{{ order.order_number }}</h2>
                            <div class="order-list">
                                <p>日期：{{ order.order_date }}</p>

                                <div v-for="item in order.items" :key="item.product_id" class="history-items">
                                    <img :src="item.file_path" alt="" />
                                    <div class="product-info">
                                        <div class="name">{{ item.name }}</div>
                                        <div class="size-quantity">
                                            <span>{{ item.size }}</span>
                                            <span>x{{ item.quantity }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="total-row">
                                    總金額：{{formatPrice(order.items.reduce((sum, item) => sum + item.price *
                                        item.quantity,
                                        0))
                                    }}
                                    付款: {{ order.payment === 1 ? '已付款' : '未付款' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
    <div v-bind="$attrs" class="nav-fixed">
        <!-- 導覽列內容 -->
        <div class="nav-link">
            <router-link to="/PT" class="return">返回首頁</router-link>
            <router-link v-if="userId" :to="{ name: 'UserInfo', query: { tab: 'userinfo' } }">
                <i class="fa fa-user" aria-hidden="true"></i>
                {{ username }}</router-link>
            <a v-else>訪客</a>
            <span class="vertical-line"></span>
            <router-link v-if="!username || username === '訪客'" to="/register">註冊</router-link>
            <span v-if="!username || username === '訪客'" class="vertical-line"></span>
            <router-link v-if="!username || username === '訪客'" to="/login">登入</router-link>
            <a class="logout" style="cursor: pointer;" v-if="username && username !== '訪客'" @click="logout">登出</a>
        </div>
        <div class="icon">
            <i style="margin-left: 10px;" class="fa fa-long-arrow-left" aria-hidden="true" @click="goBack"></i>
            <div class="search_container">
                <button id="search" @click.prevent="applySearch">
                    <i class="fas fa-search"></i>
                </button>
                <input class="search_input" v-model="searchInput" placeholder="搜尋商品名稱" @keydown.enter="applySearch" />
            </div>
            <i class="fa fa-heart-o" aria-hidden="true" @click="favorite_page()"></i>
            <router-link to="/shopcart">
                <img src="/img/shoppingcart.svg" width="24" height="24" id="shopcart" style="cursor: pointer;"></img>
                <div v-if="shopcart.length != 0" class="shopcart_length">{{ shopcart.length }}</div>
            </router-link>
            <a href="#" @click.prevent="goToHistory">
                <img style="margin-right:2px;" src="/img/list.svg" width="26" height="26" />
            </a>
        </div>
    </div>
</template>
<style scoped>
.return {
    position: absolute;
    left: 0;
    padding-left: 10px;
}

.icon {
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
    width: 100%;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 3px 0px 3px 0px;
    gap: 30px;
}

.search_container {
    display: flex;
    align-items: center;
    background-color: rgba(128, 128, 128, 0.2);
    width: 400px;
    height: 40px;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
}

#search {
    width: 40px;
    height: 40px;
    font-size: 25px;
    border: none;
    background: transparent;
    border-radius: 50%;
    cursor: pointer;
    position: absolute;
    left: 0;
    top: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

#search:hover {
    background: rgb(128, 128, 128, 0.2);
}

.search_input {
    flex: 1;
    background-color: transparent;
    height: 100%;
    border: none;
    outline: none;
    font-size: 14px;
    padding-left: 50px;
    padding-right: 10px;
    border-radius: 20px;
    position: relative;
    z-index: 1;
}

.nav-fixed {
    width: 100%;
    height: auto;
    background-color: #fff;
}

.nav-link {
    /* 你現有的 */
    letter-spacing: 2px;
    position: relative;
    display: flex;
    width: 100%;
    height: 100%;
    background-color: #f0f0f0;
    justify-content: flex-end;
    /* 新增 */
    align-items: center;
    /* 讓所有子元素垂直置中 */
    padding-right: 10px;
    box-sizing: border-box;
}

.nav-link>* {
    color: black;
    text-decoration: none;
}

.nav-fixed {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

.vertical-line {
    width: 1px;
    height: 20px;
    margin: 4px 5px;
    background-color: black;
    align-self: center;
    /* 在 flex 內垂直置中 */
}

.shopcart_length {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
    font-size: 13px;
    font-weight: bolder;
    width: 16px;
    height: 16px;
    border: 2.5px solid rgb(255, 255, 255);
    text-align: center;
    background-color: red;
    border-radius: 50%;
    margin: -35px 0px 0px 20px;
    color: white;
    user-select: none;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 900px;
    max-height: 90%;
    overflow-y: auto;
}

/* 保留 modal 查詢區樣式 */
#close {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 16px;
}

#close i {
    position: absolute;
    right: 0;
    cursor: pointer;
}

.order-tip {
    margin: 16px 0;
    color: #666;
    font-size: 14px;
}

.order-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 20px;
}

.order-form input,
.order-form button {
    height: 40px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.order-form button {
    background-color: #d9534f;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

/* ✅ 改為 A 段樣式的訂單展示區 */
.order-container {
    display: grid;
    gap: 16px;
    max-width: 600px;
    margin: 0 auto;
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
}

.order-list-wrapper {
    display: grid;
    gap: 16px;
}

.order {
    background: #f9f9f9;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    margin-bottom: 6%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.order h2 {
    background-color: rgba(204, 204, 204, 0.2);
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: default;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 8px;
}

.order-list {
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #ccc transparent;
    margin-top: 8px;
}

.order-list p {
    margin-bottom: 8px;
    color: #555;
}

/* ✅ A 段樣式的訂單項目呈現 */
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

/* 響應式設計 */
@media screen and (max-width: 640px) {
    .modal {
        margin: 20px 10px;
    }

    .order-form {
        flex-direction: column;
    }

    .order-form button {
        width: 100%;
    }

    .history-items img {
        width: 80px;
        height: 80px;
    }
}
</style>