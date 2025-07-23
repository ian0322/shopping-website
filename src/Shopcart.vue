<script setup>
import { ref, onMounted } from 'vue';
const isMobile = ref(false)

onMounted(() => {
  isMobile.value = window.innerWidth <= 768
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth <= 768
  })
})
import NT from './navt.vue'
import Cart from './components/Desktop/cart.vue'
import Mcart from './components/Mobile/Mcart.vue';
</script>

<template>
  <NT />
  <component :is="isMobile ? Mcart : Cart" />
  <router-view :key="$route.fullPath"></router-view>
  <!--<Products/>-->
</template>