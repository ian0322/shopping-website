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
  const dropdownMenuRef = ref(null);      // 給 teleport dropdown
  const sizeButtonRef = ref(null);        // 尺寸按鈕位置
  const cartItemRef = ref(null);          // 整個商品項目

  const selectedSize = ref(props.p.size);

  // 顯示 dropdown 的條件
  const itemKey = computed(() => `${props.p.id}-${props.p.size}`);
  const showSelect = computed(() => props.openSelectId === itemKey.value);

  // 點擊開啟 dropdown
  const openSelect = (e) => {
    e.stopPropagation();
    if (props.openSelectId === itemKey.value) {
      emit('updateOpenSelectId', null);
    } else {
      emit('updateOpenSelectId', itemKey.value);
    }
  };

  // dropdown 定位樣式
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

  // 監聽 dropdown 開啟時重新計算位置
  watch(showSelect, (val) => {
    if (val) {
      nextTick(updateDropdownPosition);
    }
  });

  // 尺寸列表解析
  function parseSizeToStock(str) {
    const obj = {};
    if (!str) return obj;
    str.split(',').forEach(pair => {
      const [size, stock] = pair.split(':');
      obj[size] = Number(stock);
    });
    return obj;
  }

  // 選擇尺寸
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

  // 點擊外部時關閉 dropdown
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
    <div :class="$attrs.class" ref="cartItemRef">
      <div class="productInfo">
        <img :src="p.file_path" alt="product image" />
      </div>
      <div class="itemInfo">
        <div class="name">{{ p.name }}<div class="quantity">x{{ p.quantity }}</div>
        </div>
        <div class="size-wrapper">
          <div class="size" :class="{ clicked: showSelect }" @click="openSelect" ref="sizeButtonRef">
            尺寸: {{ selectedSize }}
            <span class="arrow">▼</span>
          </div>
        </div>
        <span v-if="p.outOfStock" class="text-red-500 font-bold ml-2">（已無庫存）</span>

        <!-- Dropdown menu 渲染到 body -->
        <teleport to="body">
          <ul v-if="showSelect" class="dropdown-menu" :style="dropdownStyle" ref="dropdownMenuRef">
            <li v-for="(stock, size) in parseSizeToStock(p.sizeRaw)" :key="size"
              :class="{ disabled: stock === 0, selected: selectedSize === size }" @click.stop="selectSize(size)">
              {{ size }} <span v-if="stock === 0">(缺貨)</span>
            </li>
          </ul>
        </teleport>
      </div>

      <div class="price">
        <p>${{ p.price * p.quantity }}</p>
      </div>
    </div>
  </template>

<style scoped>
.cartitem {
  display: flex;
  gap: 16px;
  align-items: center;
  padding: 12px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.productInfo img {
  width: 100px;
  height: 100px;
  object-fit: contain;
  border-radius: 6px;
  background-color: #f9f9f9;
  margin-left: 8px;
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
}

.size {
  margin-top: 4px;
  padding: 6px 10px;
  background-color: #f2f2f2;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid #ccc;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.size.clicked {
  border-color: #3b82f6;
  background-color: #e0f2fe;
}

.dropdown-menu {
  position: absolute;
  z-index: 999;
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  min-width: 120px;
  overflow: hidden;
  margin-left: 0;
  padding-left: 0;
  margin-top: 0;
}

.dropdown-menu li {
  padding: 8px 12px;
  cursor: pointer;
  transition: background 0.2s;
  list-style: none;
  /* 去除圓點 */
  margin-left: 0;
  /* 清除左邊外距 */
  padding-left: 5px;
  /* 清除左邊內距 */

}

.dropdown-menu li:hover:not(.disabled) {
  background-color: #eef;
}

.dropdown-menu li.selected {
  background-color: #3b82f6;
}

.dropdown-menu li.disabled {
  color: #aaa;
  cursor: not-allowed;
}

.price {
  margin-left: auto;
  margin-right: 8px;
}
</style>
