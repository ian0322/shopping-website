<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2';
import CartItem from './McartItem.vue';
import { ref, computed, watch } from 'vue';
import { useRouter } from "vue-router"; // 引入 useRouter 和 useRoute

const router = useRouter();
const selectAll = computed(() => shopcart.value.every(item => item.selected));
const openSelectId = ref(null);

const touchStartX = ref(0);
const touchEndX = ref(0);
const touchMoved = ref(false);

const handleTouchStart = (e) => {
    touchStartX.value = e.touches[0].clientX;
    touchMoved.value = false;
};

const handleTouchMove = (e) => {
    touchEndX.value = e.touches[0].clientX;
    if (Math.abs(touchEndX.value - touchStartX.value) > 10) {
        touchMoved.value = true;
    }
};

const handleTouchEnd = (item) => {
    if (!touchMoved.value) return;

    const deltaX = touchEndX.value - touchStartX.value;
    if (deltaX < -30) {
        item.showRemove = true;
    } else if (deltaX > 30) {
        item.showRemove = false;
    }
};



// 從 Pinia store 中獲取購物車資料
const filterStore = useFilterStore();
const { shopcart, showModal } = storeToRefs(filterStore);

const removeItem = (id, size) => {
    filterStore.removeFromCart(id, size);
};

const totalCost = computed(() => {
    return shopcart.value
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.price * item.quantity, 0);
});
const increaseQuantity = (product) => {
    filterStore.increase(product);
}

const decreaseQuantity = (product) => {
    filterStore.decrease(product);
}

const onTextInput = (product) => {
    if (product.quantity > 99) product.quantity = 99;
    else if (product.quantity < 1) product.quantity = 1;
    updateLocalStorage();
};

const blockNonNumber = (event) => {
    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab']
    if (!/^\d$/.test(event.key) && !allowedKeys.includes(event.key)) {
        event.preventDefault()
    }
}
// 更新購物車並同步到 localStorage


const toggleSelectAll = (event) => {
    // 如果購物車是空的，不做任何事情，確保不會勾選
    if (shopcart.value.length === 0) {
        selectAll.value = false;
        return;
    }
    const checked = event.target.checked;

    shopcart.value.forEach(item => {
        item.selected = checked;
    });
};


const onBuy = async () => {
    const selectedItems = shopcart.value.filter(item => item.selected);
    if (selectedItems.length === 0) {
        alert('請選擇商品');
        return;
    }
    sessionStorage.setItem('confirmtobuy', JSON.stringify(selectedItems));
    filterStore.toBuy(selectedItems);
    await router.push('/shopcartform');
};

</script>

<template>
    <teleport to="body">
        <div v-if="showModal" class="modal-overlay">
            <div class="modal">
                <p>確定要刪除這個商品嗎？</p>
                <div class="modal-buttons">
                    <button class="confirm" @click="filterStore.confirmDelete">確定刪除</button>
                    <button class="cancel" @click="filterStore.cancelDelete">取消</button>
                </div>
            </div>
        </div>
    </teleport>
    <div class="container">
        <div class="list-container">
            <div class="ListInfo">
                <div class="grid-row list-header">
                    <div class="checkbox-header">
                        <input type="checkbox" :checked="selectAll" @change="toggleSelectAll"
                            :disabled="shopcart.length === 0" />
                    </div>
                    <div class="name-header">商品</div>
                    <div class="count-header">數量</div>
                </div>
            </div>
            <div class="cartList">
                <div v-if="shopcart.length === 0">目前購物車是空的</div>
                <div v-else>
                    <div v-for="p in shopcart" :key="p.id + '-' + p.size" class="swipe-item"
                        @touchstart.passive="(e) => handleTouchStart(e)" @touchmove.passive="(e) => handleTouchMove(e)"
                        @touchend="() => handleTouchEnd(p)" :class="{ 'show-remove': p.showRemove }">
                        <div class="swipe-content">
                            <div class="grid-row">
                                <div class="checkbox"><input type="checkbox" v-model="p.selected"></div>
                                <CartItem :p="p" :openSelectId="openSelectId"
                                    @updateOpenSelectId="(id) => openSelectId = id" class="cartitem" />
                                <div class="count">
                                    <button @click="increaseQuantity(p)">
                                        <span class="fa fa-plus"></span>
                                    </button>
                                    <input type="text" v-model="p.quantity" @input="() => onTextInput(p)"
                                        @keydown="blockNonNumber" />
                                    <button @click="decreaseQuantity(p)">
                                        <span class="fa fa-minus"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="remove" v-if="p.showRemove">
                            <i class="fa fa-trash" @click="removeItem(p.id, p.size)"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button class="delete" @click="filterStore.deleteSelected">刪除所選商品</button>
                <p class="Products-Total">總金額：{{ totalCost }}</p>
                <button class="buy" @click="onBuy">購買</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    display: flex;
    justify-content: center;
    width: 100%;
    height: 100vh;
}

.list-container {
    margin-top: 100px;
    width: 95%;
}

.ListInfo {
    background-color: #f1f5f9;
    border-bottom: 2px solid #cbd5e1;
    font-weight: bold;
    padding: 12px 8px;
    border-radius: 5px;
}

.ListInfo input {
    margin: 0 10px 0 5px;
}

.name {
    width: 100px;
    text-align: center;
    background-color: #1f2937;
}

.total {
    background-color: #1f2937;
    width: 100%;
    text-align: center;
    margin-left: 170px;
}

.cartList {
    max-height: 400px;
    overflow-y: auto;
    margin-top: 10px;
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 transparent;
    border-bottom: 1px solid #e2e8f0;

}

.cartList::-webkit-scrollbar {
    width: 6px;
}

.cartList::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}

.price>div {
    letter-spacing: 2px;
    display: flex;
    justify-content: center;
    /* 讓兩個 p 元素分開顯示 */
    align-items: center;
}

.cartitem {
    display: flex;
    align-items: center;
    padding: 2px 6px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    max-width: 100%;
    /* 不超出容器 */
    overflow: hidden;
    flex: 1;
    /* 自動撐滿 grid 中的剩餘空間 */
    min-width: 0;
    /* 🔧重要，讓文字正確截斷 */
}

.count {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.count input {
    width: 40px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px;
    font-size: 14px;
}

.count button {
    width: 30px;
    height: 30px;
    border: none;
    background-color: #e2e8f0;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
}

.button-container {
    display: flex;
    justify-content: space-between;
    margin-top: 24px;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.buy,
.delete {
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.buy {
    background-color: #3b82f6;
    color: white;
}

.delete {
    background-color: #e2e8f0;
    color: #1e293b;
}

.Products-Total {
    font-size: 14px;
    font-weight: bold;
    color: #1f2937;
    margin-top: 12px;
    text-align: right;
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
    padding: 20px 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    min-width: 280px;
    text-align: center;
}

.modal-buttons {
    display: flex;
    flex-direction: row;
    /* 明確指定為橫向 */
    justify-content: space-between;
    margin-top: 20px;
    gap: 10px;
    /* 加點間距 */
}

.modal-buttons .confirm,
.modal-buttons .cancel {
    flex: 1;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}

.modal-buttons .confirm {
    background-color: #d9534f;
    color: white;
}

.modal-buttons .cancel {
    background-color: #ccc;
    color: black;
}

.swipe-item {
    position: relative;
    overflow: hidden;
}

.swipe-content {
    display: flex;
    background: #fff;
    transition: transform 0.3s ease;
    padding: 12px 8px;

}

.swipe-item:not(:last-child) .swipe-content {
    border-bottom: 1px solid rgba(204, 204, 204, 0.281);
}

.remove {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 60px;
    background-color: red;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
}

.swipe-item.show-remove .swipe-content {
    transform: translateX(-60px);
}

.grid-row {
  display: grid;
  grid-template-columns: 40px 1.8fr 1fr;
  /* 或改為具體寬度： 40px 180px 120px */
  align-items: center;
  gap: 10px;
}

.checkbox-header,
.checkbox {
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 0;
}

.checkbox-header input,
.checkbox input {
    margin: 0;
    vertical-align: middle;
}

.name-header {
    text-align: center;
    font-weight: bold;
}

.count-header {
    text-align: center;
    font-weight: bold;
}


.count-header,
.count {
    width: 120px;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
