<script setup>
import { ref } from 'vue'
import { useFilterStore } from '@/stores/filterStore2'
import html2canvas from 'html2canvas'

const filterStore = useFilterStore()

const couponRef = ref(null)
const hasExpire = ref(false)

const coupon = ref({
  code: '',
  description: '',
  type: 'percent', // 或 'fixed'
  value: 0,
  min_purchase: 0,
  max_discount: null,
  total_quantity: null,
  is_public: true,
  start_date: '',
  expire_date: null,
})

const message = ref('')
const submit = async () => {
  if (!couponRef.value) return
  if (!hasExpire.value) {
    coupon.value.expire_date = null
  }
  await document.fonts.ready

  const canvas = await html2canvas(couponRef.value, {
    useCORS: true,
    backgroundColor: '#ffffff',
    scale: 2
  })

  const base64Img = canvas.toDataURL('image/png')
  const base64Data = base64Img.replace(/^data:image\/png;base64,/, '')

  try {
    const response = await fetch(`/coupons.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        ...coupon.value,
        image: base64Data
      })
    })

    const result = await response.json()
    message.value = response.ok ? `✅ ${result.message}` : `❌ ${result.message}`
  } catch (error) {
    console.log(error)
    message.value = '❌ 無法連接伺服器'
  }
}

</script>

<template>
  <div style="display: flex;
  flex-direction: column;margin-left: 20px;">
    <h2>新增優惠券</h2>
    <div class="coupon" ref="couponRef">
      <img src="/img/coupon.png" alt="Coupon Background" class="bg" />
      <div class="overlay">
        <div class="one">
          <div class="code">
            <span v-if="!coupon.code">優惠碼</span>
            <span v-else>{{ coupon.code }}</span>
          </div>
          <div class="expire">
            <span>UNTIL:
              <span v-if="!hasExpire">無限制</span>
              <span v-else>{{ coupon.expire_date }}</span>
            </span>
          </div>
        </div>
        <div class="two">
          <div class="type">
            <span v-if="!coupon.type || coupon.value == 0">類型</span>
            <span v-if="coupon.type == 'percent' && coupon.value">{{ coupon.value }}%</span>
            <span v-if="coupon.type == 'fixed' && coupon.value">{{ coupon.value }}$</span>
          </div>
        </div>
      </div>
    </div>
    <div>
      <label>代碼：<input v-model="coupon.code" @input="coupon.code = coupon.code.toUpperCase()" />
        <label>類型：
          <select v-model="coupon.type">
            <option value="percent">百分比</option>
            <option value="fixed">固定金額</option>
          </select>
        </label>
        <label class="discount">折扣值：<input type="number" v-model.number="coupon.value" /></label>
      </label>
      <div>
        <label>描述：<input v-model="coupon.description" /></label>
        <label>最低消費：<input type="number" v-model.number="coupon.min_purchase" /></label>
        <label>最大折扣：<input type="number" v-model.number="coupon.max_discount" /></label>
      </div>
      <div>
        <label>開始時間：<input type="date" v-model="coupon.start_date" /></label>
        <label v-if="hasExpire">到期時間：<input type="date" v-model="coupon.expire_date" /></label>
        <label>有無使用期限：<input type="checkbox" v-model="hasExpire"></label>
      </div>
    </div>
    <label>總數量：<input type="number" v-model.number="coupon.total_quantity" /></label>
    <label>是否公開：
      <input type="checkbox" v-model="coupon.is_public" />
    </label>

    <button @click="submit" style="width: 50px;cursor: pointer;">送出</button>
    <p>{{ message }}</p>
  </div>
</template>

<style scoped>
input,
select {
  margin: 4px;
}

.coupon {
  margin: 20px 20px;
  position: relative;
  width: 467px;
  height: 170px;
  border: 1px solid #ccc;
  font-family: sans-serif;
}

.bg {
  position: absolute;
  height: 100%;
  object-fit: contain;
  z-index: 1;
}

.overlay {
  position: absolute;
  margin-top: 5.5px;
  margin-left: 24px;
  height: 160px;
  width: 416px;
  z-index: 2;
  display: flex;
}

.one {
  width: 260px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  margin-bottom: 4px;
}

.code {
  position: relative;
  border-radius: 8px;
  left: 43px;
  width: 192px;
  height: 22px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  font-weight: bolder;
  color: rgb(99, 84, 84);
}

.code span {
  position: relative;
}

.expire {
  font-size: 10px;
  position: relative;
  border-radius: 8px;
  left: 43px;
  width: 192px;
  height: 22px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  font-weight: bolder;
  color: #ffe3b8;
}

.two {
  width: 157px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.type {
  font-size: 40px;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  font-weight: bolder;
  color: rgb(88, 88, 88);
  width: 104px;
  height: 104px;
  border-radius: 50%;
  margin-bottom: 2.5px;
  margin-right: 1.5px;
  display: flex;
  justify-content: center;
  align-items: center;
}

@font-face {
  font-family: 'MyFont';
  src: url('/fonts/HanWangWCL07.woff2') format('woff2');
}
</style>
