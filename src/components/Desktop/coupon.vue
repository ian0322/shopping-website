<script setup>
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2'
import { onMounted } from 'vue';
const filterStore = useFilterStore();
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
  flex-direction: row;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.3s ease;
  background-color: #fff;
}

.coupon-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.coupon-img {
  width: 120px;
  height: 120px;
  object-fit: contain;
  margin-right: 20px;
  border-radius: 4px;
  background-color: #f8f8f8;
  border: 1px solid #eee;
}

.info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
  min-width: 0;
  font-size: 14px;
  color: #333;
}

.info p {
  margin: 2px 0;
  line-height: 1.4;
}

.using {
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
}

.using button {
  padding: 8px 16px;
  border-radius: 6px;
  background-color: white;
  border: 2px solid #555;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #333;
}

.using button:hover {
  background-color: #f0f0f0;
}

.using button.active {
  background-color: #fc6a6a;
  border-color: #fc6a6a;
  color: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
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
