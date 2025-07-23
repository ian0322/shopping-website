<script setup>

import { onMounted, onBeforeUnmount, ref, watch, nextTick, computed, getCurrentInstance } from 'vue';
import { useFilterStore } from '@/stores/filterStore2'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'

const route = useRoute();

const filterStore = useFilterStore()
const swiperInstance = ref(null);
const currentPage = ref(1);
const itemsPerPage = ref(8);
const imgWidth = ref(350);
const currentProduct = ref(null);
const showModal = ref(false)
const sortIcon = ref("fa-sort");
const sortValue = ref("預設");
const sortedProducts = ref([]);
const showToast = ref(false);
const allSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
const sizeStock = ref({});

const filteredProducts = computed(() => filterStore.filteredProducts);

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
  multiSelectableTags,
  tagHierarchy,
  mainCategories,
  toggleTag,
  addToCart,
  addToFavorite,
} = filterStore
const {
  searchText,
  availableCheckboxTags,
  selectedTag,
  expandedTag,
  selectedMultiTag,
  checkboxSelectedTags,
  favoriteList,
  animatingId, otherTags,
} = storeToRefs(filterStore)

const viewDetails = (product) => {
  currentProduct.value = product;
  sizeStock.value = filterStore.parseSizeToStock(product.size); // 轉換尺寸庫存
  // 設定第一個有庫存的尺寸為預設選擇
  const availableSize = allSizes.find(size => sizeStock.value[size] > 0);
  filterStore.selectedSize = availableSize || null;
  showModal.value = true;
};

watch(
  filteredProducts,
  () => {
    currentPage.value = 1;
  },
  { deep: true }
);
function showAddToCartToast() {
  if (filterStore.selectedSize) {
    showToast.value = true;
  }
  setTimeout(() => {
    showToast.value = false;
  }, 2000);
}
function handleAddToCart(product) {
  const success = addToCart(product);
  if (success) {
    showAddToCartToast();
  }
}
const closeShowmodal = () => {
  showModal.value = false;
}
const paginatedImages = computed(() => {
  const list = Array.isArray(sortedProducts.value) ? sortedProducts.value : [];
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return list.slice(start, start + itemsPerPage.value);
});

const totalPages = computed(() => {
  return Math.ceil(
    filteredProducts.value.length / itemsPerPage.value
  );
});

const toggleSort = () => {
  const list = [...sortedProducts.value]; // 不動原始
  if (sortIcon.value === "fa-sort") {
    sortIcon.value = "fa-sort-amount-up";
    sortValue.value = "價格由小到大";
    sortedProducts.value = list.sort((a, b) => a.price - b.price);
  } else if (sortIcon.value === "fa-sort-amount-up") {
    sortIcon.value = "fa-sort-amount-down";
    sortValue.value = "價格由大到小";
    sortedProducts.value = list.sort((a, b) => b.price - a.price);
  } else {
    sortIcon.value = "fa-sort";
    sortValue.value = "預設";
    sortedProducts.value = list.sort((a, b) => a.name.localeCompare(b.name));
  }
  currentPage.value = 1;
};
watch(() => filterStore.filteredProducts, (newVal) => {
  sortedProducts.value = [...newVal]; // 初始複製
}, { immediate: true });
const changeLayout = () => {
  itemsPerPage.value = itemsPerPage.value === 8 ? 4 : 8;
  imgWidth.value = imgWidth.value === 350 ? 500 : 350;
  currentPage.value = 1;
};
const clearword = (word) => {
  if (word === 'search') {
    searchText.value = "";
  }
  else if (word === 'filter') {
    filterStore.clearFilter();
  }
}

onMounted(async () => {
  await filterStore.loadFavorite();
  // 在 DOM 完全渲染後初始化 Swiper
  await nextTick();
  swiperInstance.value = new Swiper(".swiper", {
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });

  // 監聽路由變化
  watch(route, () => {
    // 只有當 swiperInstance 存在時才進行重新初始化
    nextTick(() => {
      if (swiperInstance.value) {
        swiperInstance.value.destroy();
        swiperInstance.value = new Swiper(".swiper", {
          loop: true,
          autoplay: {
            delay: 5000,
            disableOnInteraction: false,
          },
          pagination: {
            el: ".swiper-pagination",
            clickable: true,
          },
          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },
        });
      }
    });
  });
});

onBeforeUnmount(() => {
  // 在組件卸載時銷毀 Swiper 實例，防止內存泄漏
  if (swiperInstance.value) {
    swiperInstance.value.destroy();
  }
})
</script>

<template>
  <teleport to="body">
    <div v-if="showModal" class="modal-overlay" @click.self="closeShowmodal">
      <div v-if="showToast" class="toast">已加入購物車</div>
      <div class="modal">
        <i id="close" class="fa fa-times fa-lg" aria-hidden="true" @click="closeShowmodal"></i>
        <div class="product_info">
          <!--<div class="block_container">
            <div class="block">左側</div>
            <div class="block">右側</div>
            <div class="block">背面</div>
            <div class="block">正面</div>
          </div>-->
          <img :src="currentProduct?.file_path" alt="product image" style="width: 400px;height:400px;object-fit: contain; /* 關鍵設定：保持圖片比例 */
" />
        </div>
        <div class="name">{{ currentProduct.name }}</div>
        <div class="size">
          <div class="size-btn">
            <button v-for="size in allSizes" :key="size" :class="{ selected: filterStore.selectedSize === size }"
              @click="() => { filterStore.selectedSize = size; console.log('selectedSize:', filterStore.selectedSize) }"
              :disabled="!sizeStock[size] || sizeStock[size] <= 0">
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
            ]" @click="addToFavorite(currentProduct)" style="cursor:pointer"></i>
          </div>
        </div>
        <div class="details">價格：${{ currentProduct.price }}</div>
        <div class="details">產品說明：{{ sortedTags }}</div>
        <button class="addshopcart" @click="handleAddToCart(currentProduct)">加入購物車</button>
      </div>
    </div>
  </teleport>
  <!--<NavTop class="fixed-nav" />-->
  <!-- 主要分類按鈕 -->
  <div class="swiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide"><img src="/img/test.jpg" /></div>
      <div class="swiper-slide"><img src="/img/test2.jpg" /></div>
      <div class="swiper-slide"><img src="/img/test3.jpg" /></div>
      <div class="swiper-slide"><img src="/img/test4.jpg" /></div>
    </div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
  </div>
  <div class="path">
    <div class="filter-word">
      <strong>目前篩選：{{ selectedMultiTag?.slice(0, 2) || '' }}&nbsp{{ checkboxSelectedTags.join(', ') }}&nbsp{{
        selectedTag }}</strong>
      <strong> 搜尋：{{ searchText }} </strong>
      <button class="auto-width-btn" @click="clearword('filter')">清除篩選條件</button>
      <button class="auto-width-btn" @click="clearword('search')">清除搜尋文字</button>
    </div>
    <div class="button-class">
      <button class="sort" @click="changeLayout">
        <i class="fa fa-list-ul" aria-hidden="true"></i>
      </button>
      <button class="price">
        <i :class="['fa', sortIcon]" @click="toggleSort">
          <span class="pricesort">{{ sortValue }}</span>
        </i>
      </button>
    </div>
  </div>
  <div class="shop-container">
    <div class="filter">
      <div class="multiselect">
        <button v-for="tag in multiSelectableTags" :key="tag" @click="toggleTag(tag)"
          :class="{ active: selectedMultiTag === tag }">
          <span>{{ tag }}</span>
        </button>
      </div>
      <div class="expand" v-if="selectedTag === null || !otherTags.includes(selectedTag)">
        <div v-for="main in expandedTag ? [expandedTag] : mainCategories" :key="main">
          <button @click="toggleTag(main)" :class="{
            active: expandedTag === main
          }">
            <span>{{ main }}</span>
          </button>
          <div v-if="expandedTag === main" class="subexpand">
            <button v-for="sub in tagHierarchy[main]" :key="sub" @click="toggleTag(sub)" :class="{
              active: selectedTag === sub
            }">
              {{ sub }}
            </button>
          </div>
        </div>
      </div>
      <!-- 其他 tags -->
      <div class="other" v-if="expandedTag === null">
        <button v-for="tag in selectedTag ? [selectedTag] : otherTags" :key="tag" @click="toggleTag(tag)"
          :class="{ active: selectedTag === tag }">
          <span>{{ tag }}</span>
        </button>
      </div>

      <!-- 多選 checkbox 篩選 -->
      <div class="gender">
        <div>性別</div>
        <div class="checkbox">
          <div v-for="tag in availableCheckboxTags" :key="tag">
            <input type="checkbox" :value="tag" v-model="checkboxSelectedTags" />
            {{ tag }}
          </div>
        </div>
      </div>
    </div>
    <!-- 商品列表 -->
    <div class="product-container">
      <div v-if="showToast" class="toast">已加入購物車</div>
      <div class="product">
        <div v-for="product in paginatedImages" :key="product.id"
          :style="{ width: imgWidth + 'px', height: imgWidth + 100 + 'px' }" class="product-card">
          <div class="image-wrapper" :style="{ height: imgWidth + 'px' }">
            <img :src="product.file_path" alt="product image" class="product-img" />
            <div class="overlay" @click="viewDetails(product)">
              <div>查看更多</div>
            </div>
          </div>
          <h4 class="product-name">{{ product.name }}</h4>
          <p class="product-price">{{ product.price }} 元</p>
          <i v-if="filterStore.userId" id="love" :class="[
            favoriteList.some(fav => fav.id == product.id) ? 'fa fa-heart loved' : 'fa fa-heart-o',
            animatingId === product.id ? 'fa-beat fa-solid' : ''
          ]" @click="addToFavorite(product)">
          </i>
        </div>
        <div v-if="paginatedImages.length == 0" style="width: 100%;font-size: 50px;letter-spacing: 2px;
        text-align: center;">查無商品...</div>
        <div v-if="paginatedImages.length < 8" v-for="n in (Math.max(0, 8 - paginatedImages.length) || 0)"
          :key="'empty-' + n">
          <div class="empty-item" :style="{ width: imgWidth + 'px' }"></div>
        </div>
      </div>
      <div class="page">
        <i class="fa fa-angle-double-left" aria-hidden="true" v-if="totalPages > 2" @click="currentPage = 1"></i>
        <i class="fa fa-angle-left" aria-hidden="true" v-if="totalPages > 1"
          @click="currentPage = Math.max(currentPage - 1, 1)"></i>

        <span v-for="page in totalPages" :key="page" :class="{ 'active-page': currentPage === page }"
          @click="currentPage = page">
          {{ page }}
        </span>

        <i class="fa fa-angle-right" aria-hidden="true" v-if="totalPages > 1"
          @click="currentPage = Math.min(currentPage + 1, totalPages)"></i>
        <i class="fa fa-angle-double-right" aria-hidden="true" v-if="totalPages > 2"
          @click="currentPage = totalPages"></i>
      </div>
    </div>

  </div>

</template>
<style scoped>
.swiper {
  position: relative;
  /* 使 swiper 能夠作為參考點 */
  width: 100%;
  height: 500px;
  background-color: #f0f0f0;
  text-align: center;
  margin-top: 84px;
}

.swiper-slide {
  width: fit-content;
  height: 100%;
  aspect-ratio: 1 / 1;
}

.swiper-slide img {
  width: fit-content;
  height: 100%;
  aspect-ratio: 1 / 1;
  background-size: cover;
  background-position: center;
}

.swiper-button-next,
.swiper-button-prev {
  opacity: 0;
  transition: opacity 1s ease;
  color: rgb(151, 151, 151) !important;
  /* 自訂箭頭顏色 */
}

.swiper-pagination {
  opacity: 0;
  transition: opacity 1s ease;
}

.swiper:hover .swiper-button-next,
.swiper:hover .swiper-button-prev,
.swiper:hover .swiper-pagination {
  opacity: 1;
}

.swiper-pagination-bullet-active {
  background-color: gray !important;
}

.shop-container {
  position: relative;
  width: 100%;
  height: auto;
  display: flex;
  padding-bottom: 100px;
  align-items: flex-start;
}

.filter {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 20%;
  border-radius: 12px;
  background-color: #fafafa;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  padding: 20px 0;
}

.filter>div {
  width: 85%;
}

/* 所有按鈕統一設計 */
.filter button {
  position: relative;
  font-size: 18px;
  text-align: end;
  cursor: pointer;
  width: 100%;
  height: 45px;
  margin-bottom: 20px;
  padding-right: 12px;
  border: none;
  border-radius: 8px;
  background-color: #fff;
  transition: all 0.25s ease;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.filter button:hover {
  background-color: #f2f2f2;
  transform: translateY(-2px);
}

/* 文字底線效果 */
.filter button span {
  position: relative;
  display: inline-block;
}

.filter button span::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  height: 2px;
  width: 0%;
  background-color: black;
  transition: width 0.3s ease;
}

.filter button.active span::after {
  width: 100%;
}

/* 主分類區 */
.multiselect {
  margin-top: 30px;
}

/* 子分類縮排效果 */
.subexpand {
  margin-top: 10px;
  padding-left: 12px;
  border-left: 2px solid #ccc;
}

.subexpand button.active {
  font-weight: bold;
}

/* 性別區塊 */
.gender {
  font-size: 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 30px;
  margin-bottom: 30px;
}

.gender>div:first-child {
  font-weight: bold;
  margin-bottom: 8px;
}

.gender .checkbox {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
  text-align: end;
}

.gender .checkbox div {
  padding: 8px 10px;
  border-radius: 6px;
  border-bottom: 1px solid rgba(151, 151, 151, 0.3);
  transition: background-color 0.2s;
}

.gender .checkbox div:hover {
  background-color: #f5f5f5;
}

.gender .checkbox input[type="checkbox"] {
  accent-color: black;
  transform: scale(1.1);
  cursor: pointer;
}


.product-container {
  width: 80%;
  height: 100%;
}



.product {
  height: 100%;
  position: relative;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-evenly;
  /* 改成靠左排列 */
  gap: 16px;
  /* 建議用 gap 控制間距 */
  flex: 1;
  flex-direction: row;
}

.product-card {
  border: 1px solid #ddd;
  padding: 10px;
  border-radius: 6px;
  box-sizing: border-box;
  width: 300px;
  /* or你動態的 imgWidth */
  overflow: hidden;
  word-break: break-word;
  /* 讓文字超長自動換行 */
}

.image-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: 6px;
}

.product-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  /* 關鍵設定：保持圖片比例 */
  display: block;
}

.product-name {
  font-size: 20px;
  margin: 6px 0 0 0;
}

.product-price {
  font-size: 18px;
  color: #555;
  margin: 4px 0 0 0;
}

.page {
  display: flex;
  cursor: pointer;
  justify-content: center;
  /* 水平置中 */
  align-items: center;
  /* 垂直置中 */
  /* 讓頁面佔滿父容器 */
  margin-top: 20px;
  /* 確保分頁按鈕與商品有間距 */
  font-size: 20px;
  right: 35%;
}

.page span,
.page i {
  color: gray;
  background-color: transparent;
  border: none;
  font-size: 20px;
  margin: 5px;
}

.page span.active-page {
  color: black !important;
  font-weight: bold !important;
}

.overlay {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
  /* 防止 hover 造成閃爍 */
}

/* ✅ 只有滑鼠移到單一 image-wrapper 時才顯示 */
.image-wrapper:hover .overlay {
  opacity: 1;
  pointer-events: auto;
  /* 恢復可點擊 */
}

.overlay div {
  width: auto;
  background-color: white;
  border: none;
  padding: 10px 15px;
  margin: 5px;
  cursor: pointer;
  font-weight: bold;
  border-radius: 5px;
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


.button-class {
  width: 100%;
  height: 40px;
  /* 從容器右邊偏移 10px */
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.sort,
.price {
  background-color: transparent;
  cursor: pointer;
  border: none;
  font-size: 25px;
}

.price {
  width: 150px;
  text-align: left;
}

.pricesort {
  margin-left: 5px;
  font-size: 15px;
}

.filter-word {
  font-size: 20px;
  width: 100%;
  display: flex;
  gap: 5px;
  flex-direction: column;
  padding-left: 20px;
  box-sizing: border-box;
  /* 確保 padding 不會讓寬度超出 100% */
}

.filter-word>button {
  width: auto;
  display: inline-flex;
  align-self: flex-start;
  white-space: nowrap;
  border: 1px solid #ccc;
  border-radius: 5px;
  background-color: #f2f2f2;
  cursor: pointer;
  padding: 6px 12px;
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