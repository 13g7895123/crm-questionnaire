<template>
  <nav class="bg-white shadow">
    <div class="container mx-auto px-4 py-4">
      <div class="flex justify-between items-center">
        <div class="text-xl font-bold">{{ $t('common.appName') }}</div>
        <div class="flex gap-4">
          <LanguageSwitcher />
          <button @click="handleLogout" class="text-red-600">{{ $t('auth.logout') }}</button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useRouter } from 'vue-router'
import LanguageSwitcher from './LanguageSwitcher.vue'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>
