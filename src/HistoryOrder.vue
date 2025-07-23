<script setup>
import { ref, onMounted } from 'vue';
import HistoryOrder from './components/Desktop/historyorder.vue'
import MH from './components/Mobile/Mhistory.vue'
const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})
</script>

<template>
    <component :is="isMobile ? MH : HistoryOrder" />
</template>
