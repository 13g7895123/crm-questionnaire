<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
      <h1 class="text-2xl font-bold mb-6 text-center">{{ $t('auth.login') }}</h1>
      
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('auth.username') }}</label>
          <input
            v-model="form.username"
            type="text"
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('auth.password') }}</label>
          <input
            v-model="form.password"
            type="password"
            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400"
        >
          {{ isLoading ? $t('common.loading') : $t('auth.login') }}
        </button>
      </form>

      <div v-if="error" class="mt-4 p-3 bg-red-100 text-red-700 rounded">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import type { User } from '~/types/index'

definePageMeta({
  layout: 'default',
  middleware: []
})

const router = useRouter()
const authStore = useAuthStore()
const isLoading = ref(false)
const error = ref<string | null>(null)

const form = ref({
  username: '',
  password: ''
})

const handleLogin = async () => {
  try {
    isLoading.value = true
    error.value = null

    // Mock login - in real app, call API
    const mockUser: User = {
      id: 'user-1',
      username: form.value.username,
      email: form.value.username + '@example.com',
      phone: '+886-1234567890',
      departmentId: 'dept-1',
      role: 'HOST',
      organizationId: 'org-1'
    }

    const mockToken = 'mock-jwt-token-' + Date.now()

    authStore.setUser(mockUser)
    authStore.setToken(mockToken)

    // Redirect to home
    await router.push('/')
  } catch (err: any) {
    error.value = err.message || 'Login failed'
  } finally {
    isLoading.value = false
  }
}
</script>
