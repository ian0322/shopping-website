<script setup>
import { ref, onMounted } from 'vue'

import PT from './components/Desktop/producttest.vue'
import MPT from './components/Mobile/PTMobile.vue'
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
    <component :is="isMobile ? MPT : PT" />
</template>
