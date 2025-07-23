<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore'
import { useRouter } from 'vue-router';
import { nextTick, ref } from 'vue';
const filterStore = useFilterStore();
const router = useRouter();
const openedMenu = ref(null); // 用來記住展開的主分類++
const showModal = ref(false);
const text = ref('')          // 訂單編號
const email = ref('')         // 電子信箱
const orderResult = ref([]) // 查詢結果
const {
    userId,
    username, //++
    categorizedTags, //++
    searchText, //++
    inputText,
    selectedMainTags,
    selectedSubTags,
    currentPage,
    shopcart,
} = storeToRefs(filterStore);
const { apiHost, logout/*, applyFilter*/
} = filterStore;
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
const doSearch = () => {
    searchText.value = inputText.value.trim();
    currentPage.value = 1; // 如果有分頁
    inputText.value = '';  // 清空輸入欄位

};

const applyFilter = (category, subcategory) => {
    nextTick(() => {
        if (router.name !== "Shop") {
            router.push({ name: "Shop" });
        }
    })
    searchText.value = ""; // 清空搜尋框
    selectedMainTags.value = [];
    selectedSubTags.value = [];
    if (!filterStore.mainTags.includes(category) && category !== "所有商品") {
        if (!subcategory) {
            selectedSubTags.value = [category];
        } else {
            selectedSubTags.value = [category, subcategory];
        }
    } else if (!subcategory) {
        // 主分類，沒有選副分類時
        selectedMainTags.value = category === "所有商品" ? [] : [category];
        selectedSubTags.value = [];
    } else {
        // 主分類 + 副分類
        selectedMainTags.value = [category];
        selectedSubTags.value = [subcategory];
    }
    currentPage.value = 1; // 篩選後從第1頁開始

};
const toggleMenu = (category, isHovered) => {
    if (isHovered) {
        openedMenu.value = category;
    } else {
        openedMenu.value = null;
    }
};
function goToHistory() {
    if (userId.value) {
        router.push({ name: 'UserInfo', query: { tab: 'history' } })
    } else {
        showModal.value = true;
    }
}
</script>

<template>
    <teleport to="body">
        <div v-if="showModal" class="modal-overlay">
            <div class="modal">
                <div id="close">
                    <div>查詢訂單</div>
                    <i class="fa fa-times fa-lg" aria-hidden="true" @click="closeShowmodal"></i>
                </div>
                <div style="width: 100%;margin-bottom: 20px;">請輸入訂單編號及電子信箱</div>
                <div class="order-container">
                    <input v-model="text" @input="handleInput" maxlength="21" class="search-order" placeholder="訂單編號" />
                    <input v-model="email" type="email" class="search-email" placeholder="電子信箱" />
                    <input type="submit" class="search-submit" value="確認" @click="searchOrder" />
                </div>

                <div v-if="orderResult.length">
                    <div v-for="order in orderResult" :key="order.order_id" class="order-block">
                        <h3>訂單編號：{{ order.order_number }}</h3>
                        <p>日期：{{ order.order_date }}</p>

                        <table cellspacing="0" cellpadding="8">
                            <thead>
                                <tr>
                                    <th>圖片</th>
                                    <th>名稱</th>
                                    <th>單價</th>
                                    <th>數量</th>
                                    <th>小計</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items" :key="item.product_id" class="history-items">
                                    <td><img :src="item.file_path" alt="" class="product-img" /></td>
                                    <td class="name">{{ item.name }}</td>
                                    <td class="quantity">{{ item.quantity }}</td>
                                    <td class="price">{{ item.price }}</td>
                                    <td>{{ item.price * item.quantity }}</td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="5" style="text-align: right; font-weight: bold;">
                                        總金額：{{(order.items.reduce((sum, item) => sum + item.price *
                                            item.quantity, 0))}}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
    <div class="nav_top">
        <router-link v-if="userId" :to="{ name: 'UserInfo', query: { tab: 'userinfo' } }" class="nav-link">
            <i class="fa fa-user" aria-hidden="true"></i>
            {{ username }}|</router-link>
        <a v-else>訪客|</a>
        <router-link v-if="!username || username === '訪客'" to="/register" class="nav-link">註冊|</router-link>

        <router-link v-if="!username || username === '訪客'" to="/login" class="nav-link">登入|</router-link>

        <a class="logout" v-if="username && username !== '訪客'" @click="logout">登出|</a>

        <div class="main_guide">
            <div class="nav_product">
                <div class="category" v-for="(subcategories, category) in categorizedTags" :key="category"
                    @mouseover="toggleMenu(category, true)" @mouseleave="toggleMenu(category, false)"
                    :class="{ opened: openedMenu === category }" @click="applyFilter(category, '')">
                    <span class="left"></span>
                    <div class="">{{ category }}</div>
                    <span class="bottom"></span>

                    <div v-if="openedMenu === category && subcategories.length > 0" class="sub-categories">
                        <button v-for="subcategory in subcategories" :key="subcategory"
                            @click.stop="applyFilter(category, subcategory)">
                            {{ subcategory }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="icon">
                <div class="search_container">
                    <button id="search" @click.prevent="doSearch">
                        <i class="fas fa-search"></i>
                    </button>
                    <input type="text" v-model="inputText" @keyup.enter.prevent="doSearch" class="search_input"
                        placeholder="搜尋" />
                </div>
                <i class="fa fa-heart-o" aria-hidden="true" @click="favorite_page()"></i>
                <router-link to="/shopcart">
                    <img src="/img/shopping-cart.png" id="shopcart" style="cursor: pointer;"></img>
                    <div v-if="shopcart.length != 0" class="shopcart_length">{{ shopcart.length }}</div>
                </router-link>
                <a href="#" @click.prevent="goToHistory">
                    <img src="/img/order-delivery.png" />
                </a>
            </div>
        </div>
        <!-- 這個才是 main_guide 結束 -->
    </div>
</template>
<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    text-decoration: none;
}

.nav-link {
    color: black;
    text-decoration: none;
}

.nav_top {
    background-color: rgb(233, 233, 233);
    height: 40px;
    position: fixed;
    width: 100%;
    text-align: end;
    padding-right: 40px;
    z-index: 1000;
    letter-spacing: 2px;
}

.main_guide {
    box-shadow: 1px 1px 2px 1px rgba(0, 0, 0, 0.2);
    background-color: white;
    position: fixed;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    padding: 0 40px;
    z-index: 1000;
}

.logout {
    cursor: pointer;
}

/* 中間 ul 固定寬度，靠中間 */
.nav_product {
    width: 100%;
    list-style: none;
    display: flex;
    gap: 40px;
    margin: 0;
    padding: 0;
    white-space: nowrap;
    justify-content: flex-end;
    align-items: center;
    margin-right: 100px;
    flex: 1;
}

.nav_product .category {
    height: 60px;
    display: flex;
    align-items: center;
    position: relative;
    /* 讓子分類能夠定位在父容器內 */
}

.nav_product .sub-categories {
    display: flex;
    /* 使用 Flexbox 布局 */
    flex-direction: column;
    position: absolute;
    top: 100%;
    /* 子分類出現在父元素下方 */
    left: 0;
    background-color: white;
    border: 1px solid #ccc;
    padding: 10px 0;
    z-index: 10;
    width: 100%;
    /* 自動適應內容的寬度 */
}

.nav_product .category button {
    font-size: 18px;
    padding: 10px 20px;
    background: transparent;
    border: none;
    text-align: left;
    width: 100%;
    /* 讓按鈕寬度與容器一致 */
    cursor: pointer;
}

.nav_product .category button:hover {
    background-color: rgba(0, 0, 0, 0.1);
    /* 滑過按鈕時變色 */
}


/* icon 區塊靠右，不用絕對定位 */
.icon {
    position: relative;
    width: 400px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 30px;
}

.nav_product div {
    position: relative;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: bold;
    background: white;
    overflow: visible;
}

/* 邊框動畫：初始透明，滑過後顯示 */
/* 父級項目的邊框動畫 */
.nav_product>div::before,
.nav_product>div::after,
.nav_product>div span.bottom,
.nav_product>div span.left {
    content: "";
    position: absolute;
    background: rgb(116, 114, 114);
    transition: transform 0.4s ease, opacity 0.4s ease;
    opacity: 0;
    /* 初始透明 */
    pointer-events: none;
}

/* 上邊線：從左上角滑入 */
.nav_product>div::before {
    height: 2px;
    width: 100%;
    top: 0;
    left: 0;
    transform: translateX(-100%) translateY(-100%);
    /* 從左上角外部滑入 */
}

/* 右邊線：從右下角滑入 */
.nav_product>div::after {
    width: 2px;
    height: 100%;
    top: 0;
    right: 0;
    transform: translateX(100%) translateY(100%);
    /* 從右下角外部滑入 */
}

/* 下邊線：從右下角滑入 */
.nav_product>div span.bottom {
    height: 2px;
    width: 100%;
    bottom: 0;
    left: 0;
    transform: translateX(100%) translateY(100%);
    /* 從右下角外部滑入 */
}

/* 左邊線：從左上角滑入 */
.nav_product>div span.left {
    width: 2px;
    height: 100%;
    left: 0;
    top: 0;
    transform: translateX(-100%) translateY(-100%);
    /* 從左上角外部滑入 */
}

/* 當滑過時，邊框顯示 */
.nav_product>div:hover::before,
.nav_product>div:hover::after,
.nav_product>div:hover span.bottom,
.nav_product>div:hover span.left {
    opacity: 0.8;
    /* 邊框顯示 */
    transform: translate(0, 0);
    /* 邊框滑動至正確位置 */
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

.main_guide .fa {
    color: black;
    font-size: 25px;
    cursor: pointer;
    padding-right: 0px;
}

.search_input:hover {
    background: rgb(128, 128, 128, 0.4);
}

#search:hover {
    background: rgb(128, 128, 128, 0.2);
}

.shopcart_length {
    position: absolute;
    font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
    font-size: 13px;
    font-weight: bolder;
    width: 20px;
    height: 20px;
    border: 2.5px solid rgb(255, 255, 255);
    border-radius: 50%;
    text-align: center;
    background-color: red;
    margin: -40px 0px 0px 20px;
    padding-left: 2px;
    color: white;
    user-select: none;
}

.order-container {

    display: flex;
    gap: 20px;
    width: 100%;
    height: 50px;
}

.order-container .search-order {
    padding-left: 10px;
    border-radius: 5px;
    flex: 8;
}

.order-container .search-email {
    padding-left: 10px;
    border-radius: 5px;
    flex: 8;
}

.order-container .search-submit {
    border-radius: 4px;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    background-color: #d9534f;
    color: white;
    align-self: center;
    height: 25px;
    flex: 1.5;
}

table {
    table-layout: fixed;
    border: 1px solid #ccc;
    width: 100%;
    height: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 10px 10px 0 0;
    /* 只保留上方圓角 */
    overflow: hidden;
}

table thead {
    background-color: orange;
    width: 100%;
    height: 100%;
}

thead tr {
    width: 100%;
    height: 100%;
}

tbody tr:not(:last-child) {
    box-shadow: inset 0 -1px 0 #ccc;
}

.history-items img {
    width: 150px;
    height: 150px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

.history-items {
    font-size: 20px;
}

td:not(.name) {
    text-align: center;
}

.total-row {
    position: relative;
    right: 5%;
    font-size: 20px;
}

.modal {
    position: absolute;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    width: 50%;
}

.modal #close {
    position: relative;
    display: flex;
    justify-content: center;
    width: 100%;
    margin-bottom: 10px;
}

#close div {
    font-size: 30px;
    letter-spacing: 2px;
}

#close i {
    cursor: pointer;
    position: absolute;
    right: 0;
}
</style>
