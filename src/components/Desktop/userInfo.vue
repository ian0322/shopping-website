<script setup>
import { shallowRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import FavoriteList from '../../Favorite.vue'
import CouponList from '../../CouponList.vue'
import UserProfile from '../../UserProfile.vue'
import HistoryOrder from '../../HistoryOrder.vue'
const currentComponent = shallowRef(null)
const route = useRoute()
const router = useRouter()

const setComponentByQuery = (tab) => {
    router.replace({ name: 'UserInfo', query: { tab } })
    switch (tab) {
        case 'favorite':
            currentComponent.value = FavoriteList
            break
        case 'coupon':
            currentComponent.value = CouponList
            break
        case 'userinfo':
            currentComponent.value = UserProfile
            break
        case 'history':
            currentComponent.value = HistoryOrder
            break
        default:
            currentComponent.value = null
    }
}

watch(
    () => route.query.tab,
    (newTab) => {
        setComponentByQuery(newTab)
    },
    { immediate: true }
)
</script>

<template>
    <div class="container">
        <div class="info-container">
            <div class="infolist">
                <div class="userinfo" :class="{ active: route.query.tab === 'userinfo' }"
                    @click="setComponentByQuery('userinfo')">
                    <span>個人資訊</span>
                </div>
                <div class="coupon" :class="{ active: route.query.tab === 'coupon' }"
                    @click="setComponentByQuery('coupon')">
                    <span>優惠券</span>
                </div>
                <div class="favoritelist" :class="{ active: route.query.tab === 'favorite' }"
                    @click="setComponentByQuery('favorite')">
                    <span>收藏清單</span>
                </div>
                <div class="historylist" :class="{ active: route.query.tab === 'history' }"
                    @click="setComponentByQuery('history')">
                    <span>歷史訂單</span>
                </div>
            </div>
            <div class="infoarea">
                <component :is="currentComponent" class="infoContent" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 40px 20px;
}

.info-container {
    margin-top: 100px;
    width: 100%;
    max-width: 800px;
    height: 80vh;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.infolist {
    display: flex;
    height: 60px;
    background-color: #f7f7f7;
    border-bottom: 1px solid #e0e0e0;
}

.infolist div {
    flex: 1;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: 500;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
    position: relative;
    user-select: none;
}

.infolist div:hover {
    background-color: #eaeaea;
}

.infolist div.active {
    font-weight: bold;
    color: #333;
    background-color: #ffffff;
}

.infolist div span {
    position: relative;
    padding: 6px 0;
}

.infolist div span::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -4px;
    width: 100%;
    height: 2px;
    background-color: #333;
    transform: scaleX(0);
    transform-origin: center;
    transition: transform 0.3s ease;
}

.infolist div.active span::after {
    transform: scaleX(1);
}

.infoarea {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background-color: #fff;
    box-sizing: border-box;
}
</style>
