<script setup>
import { ref, onMounted } from 'vue'
import MI from './components/Mobile/Mintro.vue'
import Introduction from './components/Desktop/introduction.vue'

const isMobile = ref(false)

onMounted(() => {
    isMobile.value = window.innerWidth <= 768
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth <= 768
    })
})
</script>

<template>
    <component :is="isMobile ? MI : Introduction" />
</template>
