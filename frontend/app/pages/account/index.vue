<template>
  <div></div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

definePageMeta({
  middleware: 'auth'
})

onMounted(() => {
  navigateTo('/account/profile')
})
</script>
