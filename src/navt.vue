<script setup>
import { ref, onMounted } from 'vue'
import NT from './components/Desktop/navt.vue'
import MNT from './components/Mobile/Mnav.vue'

const isMobile = ref(false)

onMounted(() => {
  isMobile.value = window.innerWidth <= 768
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth <= 768
  })
})
</script>

<template>
  <component :is="isMobile ? MNT : NT" />
</template>