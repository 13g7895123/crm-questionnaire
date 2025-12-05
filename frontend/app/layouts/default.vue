<template>
  <div class="min-h-screen bg-gray-50">
    <ClientOnly>
      <Navbar v-if="isAuthenticated" />
    </ClientOnly>
    <Breadcrumb v-if="isAuthenticated" />
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import Navbar from '~/components/common/Navbar.vue'
import Breadcrumb from '~/components/common/Breadcrumb.vue'

definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()

const isAuthenticated = computed(() => authStore.isAuthenticated)

onMounted(() => {
  // Restore auth state on mount
  authStore.restoreAuth()
})
</script>
