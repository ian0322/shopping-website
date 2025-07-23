<script setup>
import { onMounted, onBeforeUnmount, ref, computed, nextTick, watch } from 'vue';
import { useFilterStore } from '@/stores/filterStore2';
import { storeToRefs } from 'pinia';

const props = defineProps({
  p: Object,
  openSelectId: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['updateOpenSelectId']);

const filterStore = useFilterStore();
const { userId, guestId, Usershopcart, Guestshopcart } = storeToRefs(filterStore);

// refs
const dropdownMenuRef = ref(null);
const sizeButtonRef = ref(null);
const cartItemRef = ref(null);

const selectedSize = ref(props.p.size);

const itemKey = computed(() => `${props.p.id}-${props.p.size}`);
const showSelect = computed(() => props.openSelectId === itemKey.value);

const openSelect = (e) => {
  e.stopPropagation();
  if (props.openSelectId === itemKey.value) {
    emit('updateOpenSelectId', null);
  } else {
    emit('updateOpenSelectId', itemKey.value);
  }
};

const dropdownStyle = ref({});
const updateDropdownPosition = () => {
  if (!sizeButtonRef.value) return;
  const rect = sizeButtonRef.value.getBoundingClientRect();
  dropdownStyle.value = {
    position: 'absolute',
    top: `${rect.bottom + window.scrollY}px`,
    left: `${rect.left + window.scrollX}px`,
    zIndex: 9999,
    backgroundColor: 'white',
  };
};

watch(showSelect, (val) => {
  if (val) {
    nextTick(updateDropdownPosition);
  }
});

function parseSizeToStock(str) {
  const obj = {};
  if (!str) return obj;
  str.split(',').forEach(pair => {
    const [size, stock] = pair.split(':');
    obj[size] = Number(stock);
  });
  return obj;
}

function selectSize(size) {
  if (parseSizeToStock(props.p.sizeRaw)[size] === 0) return;

  const oldSize = props.p.size;
  const newSize = size;

  const cartStore = computed(() => userId.value ? Usershopcart : Guestshopcart);
  const cartKey = computed(() => userId.value || guestId.value);

  const success = filterStore.updateCartSize(
    cartKey.value,
    props.p,
    oldSize,
    newSize,
    cartStore.value
  );
  if (success) {
    selectedSize.value = newSize;
    props.p.size = newSize;
    filterStore.syncCart();
    emit('updateOpenSelectId', null); // 關閉 dropdown
  }
}

const handleClickOutside = (e) => {
  const outsideDropdown = dropdownMenuRef.value && !dropdownMenuRef.value.contains(e.target);
  const outsideCartItem = cartItemRef.value && !cartItemRef.value.contains(e.target);
  if (outsideDropdown && outsideCartItem) {
    emit('updateOpenSelectId', null);
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div ref="cartItemRef">
    <div class="productInfo">
      <img :src="p.file_path" alt="product image" />
    </div>
    <div class="itemInfo">
      <div class="name">{{ p.name }}
        <div class="quantity">x{{ p.quantity }}</div>
      </div>

      <div class="size-wrapper">
        <div class="size" :class="{ clicked: showSelect }" @click="openSelect" ref="sizeButtonRef">
          尺寸: {{ selectedSize }}
          <span class="arrow">▼</span>
        </div>
        <div class="price">
          <p>${{ p.price * p.quantity }}</p>
        </div>
      </div>
      <span v-if="p.outOfStock" class="text-red-500 font-bold ml-2">（已無庫存）</span>
      <teleport to="body">
        <ul v-if="showSelect" class="dropdown-menu" :style="dropdownStyle" ref="dropdownMenuRef">
          <li v-for="(stock, size) in parseSizeToStock(p.sizeRaw)" :key="size"
            :class="{ disabled: stock === 0, selected: selectedSize === size }" @click.stop="selectSize(size)">
            {{ size }} <span v-if="stock === 0">(缺貨)</span>
          </li>
        </ul>
      </teleport>
    </div>
  </div>
</template>


<style scoped>
.cartitem {
  display: flex;
  gap: 16px;
  align-items: center;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.size-wrapper {
  position: relative;
  font-size: 12px;
  width: 70px;
  justify-items: flex-start;
  display: inline-block;
}

.size {
  position: relative;
  width: 100%;
  cursor: pointer;
  padding: 2px 4px;
  border: 1px solid #ccc;
  display: inline-block;
  background-color: white;
}

.size.clicked {
  border-color: #3b82f6;
  background-color: #e0f2fe;
}

.dropdown-menu {
  margin: 0;
  border: 1px solid #ccc;
  border-radius: 4px;
  max-height: 180px;
  overflow-y: auto;
  background-color: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  list-style: none;
  padding: 0;
  width: 70px;
}

.dropdown-menu li {
  padding: 2px 4px;
  cursor: pointer;
}

.dropdown-menu li.disabled {
  cursor: not-allowed;
  color: #999;
}

.dropdown-menu li.selected {
  background-color: #3b82f6;
  color: white;
}

.dropdown-menu li:hover:not(.disabled) {
  background-color: #bfdbfe;
}

.productInfo img {
  width: 70px;
  height: 70px;
  object-fit: contain;
  border-radius: 6px;
  background-color: #f9f9f9;
}


.itemInfo {
  width: 80px;
  display: flex;
  justify-content: flex-start;
  align-items: start;
  flex-direction: column;
  gap: 5px;
}

.itemInfo .name {
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: bold;
  font-size: 16px;
}

.itemInfo .name .quantity {
  padding-left: 3px;
  letter-spacing: 1px;
  color: gray;
  font-size: 14px;
}
</style>
