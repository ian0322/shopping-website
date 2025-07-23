<script setup>
import { ref, onMounted } from 'vue';
import UserInfo from './components/Desktop/userInfo.vue'
import MuserInfo from './components/Mobile/MuserInfo.vue';
import NT from './navt.vue'
const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})
</script>

<template>
    <NT />
  <component :is="isMobile ? MuserInfo : UserInfo" />
</template>
