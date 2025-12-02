<template>
  <div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4">{{ $t('member.updateProfile') }}</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('auth.username') }}</label>
        <input
          v-model="form.username"
          type="text"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          disabled
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('auth.email') }}</label>
        <input
          v-model="form.email"
          type="email"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('auth.phone') }}</label>
        <input
          v-model="form.phone"
          type="tel"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('member.department') }}</label>
        <input
          v-model="form.departmentId"
          type="text"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
        />
      </div>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400"
      >
        {{ isLoading ? $t('common.loading') : $t('common.save') }}
      </button>
    </form>

    <div v-if="success" class="mt-4 p-3 bg-green-100 text-green-700 rounded">
      {{ $t('member.profileUpdated') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const isLoading = ref(false)
const success = ref(false)

const form = ref({
  username: '',
  email: '',
  phone: '',
  departmentId: ''
})

onMounted(() => {
  if (authStore.user) {
    form.value = {
      username: authStore.user.username,
      email: authStore.user.email,
      phone: authStore.user.phone,
      departmentId: authStore.user.departmentId
    }
  }
})

const handleSubmit = async () => {
  try {
    isLoading.value = true
    success.value = false

    authStore.updateUser({
      email: form.value.email,
      phone: form.value.phone,
      departmentId: form.value.departmentId
    })

    success.value = true
    if (process.client) {
      setTimeout(() => {
        success.value = false
      }, 3000)
    }
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}
</script>
