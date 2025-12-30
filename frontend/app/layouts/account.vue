<template>
  <div class="flex h-[calc(100vh-64px)] overflow-hidden bg-white">
    <!-- Sidebar -->
    <aside v-if="isAdmin" class="w-64 flex-shrink-0 border-r border-gray-200 overflow-y-auto bg-gray-50/50">
      <div class="p-4">
        <AccountSidebar />
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0 overflow-y-auto">
      <div class="p-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import AccountSidebar from '~/components/account/AccountSidebar.vue'

const authStore = useAuthStore()
const isAdmin = computed(() => {
  const role = authStore.user?.role
  return role === 'HOST' || role === 'ADMIN'
})
</script>
