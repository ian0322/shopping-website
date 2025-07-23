<template>
<router-view :key="$route.fullPath" />
</template>

<script setup>
import { onMounted } from 'vue'
import { useFilterStore } from './stores/filterStore2'

const filterStore = useFilterStore()

onMounted(() => {
  filterStore.loadProducts()
})
</script>


<style>
html,
body,
#app {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

.router-link-active,
.router-link-exact-active {
  color: inherit !important;
  background: none !important;
  text-decoration: none !important;
}
</style>
