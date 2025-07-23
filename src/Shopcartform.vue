<script setup>
import { ref, onMounted } from 'vue';
import NT from './navt.vue'
import Shopcartform from './components/Desktop/shopcartform.vue';
import MS from './components/Mobile/Msubmitorder.vue'
const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})

//import Products from './components/products.vue'
</script>

<template>
    <NT />
    <component :is="isMobile ? MS : Shopcartform" />
    <router-view :key="$route.fullPath"></router-view>
    <!--<Products/>-->
</template>