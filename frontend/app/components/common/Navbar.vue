<template>
  <nav class="bg-white shadow w-full">
    <div class="w-full px-6 py-4">
      <div class="flex justify-between items-center">
        <!-- Left: Logo/App Name -->
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">C</div>
          <div>
            <div class="text-xl font-bold text-gray-900">會員中心</div>
            <div class="text-xs text-gray-500">Member Center</div>
          </div>
        </div>

        <!-- Center: Navigation Links (Empty for now as requested) -->
        <div class="flex-1"></div>

        <!-- Right: User Info & Actions -->
        <div class="flex items-center gap-6">
          <!-- Notification -->
          <button class="text-gray-600 hover:text-gray-900">
            <UIcon name="i-heroicons-bell" class="w-6 h-6" />
          </button>

          <!-- User Profile -->
          <div class="flex items-center gap-2 cursor-pointer">
            <UAvatar
              :alt="user?.username || 'User'"
              size="sm"
              class="bg-gray-200 text-gray-600"
            />
            <div class="flex items-center gap-1 text-sm font-medium text-gray-700">
              {{ user?.username || 'User' }}
              <UIcon name="i-heroicons-chevron-down" class="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useAuthStore } from '~/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const { user } = storeToRefs(authStore)

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>
