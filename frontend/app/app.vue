<template>
  <div>
    <NuxtRouteAnnouncer />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  authStore.restoreAuth()
})
</script>

<style>
/* SweetAlert2 寬版樣式 - 用於顯示長內容 */
.swal-wide {
  width: 600px !important;
  max-width: 90% !important;
}

.swal-wide .swal2-html-container {
  text-align: left !important;
  line-height: 1.6;
  max-height: 400px;
  overflow-y: auto;
}
</style>
