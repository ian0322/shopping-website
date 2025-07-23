<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2'
import { onMounted } from 'vue';
const filterStore = useFilterStore();
//const loading = ref(true)
//const error = ref('')
const {
  coupons,
} = storeToRefs(filterStore);
onMounted(async () => {
  filterStore.loadSelectedCoupon()

})
</script>

<template>
  <div>
    <!--<p v-if="loading">載入中...</p>-->
    <!--<p v-if="error" style="color: red;">{{ error }}</p>-->
    <div v-if="coupons.length === 0">目前沒有優惠券</div>
    <transition-group v-else name="fade-slide" tag="div" appear>
      <div v-for="coupon in coupons" :key="coupon.user_coupon_id" class="coupon-card">
        <img :src="coupon.img_url" alt="coupon" class="coupon-img" />
        <div class="info">
          <p><strong>代碼：</strong>{{ coupon.code }}</p>
          <p><strong>描述：</strong>{{ coupon.description }}</p>
          <p><strong>類型：</strong>{{ coupon.type === 'percent' ? coupon.value + '%' : '$' + coupon.value }}</p>
          <p><strong>有效期限：</strong><br>
            <span v-if="coupon.expire_date">{{ coupon.expire_date }}</span>
            <span v-else>無使用期限</span>
          </p>
          <p>
            {{ coupon.is_used ? '✅ 已使用' : '❌ 未使用' }}
          </p>
          <div class="using">
            <button @click="filterStore.selectCoupon(coupon)"
              :class="{ active: filterStore.selectedCoupon?.user_coupon_id === coupon.user_coupon_id }">
              {{ filterStore.selectedCoupon?.user_coupon_id === coupon.user_coupon_id ? '取消' : '使用' }}
            </button>
          </div>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.coupon-card {
  display: flex;
  flex-direction: column;
  /* 手機版直排 */
  border: 1px solid #ccc;
  border-radius: 8px;
  padding: 12px;
  margin: 12px 0;
  background: #fff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.coupon-img {
  width: 100%;
  max-height: 150px;
  object-fit: contain;
  border-radius: 4px;
  margin-bottom: 10px;
}

.info {
  font-size: 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  word-wrap: break-word;
}

.info p {
  margin: 2px 0;
}

.using {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}

.using button {
  border-radius: 5px;
  cursor: pointer;
  padding: 6px 12px;
  border: 2px solid black;
  background-color: transparent;
  transition: all 0.3s ease;
  font-size: 14px;
}

.using button.active {
  background: #fc6a6a;
  color: white;
  font-weight: bold;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

/* 桌面版排版：橫排圖片＋文字 */
@media (min-width: 768px) {
  .coupon-card {
    flex-direction: row;
    align-items: center;
  }

  .coupon-img {
    width: 180px;
    height: 100%;
    margin-right: 16px;
    margin-bottom: 0;
  }

  .info {
    flex: 1;
  }

  .using {
    margin-top: 0;
  }
}

/* 動畫效果 */
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
</style>
