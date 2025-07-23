<script setup>
import { useFilterStore } from '@/stores/filterStore2'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router';
import { ref } from 'vue';
const router = useRouter();
const filterStore = useFilterStore()
const showModal = ref(false);
const text = ref('')          // 訂單編號
const email = ref('')         // 電子信箱
const orderResult = ref([]) // 查詢結果
const {
    userId,
    username,
    searchInput,
    hoveredNavTop,
    hoveredNavSubTags,
    hoveredNavSub,
    shopcart,
} = storeToRefs(filterStore)
const {
    navTopTags,
    onNavTopClick,
    onNavSubClick,
    applySearch,
} = filterStore
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
                            <h2>
                                訂單編號：{{ order.order_number }}
                            </h2>
                            <div class="order-list">
                                <p>日期：{{ order.order_date }}</p>
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
                                            <td class="img"><img :src="item.file_path" alt="" class="product-img" />
                                            </td>
                                            <td class="name">{{ item.name }}</td>
                                            <td class="size">{{ item.size }}</td>
                                            <td class="price">{{ item.price }}</td>
                                            <td class="quantity">{{ item.quantity }}</td>
                                            <td>{{ item.price * item.quantity }}</td>
                                        </tr>
                                        <tr class="total-row">
                                            <td colspan="6">
                                                訂單金額：{{order.items.reduce((sum, item) => sum + item.price *
                                                    item.quantity, 0)}}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
        <div class="navTop">
            <div class="navTopCenter">
                <div class="navTopTags" v-for="tag in navTopTags" :key="tag" @mouseenter="hoveredNavTop = tag"
                    @mouseleave="hoveredNavTop = null">
                    <button class="navMain" @click.prevent="onNavTopClick(tag)"
                        :class="{ active: hoveredNavTop === tag }">
                        <span class="top"></span>
                        <span class="right"></span>
                        <span class="bottom"></span>
                        <span class="left"></span>
                        {{ tag }}
                    </button>
                    <div class="navSubTags" v-show="hoveredNavTop === tag && hoveredNavSubTags.length > 0">
                        <button v-for="subTag in hoveredNavSubTags" :key="subTag" @mouseenter="hoveredNavSub = subTag"
                            @mouseleave="hoveredNavSub = null" @click.prevent="onNavSubClick(subTag)">
                            {{ subTag }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="icon">
                <div class="search_container">
                    <button id="search" @click.prevent="applySearch">
                        <i class="fas fa-search"></i>
                    </button>
                    <input class="search_input" v-model="searchInput" placeholder="搜尋商品名稱"
                        @keydown.enter="applySearch" />
                </div>
                <i class="fa fa-heart-o" aria-hidden="true" @click="favorite_page()"></i>
                <router-link to="/shopcart">
                    <img src="/img/shoppingcart.svg" width="32" height="32" id="shopcart"
                        style="cursor: pointer;"></img>
                    <div v-if="shopcart.length != 0" class="shopcart_length">{{ shopcart.length }}</div>
                </router-link>
                <a href="#" @click.prevent="goToHistory">
                    <img src="/img/list.svg" width="32" height="32" />
                </a>
            </div>
        </div>
    </div>
</template>
<style scoped>
.navTop {
    position: relative;
    width: 100%;
    display: flex;
    height: 60px;
    /* 兩側對齊 */
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
    background-color: white;
    z-index: 1000;
}

.navTopCenter {
    display: flex;
    justify-content: flex-end;
    flex: 1;
    /* 撐開中間空間，置中 */
    gap: 10px;
}

.navTopTags {
    width: 10%;
    position: relative;
    display: flex;
    margin-right: 50px;
}

.navTopTags button {
    cursor: pointer;
    border: none;
    width: 100%;
    font-size: 18px;
}

.navMain {
    position: relative;
    overflow: hidden;
    padding: 10px 20px;
    background: white;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    z-index: 1;
}

/* 邊框共用樣式 */
.navMain span {
    content: "";
    position: absolute;
    background: rgb(116, 114, 114);
    opacity: 0;
    transition: transform 0.4s ease, opacity 0.4s ease;
    pointer-events: none;
}

/* 上邊線 */
.navMain span.top {
    height: 2px;
    width: 100%;
    top: 0;
    left: 0;
    transform: translateX(-100%);
}

/* 右邊線 */
.navMain span.right {
    width: 2px;
    height: 100%;
    top: 0;
    right: 0;
    transform: translateY(100%);
}

/* 下邊線 */
.navMain span.bottom {
    height: 2px;
    width: 100%;
    bottom: 0;
    left: 0;
    transform: translateX(100%);
}

/* 左邊線 */
.navMain span.left {
    width: 2px;
    height: 100%;
    top: 0;
    left: 0;
    transform: translateY(-100%);
}

/* 滑入時觸發動畫 */
.navMain.active span.top,
.navMain.active span.right,
.navMain.active span.bottom,
.navMain.active span.left {
    opacity: 0.8;
    transform: translate(0, 0);
}

.navSubTags {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    padding: 5px 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 10;
    min-width: 120px;
}

.navSubTags button {
    width: 100%;
    box-sizing: border-box;
    /* 防止 padding 影響寬度 */
    padding: 10px;
    text-align: left;
    border: none;
    background-color: white;
    cursor: pointer;
}

.navSubTags button:hover {
    background-color: gainsboro;
}

.navTop .icon {
    width: 20%;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-right: 30px;
    gap: 30px;
}

.search_container {
    display: flex;
    align-items: center;
    background-color: rgba(128, 128, 128, 0.2);
    width: 400px;
    height: 40px;
    border-radius: 20px;
    padding: 0;
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

.navTop .fa {
    color: black;
    font-size: 25px;
    cursor: pointer;
    padding-right: 0px;
}

.nav-fixed {
    width: 100%;
    height: auto;
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

    padding-right: 30px;
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
    width: 20px;
    height: 20px;
    border: 2.5px solid rgb(255, 255, 255);
    text-align: center;
    background-color: red;
    border-radius: 50%;
    margin: -40px 0px 0px 20px;
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
    margin-bottom: 16px;
    color: #666;
    text-align: center;
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

.order-container {
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
}

.order {
    background: #f9f9f9;
    border: 1px solid #ddd;
    margin-bottom: 24px;
    padding: 16px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.order h2 {
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

.product-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 6px;
}

.name {
    text-align: left;
    padding-left: 10px;
}

td:not(.name) {
    text-align: center;
    vertical-align: middle;
}

.total-row td {
    background-color: #eee;
    font-weight: bold;
    font-size: 18px;
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: right;
}
</style>
