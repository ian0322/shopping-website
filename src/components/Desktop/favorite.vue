<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2'
import { ref, computed } from 'vue';
defineOptions({ inheritAttrs: false });
const filterStore = useFilterStore();
const hoveredId = ref(null)
const currentProduct = ref(null);
const sizeStock = ref({});
const showModal = ref(false)
const allSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
const showToast = ref(false);
const sortedTags = computed(() => {
    if (!currentProduct.value || typeof currentProduct.value.tags !== 'string') return ''
    return currentProduct.value.tags
        .split(',')
        .sort((a, b) => {
            if (a.length !== b.length) return a.length - b.length
            return a.localeCompare(b, 'zh-Hant')
        })
        .join(',')
})
const {
    favoriteList, animatingId
} = storeToRefs(filterStore);
const removeItem = (id) => {
    filterStore.removeFromFav(id);
};
const viewDetails = (product) => {

    currentProduct.value = product;
    sizeStock.value = filterStore.parseSizeToStock(product.size); // 轉換尺寸庫存
    // 設定第一個有庫存的尺寸為預設選擇
    const availableSize = allSizes.find(size => sizeStock.value[size] > 0);
    filterStore.selectedSize = availableSize || null;

    showModal.value = true;
};
const closeShowmodal = () => {
    showModal.value = false;
}
function showAddToCartToast() {
    if (filterStore.selectedSize) {
        showToast.value = true;
    }
    setTimeout(() => {
        showToast.value = false;
    }, 2000);
}
function handleAddToCart(product) {
    const success = filterStore.addToCart(product);
    if (success) {
        showAddToCartToast();
    }
}
</script>
<template>
    <teleport to="body">
        <div v-if="showModal" class="modal-overlay" @click.self="closeShowmodal">
            <div v-if="showToast" class="toast">已加入購物車</div>
            <div class="modal">
                <i id="close" class="fa fa-times fa-lg" aria-hidden="true" @click="closeShowmodal"></i>
                <div class="product_info">
                    <img :src="currentProduct?.file_path" alt="product image"
                        style="width: 400px;height:400px;object-fit: contain; /* 關鍵設定：保持圖片比例 */" />
                </div>
                <div class="name">{{ currentProduct.name }}</div>
                <div class="size">
                    <div class="size-btn">
                        <button v-for="size in allSizes" :key="size"
                            :disabled="!sizeStock[size] || sizeStock[size] <= 0"
                            :class="{ selected: filterStore.selectedSize === size }"
                            @click="filterStore.selectedSize = size">
                            {{ size }}
                        </button>
                    </div>
                </div>
                <div class="info-row">
                    <div class="spacer"></div>
                    <p v-if="filterStore.selectedSize" class="stock">庫存：{{ sizeStock[filterStore.selectedSize] }}</p>
                    <div class="favorite">
                        <i v-if="filterStore.userId" id="love" :class="[
                            favoriteList.some(fav => fav.id == currentProduct.id) ? 'fa fa-heart loved' : 'fa fa-heart-o',
                            animatingId === currentProduct.id ? 'fa-beat fa-solid' : ''
                        ]" @click="filterStore.addToFavorite(currentProduct)" style="cursor:pointer"></i>
                    </div>
                </div>
                <div class="details">價格：${{ currentProduct.price }}</div>
                <div class="details">產品說明：{{ sortedTags }}</div>
                <button class="addshopcart" @click="handleAddToCart(currentProduct)">加入購物車</button>
            </div>
        </div>
    </teleport>
    <div v-if="favoriteList.length === 0">目前收藏清單是空的</div>
    <div v-else class="fav-container" :class="$attrs.class">
        <transition-group name="fade-slide" tag="div" class="favList" appear>
            <div v-for="p in favoriteList" :key="p.id" class="favproduct-list">
                <div class="favproductInfo" @click="viewDetails(p)" style="cursor: pointer;">
                    <img :src="p.file_path" alt="product image" />
                </div>
                <p class="fav_name">{{ p.name }}</p>
                <p class="fav_price">${{ p.price }}</p>
                <div class="favremove">
                    <i :class="[
                        'fas',
                        hoveredId === p.id ? 'fa-heart-broken fa-2x' : 'fa-heart fa-2x'
                    ]" @mouseenter="hoveredId = p.id" @mouseleave="hoveredId = null" @click="removeItem(p.id)"></i>
                </div>
            </div>
        </transition-group>
    </div>
</template>
<style scoped>
.fav-container {
    width: 100%;
    box-sizing: border-box;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.favList {
    width: 100%;
    max-width: 900px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    background-color: #fafafa;
    overflow-y: auto;
}

/* 進場滑動淡入動畫 */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.favproduct-list {
    display: flex;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
    gap: 20px;
}

.favproduct-list:last-child {
    border-bottom: none;
}

.favproductInfo img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 6px;
    background-color: white;
}

.fav_name,
.fav_price {
    font-size: 18px;
    flex: 1;
    color: #333;
}

.fav_price {
    text-align: center;
    font-weight: 500;
}

.favremove {
    flex: 0 0 40px;
    text-align: center;
}

.favremove i {
    cursor: pointer;
    transition: transform 0.3s;
}

.favremove .fa-heart {
    color: red;
}

.favremove .fa-heart-broken {
    color: black;
    transform: rotate(20deg);
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    /* 加深遮罩更有層次 */
    z-index: 9999;
    backdrop-filter: blur(3px);
    /* 模糊背景更聚焦 */
}

.modal {
    /* 保留原本固定置中及大小 */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 30%;
    max-height: 90%;
    background: #fff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    padding: 24px 32px;
    border-radius: 12px;
    overflow-y: auto;
    z-index: 1000;

    display: flex;
    flex-direction: column;
    align-items: center;
}

.modal::-webkit-scrollbar {
    width: 6px;
}

.modal::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.15);
    border-radius: 3px;
}

.modal::-webkit-scrollbar-track {
    background: transparent;
}

.modal .info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    /* 左中右三區域 */
    margin-bottom: 12px;
    font-size: 16px;
    color: #444;
    width: 100%;
}

.modal .info-row .stock {
    flex: 1;
    text-align: center;
    /* 庫存置中 */
}

.modal .info-row .favorite {
    flex: 1;
    text-align: right;
    /* 愛心靠右 */
}

.modal .info-row .spacer {
    flex: 1;
    /* 空白區塊，推動中間置中 */
}

.modal #close {
    align-self: flex-end;
    cursor: pointer;
    margin-bottom: 16px;
    color: #333;
    transition: color 0.3s;
}

.modal #close:hover {
    color: #000;
}

.modal .product_info {
    border: 1.5px solid #333;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    width: 100%;
    height: auto;
    padding: 8px;
    box-sizing: border-box;
    margin-bottom: 16px;
    background: #fafafa;
}

.modal .size {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
    margin-bottom: 12px;
}

.size-btn {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
}

.size-btn button {
    flex: 0 0 auto;
    width: 70px;
    height: 36px;
    font-weight: 600;
    border: 1.8px solid #444;
    border-radius: 8px;
    background-color: white;
    cursor: pointer;
    transition: background-color 0.3s, border-color 0.3s;
}

.size-btn button:hover:not(:disabled) {
    background-color: #f0f0f0;
}

.size-btn button:disabled {
    cursor: not-allowed;
    color: #999;
    border-color: #ccc;
    background-color: #eee;
}

button.selected {
    border: 2px solid #4a90e2;
    color: #1a3e72;
}

.modal .name {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
    width: 100%;
}

.modal .details {
    font-size: 16px;
    letter-spacing: 1px;
    margin: 6px 0;
    text-align: center;
    color: #444;
}

.modal .addshopcart {
    background-color: #222;
    color: white;
    border: none;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 24px;
    white-space: nowrap;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px 20px;
    width: fit-content;
    font-size: 18px;
    transition: background-color 0.3s;
    align-self: center;
}

.modal .addshopcart:hover {
    background-color: #444;
}

#love {
    cursor: pointer;
}

.loved {
    -webkit-text-stroke-width: 1.5px;
    -webkit-text-stroke-color: black;
    color: red;
}

.toast {
    position: fixed;
    background: rgba(72, 187, 120, 0.8);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    left: 50%;
    top: 50%;
    /* 改成 top 50% */
    transform: translate(-50%, -50%);
    /* 往左上偏移自身寬高的一半，達成真正置中 */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    animation: fadeInOut 2s forwards;
    z-index: 2000;
}

@keyframes fadeInOut {
    0% {
        opacity: 0;
    }

    20%,
    80% {
        opacity: 1;
    }

    100% {
        opacity: 0;
    }
}
</style>