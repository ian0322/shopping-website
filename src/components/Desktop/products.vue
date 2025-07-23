<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore'
import { onMounted, onBeforeUnmount, ref, watch, nextTick, computed } from 'vue';
import { useRoute } from 'vue-router';
const route = useRoute();

const filterStore = useFilterStore();
const swiperInstance = ref(null);
const currentProduct = ref(null);
const {
  animatingId,
  favoriteList,
  showModal,
  imgWidth, //--
  sortIcon, //--
  sortValue, //--
  currentPage, //--
  itemsPerPage, //--
  paginatedImages, //--
  allTags, //--
  selectedMainTags, //--
  selectedSubTags, //--
  totalPages, //--
  filteredProducts,
} = storeToRefs(filterStore);
const {
  addToFavorite,
  addToCart,
  changeLayout,
  toggleSort,
  toggleSubTag, //++
} = filterStore;
const viewDetails = (product) => {
  currentProduct.value = product; // 設定目前點擊的商品
  showModal.value = true;
  // 或者你可以使用 router.push(`/product/${product.id}`)
};
const closeShowmodal = () => {
  showModal.value = false;
}

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

onMounted(async () => {

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
});
</script>

<template>
  <teleport to="body">
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <i id="close" class="fa fa-times fa-lg" aria-hidden="true" @click="closeShowmodal"></i>
        <div class="product_info">
          <div class="block_container">
            <div class="block">左側</div>
            <div class="block">右側</div>
            <div class="block">背面</div>
            <div class="block">正面</div>
          </div>
          <img :src="currentProduct?.file_path" alt="product image" />
        </div>
        <div class="name">{{ currentProduct.name }}</div>
        <div class="size"><!--在upload設定有哪些尺寸跟數量-->
          <button>S</button>
          <button>M</button>
          <button>L</button>
          <button>XL</button>
          <button>XXL</button>
          <p><i v-if="filterStore.userId" id="love" :class="[
            favoriteList.some(fav => fav.id == currentProduct.id) ? 'fa fa-heart loved' : 'fa fa-heart-o',
            animatingId === currentProduct.id ? 'fa-beat fa-solid' : ''
          ]" @click="addToFavorite(currentProduct)">
            </i>
          </p>
        </div>
        <div class="details">價格：${{ currentProduct.price }}</div>
        <div class="details">產品說明：{{ sortedTags }}</div>
        <button class="addshopcart" @click="addToCart(currentProduct)">加入購物車</button>
      </div>

    </div>
  </teleport>
  <div class="body_container">
    <div class="swiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide"><img src="/img/test.jpg" /></div>
        <div class="swiper-slide"><img src="/img/test2.jpg" /></div>
        <div class="swiper-slide"><img src="/img/test3.jpg" /></div>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>
    <div class="path">
      <div class="searchfilter">搜尋條件：{{ filterStore.searchText }}</div>
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
    <div class="items_container">
      <div class="filter-container"><!--控制左邊button篩選-->
        <div v-for="tag in allTags.subtags" :key="tag" class="tagdiv">
          <button @click="toggleSubTag(tag)" :class="{ active: selectedSubTags.includes(tag) }" class="tag-button">
            <span class="text">{{ tag }}</span>
          </button>
        </div>
        <div v-for="tag in filteredProducts.matchedTags" :key="tag" class="tagdiv">
          <input type="checkbox" :id="tag" :value="tag" v-model="selectedMainTags" />
          <label :for="tag">{{ tag }}</label>
        </div>
      </div>

      <div class="my_product" :class="itemsPerPage === 8 ? 'align-start' : 'align-center'">
        <div class="product-container">
          <div v-for="p in paginatedImages" :key="p.id" class="product">
            <div>
              <div class="image-container" :style="{ width: imgWidth + 'px', height: imgWidth + 'px' }">
                <img :src="p.file_path" alt="product image" />
                <div class="overlay">
                  <button @click="viewDetails(p)">查看更多</button>
                  <button @click="addToCart(p)">加入購物車</button>
                </div>
              </div>
              <div>{{ p.name }} - ${{ p.price }}</div>
              <i v-if="filterStore.userId" id="love" :class="[
                favoriteList.some(fav => fav.id == p.id) ? 'fa fa-heart loved' : 'fa fa-heart-o',
                animatingId === p.id ? 'fa-beat fa-solid' : ''
              ]" @click="addToFavorite(p)">
              </i>
            </div>
          </div>
          <div v-if="paginatedImages.length == 0" style="font-size: 50px;letter-spacing: 2px;">查無商品...</div>

          <div v-if="paginatedImages.length < 8" v-for="n in ((8 - paginatedImages.length) % 4)" :key="'empty-' + n">
            <div class="empty-item" :style="{ width: imgWidth + 'px' }"></div>
          </div>
        </div>
        <!-- 分頁控制 -->
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

      <!-- 補空格子（讓總數達到 8） -->
    </div>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
  text-decoration: none;
}

.body_container {
  display: flex;
  flex-direction: column;
  position: relative;
  width: 100%;
}

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

.items_container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  /* 垂直置中 */
  align-items: flex-start;
  /* 水平置中 */
  width: 100%;
  position: relative;
  /* 使按鈕能夠絕對定位 */
}

.button-class {
  width: 100%;
  height: 40px;
  margin-right: 50px;
  /* 從容器右邊偏移 10px */
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 20px;
}

.filter-container {
  position: relative;
  display: flex;
  padding-top: 20px;
  gap: 20px;
  width: 30%;
  margin-left: 20px;
  font-size: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-wrap: wrap;
  border: 2px solid #555;
  height: 100vh;
  /* 確保容器有足夠的高度 */
}

.filter-container .tagdiv {
  position: relative;
  display: flex;
  justify-content: flex-end;
}

.filter-container .tagdiv button {
  display: inline-block;
  /* 讓寬度符合文字 */
  cursor: pointer;
  position: relative;
  font-size: 20px;
  width: 100%;
  height: 50px;
  text-align: right;
  background-color: transparent;
}

.tag-button {
  background: none;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 16px;
  color: #333;
}

.text {
  position: relative;
  display: inline-block;
  /* 讓寬度剛好包文字 */
}

.text::after {
  content: '';
  position: absolute;
  bottom: -2px;
  /* 線在文字正下方，調整數值可讓線離文字近一點 */
  left: 0;
  width: 100%;
  height: 2px;
  background-color: black;
  transform-origin: center;
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.tag-button.active .text::after {
  transform: scaleX(1);
}


.filter-container .tagdiv label {
  padding-right: 5px;
  font-size: 21px;
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

.page {
  position: relative;
  display: flex;
  cursor: pointer;
  justify-content: center;
  /* 水平置中 */
  align-items: center;
  /* 垂直置中 */
  width: 100%;
  /* 讓頁面佔滿父容器 */
  margin-top: 20px;
  /* 確保分頁按鈕與商品有間距 */
  font-size: 20px;
  z-index: 10;
  /* 確保分頁按鈕層級在最上 */
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

.my_product {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  /* 圖片之間的間距 */
  min-height: 500px;
  height: auto;
  width: 100%;
  /* 可以控制 .my_product 的寬度，讓它顯得居中 */
}

.my_product img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  transition: transform 0.3s ease;
  margin-top: 20px;
  border: 1px solid rgb(0, 0, 0, 0.1);
  border-radius: 5px;
}

/* 8 個商品時，每排顯示 4 個 */

.tagdiv {
  display: flex;
  border-bottom: 1px solid #ccc;
  /* 分隔線 */
  width: 80%;
}

.cart-count {
  position: absolute;
  top: -5px;
  right: -10px;
  background: red;
  color: white;
  font-size: 12px;
  border-radius: 50%;
  padding: 2px 6px;
}

.product {
  position: relative;
  display: flex;
  overflow: hidden;
}

.product-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px
}

.image-container {
  position: relative;
}

.overlay {
  border-radius: 5px;
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.product:hover .overlay {
  opacity: 1;
}

.overlay button {
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
  width: 100%;
  height: 100%;
}

.modal::-webkit-scrollbar {
  display: none;
}

.modal {
  overflow-y: auto;
  position: absolute;
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  width: 30%;
  height: 90%;
}

.modal #close {
  align-self: flex-end;
  cursor: pointer;
  margin-bottom: 10px;
}

.modal .product_info {
  border: 1px solid green;
  display: flex;
  justify-content: flex-end;
  width: 100%;
  height: auto;
}

.modal img {
  width: 70%;
  height: max-content;
}

.modal .block_container {
  border: 1px solid blue;
  gap: 10px;
  height: auto;
  display: flex;
  flex-direction: column;
}

.modal .block {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: 10px;
  border: 1px solid red;
  width: 50px;
  height: 50px;
}

.modal .size {
  width: 100%;
  height: 10%;
  border: 1px solid pink;
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.modal .name {
  border: 1px solid brown;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 5%;
  font-size: 24px;
}

.size button {
  font-size: 15px;
  cursor: pointer;
  width: 60px;
  text-align: center;
  min-height: 40%;
  border: 1px solid rgb(85, 85, 85, 0.5);
}

.modal .details {
  letter-spacing: 1px;
}

.modal .addshopcart {
  background-color: white;
  border: 1px solid black;
  font-weight: bold;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 20px;
  white-space: nowrap;
  display: flex;
  justify-content: center;
  align-items: center;
  width: fit-content;
  height: 5%;
  font-size: 18px;
  padding: 8px 12px;
  align-self: center;
}

#love {
  cursor: pointer;
  margin-left: 10px;
}

.loved {
  -webkit-text-stroke-width: 1.5px;
  -webkit-text-stroke-color: black;
  color: red;
}

.searchfilter {
  margin-top: 10px;
  margin-left: 10px;
}
</style>
