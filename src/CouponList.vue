<script setup>
import { ref, onMounted } from 'vue';
import MCP from './components/Mobile/Mcoupon.vue'
import CP from './components/Desktop/coupon.vue'

const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})
</script>

<template>
    <component :is="isMobile ? MCP : CP" />
</template>
