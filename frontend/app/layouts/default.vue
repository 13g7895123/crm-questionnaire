<template>
  <div class="min-h-screen bg-gray-50">
    <div v-if="isMounted && isAuthenticated">
      <Navbar />
      <Breadcrumb />
    </div><main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import Navbar from '~/components/common/Navbar.vue'
import Breadcrumb from '~/components/common/Breadcrumb.vue'



const authStore = useAuthStore()
const isMounted = ref(false)

const isAuthenticated = computed(() => authStore.isAuthenticated)

onMounted(() => {
  isMounted.value = true
  // Restore auth state on mount (for user object)
  authStore.restoreAuth()
})
</script>
