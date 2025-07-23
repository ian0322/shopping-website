<script setup>
import { ref, onMounted } from 'vue';
import MFavorite from './components/Mobile/Mfavorite.vue'
const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})
import Favorite from './components/Desktop/favorite.vue'
</script>

<template>
    <component :is="isMobile ? MFavorite : Favorite" />
</template>
