<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2';
import CartItem from './cartItem.vue';
import { ref, computed } from 'vue';
import { useRouter } from "vue-router"; // 引入 useRouter 和 useRoute

const router = useRouter();
const selectAll = computed(() => shopcart.value.every(item => item.selected));
const openSelectId = ref(null);

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
                <div class="checkbox">
                    <input type="checkbox" :checked="selectAll" @change="toggleSelectAll"
                        :disabled="shopcart.length === 0" />
                </div>
                <div class="name">商品</div>
                <div class="total">數量</div>
            </div>
            <div class="cartList">
                <div v-if="shopcart.length === 0">目前購物車是空的</div>
                <div v-else>
                    <div v-for="p in shopcart" :key="p.id" class="product-list">
                        <div class="checkbox"><input type="checkbox" v-model="p.selected"></div>
                        <CartItem :key="p.id" :p="p" :openSelectId="openSelectId"
                            @updateOpenSelectId="(id) => openSelectId = id" class="cartitem" />
                        <div class="count"><button class="fa fa-plus" @click="increaseQuantity(p)"></button>
                            <input type="text" v-model="p.quantity" @input="() => onTextInput(p)"
                                @keydown="blockNonNumber" />
                            <button class="fa fa-minus" @click="decreaseQuantity(p)"></button>
                        </div>
                        <div class="remove">
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
    margin-top: 100px;
    display: flex;
    justify-content: center;
    padding: 20px;
    background-color: #f7f7f7;
    max-height: 500px;
}

.list-container {
    width: 100%;
    max-width: 900px;
    background-color: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ListInfo {
    display: grid;
    grid-template-columns: 1fr 5fr 2fr 1fr;
    padding: 16px 12px;
    border-bottom: 2px solid #cbd5e1;
    font-weight: bold;
    text-align: center;
    background-color: #f1f5f9;
    font-size: 16px;
}

.product-list {
    display: grid;
    grid-template-columns: 1fr 5fr 2fr 1fr;
    padding: 16px 12px;
    align-items: center;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    transition: background-color 0.2s ease;
}

.product-list:hover {
    background-color: #f9fafb;
}

.cartList {
    max-height: 400px;
    overflow-y: auto;
    margin-top: 10px;
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 transparent;
}

.cartList::-webkit-scrollbar {
    width: 6px;
}

.cartList::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
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

.count button:hover {
    background-color: #cbd5e1;
}

.remove i {
    font-size: 20px;
    color: #888;
    cursor: pointer;
    transition: color 0.2s;
}

.remove i:hover {
    color: #ef4444;
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

.buy:hover {
    background-color: #2563eb;
}

.delete {
    background-color: #e2e8f0;
    color: #1e293b;
}

.delete:hover {
    background-color: #cbd5e1;
}

.Products-Total {
    font-size: 18px;
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
</style>
